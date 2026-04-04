<?php

namespace App\Http\Controllers;

use App\Models\AccountMembership;
use App\Models\Form;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\HQService;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FormManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Form::query()->latest();
        $billingAccountId = $this->resolveBillingAccountId($request);

        $billingRole = is_string($billingAccountId) && $billingAccountId !== ''
            ? $this->resolvedMembershipRole($request, $billingAccountId)
            : null;

        if (! is_string($billingRole) || $billingRole === '') {
            $billingRole = $this->fallbackMembershipRole($billingAccountId);
        }

        $currentSubscription = is_string($billingAccountId) && $billingAccountId !== ''
            ? Subscription::query()
                ->with('plan')
                ->where('account_id', $billingAccountId)
                ->orderByRaw("case when status in ('active', 'trialing', 'past_due') then 0 else 1 end")
                ->orderByDesc('updated_at')
                ->first()
            : null;

        $paidPlans = Plan::query()
            ->whereIn('slug', ['growth', 'pro'])
            ->orderByRaw("CASE slug WHEN 'growth' THEN 1 WHEN 'pro' THEN 2 ELSE 99 END")
            ->get();

        $upgradePlanCandidate = strtolower(trim((string) $request->query('upgrade', '')));
        $highlightedUpgradePlan = in_array($upgradePlanCandidate, ['growth', 'pro'], true)
            ? $upgradePlanCandidate
            : null;

        return view('forms.index', [
            'forms' => $query->paginate(15)->withQueryString(),
            'billingAccountId' => $billingAccountId,
            'billingRole' => $billingRole,
            'canManageBilling' => $this->isAdminOverride($request) || $billingRole === 'owner',
            'currentSubscription' => $currentSubscription,
            'paidPlans' => $paidPlans,
            'highlightedUpgradePlan' => $highlightedUpgradePlan,
        ]);
    }

    public function create(): View
    {
        $this->authorizeForRequest(request(), 'viewAny', Form::class);

        return view('forms.create');
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'account_id' => ['nullable', 'uuid'],
            'application_id' => ['nullable', 'uuid'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $accountId = $this->resolveOperationalAccountId($request, $data['account_id'] ?? null);

        $applicationId = (string) ($data['application_id'] ?? '');

        if ($applicationId === '') {
            // Local-first creation: assign an internal application id when omitted.
            $applicationId = (string) Str::uuid();
        }

        if (! $this->isAdminOverride($request) && ! $planGate->formCreationAllowed($accountId)) {
            return back()->withErrors([
                'plan' => 'Your current plan has reached the maximum number of forms.',
            ])->withInput();
        }

        $this->authorizeForRequest($request, 'create', [Form::class, $accountId]);

        $form = Form::create([
            'name' => $data['name'],
            'account_id' => $accountId,
            'application_id' => $applicationId,
            'is_active' => true,
            'settings' => [
                'notification_email' => $data['notification_email'] ?? null,
            ],
        ]);

        $auditLogger->log(
            $request,
            'forms.create',
            'form',
            (string) $form->uuid,
            $form->account_id,
            ['admin_override' => $this->isAdminOverride($request)]
        );

        return redirect()
            ->route('manage.forms.edit', $form)
            ->with('status', 'Form created successfully.');
    }

    public function edit(Request $request, Form $form, AuditLogger $auditLogger): View
    {
        $this->authorizeAccountAccess($request, $form->account_id);
        $this->authorizeForRequest($request, 'view', $form);

        if ($this->isAdminOverride($request)) {
            $this->requireAdminAccessReason($request);

            $auditLogger->logDataAccess(
                $request,
                'form',
                (string) $form->uuid,
                $form->account_id,
                ['source' => 'manage.forms.edit']
            );
        }

        return view('forms.edit', [
            'form' => $form,
        ]);
    }

    public function update(Request $request, Form $form, AuditLogger $auditLogger, HQService $hqService): RedirectResponse
    {
        $this->authorizeAccountAccess($request, $form->account_id);
        $this->authorizeForRequest($request, 'update', $form);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'account_id' => ['nullable', 'uuid'],
            'application_id' => ['nullable', 'uuid'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $accountId = $this->resolveOperationalAccountId($request, $data['account_id'] ?? $form->account_id);

        $applicationId = (string) ($data['application_id'] ?? $form->application_id);

        if ($applicationId !== (string) $form->application_id && ! $hqService->validateApplication($accountId, $applicationId)) {
            return back()->withErrors([
                'application_id' => 'The selected application could not be validated with HQ.',
            ])->withInput();
        }

        $settings = $form->settings ?? [];
        $settings['notification_email'] = $data['notification_email'] ?? null;

        $form->update([
            'name' => $data['name'],
            'account_id' => $accountId,
            'application_id' => $applicationId,
            'settings' => $settings,
        ]);

        $auditLogger->log(
            $request,
            'forms.update',
            'form',
            (string) $form->uuid,
            $form->account_id,
            ['admin_override' => $this->isAdminOverride($request)]
        );

        return redirect()
            ->route('manage.forms.edit', $form)
            ->with('status', 'Form updated successfully.');
    }

    public function toggleActive(Request $request, Form $form, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorizeAccountAccess($request, $form->account_id);
        $this->authorizeForRequest($request, 'toggleActive', $form);

        $form->update([
            'is_active' => ! $form->is_active,
        ]);

        $auditLogger->log(
            $request,
            'forms.toggle_active',
            'form',
            (string) $form->uuid,
            $form->account_id,
            [
                'is_active' => (bool) $form->is_active,
                'admin_override' => $this->isAdminOverride($request),
            ]
        );

        return redirect()
            ->route('manage.forms.index')
            ->with('status', $form->is_active ? 'Form activated.' : 'Form deactivated.');
    }

    private function resolveOperationalAccountId(Request $request, mixed $candidate): string
    {
        $resolved = $this->resolvedAccountId($request);

        if (is_string($resolved) && $resolved !== '') {
            return $resolved;
        }

        if (is_string($candidate) && $candidate !== '') {
            return $candidate;
        }

        $fallback = (string) config('capture.features.default_account_id', '');

        if ($fallback !== '' && Str::isUuid($fallback)) {
            return $fallback;
        }

        return '00000000-0000-0000-0000-000000000001';
    }

    private function resolveBillingAccountId(Request $request): ?string
    {
        $resolvedAccountId = $this->resolvedAccountId($request);

        if (is_string($resolvedAccountId) && $resolvedAccountId !== '') {
            return $resolvedAccountId;
        }

        $sessionAccountId = (string) $request->session()->get('active_account_id', '');

        if ($sessionAccountId !== '' && Str::isUuid($sessionAccountId)) {
            return $sessionAccountId;
        }

        $user = Auth::guard('web')->user();

        if (! $user) {
            return null;
        }

        $ownerAccountId = (string) AccountMembership::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('role', 'owner')
            ->whereNull('removed_at')
            ->value('account_id');

        if ($ownerAccountId !== '') {
            return $ownerAccountId;
        }

        $memberAccountId = (string) AccountMembership::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereNull('removed_at')
            ->value('account_id');

        return $memberAccountId !== '' ? $memberAccountId : null;
    }

    private function fallbackMembershipRole(?string $accountId): ?string
    {
        $user = Auth::guard('web')->user();

        if (! $user || ! is_string($accountId) || $accountId === '') {
            return null;
        }

        $role = AccountMembership::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('account_id', $accountId)
            ->whereNull('removed_at')
            ->value('role');

        return is_string($role) && $role !== '' ? $role : null;
    }
}
