<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Plan;
use App\Services\StripeBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function checkout(Request $request, StripeBillingService $stripeBillingService): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'string', 'in:growth,pro'],
            'account_id' => ['nullable', 'uuid'],
        ]);

        $account = $this->resolveOwnerAccount($request, $data['account_id'] ?? null);

        $plan = Plan::query()->where('slug', $data['plan'])->firstOrFail();
        $priceId = trim((string) $plan->stripe_price_id);

        if ($priceId === '') {
            return back()->withErrors([
                'plan' => 'Selected plan is not currently available for paid checkout.',
            ]);
        }

        $checkoutUrl = $stripeBillingService->createCheckoutSession($account, $priceId);

        return redirect()->away($checkoutUrl);
    }

    public function portal(Request $request, StripeBillingService $stripeBillingService): RedirectResponse
    {
        $request->validate([
            'account_id' => ['nullable', 'uuid'],
        ]);

        $account = $this->resolveOwnerAccount($request, $request->input('account_id'));
        $portalUrl = $stripeBillingService->createPortalSession($account);

        return redirect()->away($portalUrl);
    }

    private function resolveOwnerAccount(Request $request, mixed $candidateAccountId = null): Account
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $accountId = is_string($candidateAccountId) && $candidateAccountId !== ''
            ? $candidateAccountId
            : (string) ($request->session()->get('active_account_id') ?? '');

        if ($accountId === '') {
            $accountId = (string) AccountMembership::query()
                ->where('user_id', $user->id)
                ->where('role', 'owner')
                ->whereNull('removed_at')
                ->value('account_id');
        }

        abort_if($accountId === '', 403, 'No owner account found for billing operations.');

        $ownerMembership = AccountMembership::query()
            ->where('user_id', $user->id)
            ->where('account_id', $accountId)
            ->where('role', 'owner')
            ->whereNull('removed_at')
            ->exists();

        abort_unless($ownerMembership, 403, 'Only account owners can manage billing.');

        $account = Account::query()->find($accountId);

        abort_unless($account instanceof Account, 403, 'Account workspace is not provisioned for billing.');

        return $account;
    }
}
