<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\AccountProvisioningService;
use Illuminate\View\View;

class UserSessionController extends Controller
{
    public function register(Request $request): View
    {
        return view('auth.user-register', [
            'upgradePlan' => $this->rememberUpgradePlanIntent($request),
        ]);
    }

    public function storeRegistration(Request $request, AccountProvisioningService $provisioningService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::guard('admin')->logout();
        $request->session()->forget('admin.last_activity_at');

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->sendEmailVerificationNotification();

        $account = $provisioningService->provisionForUser($user);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.guard_context', 'web');
        $request->session()->put('active_account_id', $account->id);

        $upgradePlan = $this->pullUpgradePlanIntent($request);

        if ($upgradePlan !== null) {
            return redirect()
                ->route('manage.forms.index', ['upgrade' => $upgradePlan])
                ->with('status', 'Account created. Continue with your ' . ucfirst($upgradePlan) . ' upgrade.');
        }

        return redirect()->route('verification.notice')->with('status', 'Account created. Please verify your email address.');
    }

    public function create(Request $request): View
    {
        return view('auth.user-login', [
            'upgradePlan' => $this->rememberUpgradePlanIntent($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Keep user and admin sessions mutually exclusive.
        Auth::guard('admin')->logout();
        $request->session()->forget('admin.last_activity_at');

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'email' => 'Invalid user credentials.',
                ]);
        }

        $request->session()->regenerate();
        $request->session()->put('auth.guard_context', 'web');

        $upgradePlan = $this->pullUpgradePlanIntent($request);

        if ($upgradePlan !== null) {
            return redirect()
                ->route('manage.forms.index', ['upgrade' => $upgradePlan])
                ->with('status', 'Continue with your ' . ucfirst($upgradePlan) . ' upgrade from the dashboard.');
        }

        return redirect()->intended(route('manage.forms.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'User session signed out.');
    }

    private function rememberUpgradePlanIntent(Request $request): ?string
    {
        $queryPlan = $this->normalizeUpgradePlan($request->query('plan'));

        if ($queryPlan !== null) {
            $request->session()->put('billing_upgrade_plan', $queryPlan);

            return $queryPlan;
        }

        return $this->normalizeUpgradePlan($request->session()->get('billing_upgrade_plan'));
    }

    private function pullUpgradePlanIntent(Request $request): ?string
    {
        return $this->normalizeUpgradePlan($request->session()->pull('billing_upgrade_plan'));
    }

    private function normalizeUpgradePlan(mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $plan = strtolower(trim($candidate));

        return in_array($plan, ['growth', 'pro'], true) ? $plan : null;
    }
}
