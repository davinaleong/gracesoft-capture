<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Plan;
use App\Services\StripeBillingService;
use App\Services\StripeCatalogSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class BillingController extends Controller
{
    public function showPlan(Request $request, string $plan): View
    {
        abort_unless(in_array($plan, ['growth', 'pro'], true), 404);

        $account = $this->resolveOwnerAccount($request, $request->query('account_id'));

        return view('billing.plan', [
            'selectedPlan' => $plan,
            'billingAccountId' => $account->id,
            'plans' => [
                'free' => [
                    'name' => 'Free',
                    'price' => '$0 / month',
                    'headline' => 'Get started, stay organized',
                    'features' => [
                        '1 personal inbox',
                        'Up to 100 captured items',
                        'Light follow-ups',
                        'Simple, distraction-free capture',
                    ],
                    'best_for' => 'Best for individuals getting their workflow in place',
                ],
                'growth' => [
                    'name' => 'Growth',
                    'price' => '$9 / month',
                    'headline' => 'Collaborate and scale your workflow',
                    'features' => [
                        'Up to 5 collaborators in one inbox',
                        'Up to 1,000 captured items',
                        'Up to 10,000 follow-ups',
                        'Shared workflow across your team',
                        'Basic support',
                    ],
                    'best_for' => 'Best for small teams managing real work together',
                ],
                'pro' => [
                    'name' => 'Pro',
                    'price' => '$29 / month',
                    'headline' => 'Operate with clarity and insight',
                    'features' => [
                        'Up to 20 collaborators in one inbox',
                        'Unlimited capture and follow-ups',
                        'Priority support',
                        'Metrics dashboard (understand where time goes)',
                        'Attach notes to any item or reply for full context',
                    ],
                    'best_for' => 'Best for teams who want visibility, accountability, and optimization',
                ],
            ],
        ]);
    }

    public function checkout(
        Request $request,
        StripeBillingService $stripeBillingService,
        StripeCatalogSyncService $stripeCatalogSyncService
    ): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', 'string', 'in:growth,pro'],
            'account_id' => ['nullable', 'uuid'],
        ]);

        $account = $this->resolveOwnerAccount($request, $data['account_id'] ?? null);

        $plan = Plan::query()->where('slug', $data['plan'])->firstOrFail();
        $priceId = trim((string) $plan->stripe_price_id);

        if ($priceId === '') {
            try {
                $stripeCatalogSyncService->syncFromStripe($stripeBillingService);
                $plan->refresh();
                $priceId = trim((string) $plan->stripe_price_id);
            } catch (RuntimeException $exception) {
                Log::warning('Unable to sync Stripe catalog before checkout.', [
                    'account_id' => $account->id,
                    'plan_slug' => (string) $plan->slug,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($priceId === '') {
            return back()->withErrors([
                'plan' => 'Selected plan is not currently available for paid checkout.',
            ]);
        }

        try {
            $checkoutUrl = $stripeBillingService->createCheckoutSession($account, $priceId, (string) $plan->slug);
        } catch (RuntimeException $exception) {
            Log::warning('Unable to create Stripe checkout session.', [
                'account_id' => $account->id,
                'plan_slug' => (string) $plan->slug,
                'price_id' => $priceId,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'plan' => 'Unable to start checkout right now. Please try again in a moment.',
            ])->withInput();
        }

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
