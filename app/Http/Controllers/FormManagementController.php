<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\HQService;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FormManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Form::query()->latest();

        return view('forms.index', [
            'forms' => $query->paginate(15)->withQueryString(),
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
            'application_id' => ['required', 'uuid'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $accountId = $this->resolveOperationalAccountId($request, $data['account_id'] ?? $form->account_id);

        if (! $hqService->validateApplication($accountId, $data['application_id'])) {
            return back()->withErrors([
                'application_id' => 'The selected application could not be validated with HQ.',
            ])->withInput();
        }

        $settings = $form->settings ?? [];
        $settings['notification_email'] = $data['notification_email'] ?? null;

        $form->update([
            'name' => $data['name'],
            'account_id' => $accountId,
            'application_id' => $data['application_id'],
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
}
