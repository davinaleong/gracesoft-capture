<?php

namespace App\Http\Controllers;

use App\Models\AccountMembership;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserSecuritySettingsController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $billingAccountId = $this->resolveBillingAccountId($request = request(), (int) $user->getAuthIdentifier());

        $currentSubscription = is_string($billingAccountId) && $billingAccountId !== ''
            ? Subscription::query()
                ->with('plan')
                ->where('account_id', $billingAccountId)
                ->orderByRaw("case when status in ('active', 'trialing', 'past_due') then 0 else 1 end")
                ->orderByDesc('updated_at')
                ->first()
            : null;

        return view('settings.security', [
            'user' => $user,
            'twoFactorEnabled' => $user->two_factor_enabled_at !== null,
            'billingAccountId' => $billingAccountId,
            'currentSubscription' => $currentSubscription,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], (string) $user->password)) {
            return back()->withErrors([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
        ])->save();

        return back()->with('status', 'Password updated successfully.');
    }

    public function toggleTwoFactor(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        abort_unless($user !== null, 401);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $data['enabled'];

        $user->forceFill([
            'two_factor_enabled_at' => $enabled ? now() : null,
        ])->save();

        return back()->with('status', $enabled ? 'Two-factor authentication enabled.' : 'Two-factor authentication disabled.');
    }

    private function resolveBillingAccountId(Request $request, int $userId): ?string
    {
        $sessionAccountId = (string) $request->session()->get('active_account_id', '');

        if ($sessionAccountId !== '' && Str::isUuid($sessionAccountId)) {
            return $sessionAccountId;
        }

        $ownerAccountId = (string) AccountMembership::query()
            ->where('user_id', $userId)
            ->where('role', 'owner')
            ->whereNull('removed_at')
            ->value('account_id');

        if ($ownerAccountId !== '') {
            return $ownerAccountId;
        }

        $memberAccountId = (string) AccountMembership::query()
            ->where('user_id', $userId)
            ->whereNull('removed_at')
            ->value('account_id');

        return $memberAccountId !== '' ? $memberAccountId : null;
    }
}
