<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\HQService;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Form::query()->latest();
        $resolvedAccountId = $this->resolvedAccountId($request);

        if (! $this->isAdminOverride($request) && $resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        } elseif ($resolvedAccountId !== null) {
            $query->where('account_id', $resolvedAccountId);
        }

        return view('forms.index', [
            'forms' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeForRequest(request(), 'viewAny', Form::class);

        return view('forms.create');
    }

    public function store(Request $request, AuditLogger $auditLogger, PlanGate $planGate, HQService $hqService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'account_id' => ['required', 'uuid'],
            'application_id' => ['nullable', 'uuid'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $applicationId = (string) ($data['application_id'] ?? '');

        if ($applicationId === '') {
            $applicationId = (string) $hqService->createApplication($data['account_id'], $data['name']);

            if ($applicationId === '') {
                return back()->withErrors([
                    'application_id' => 'Unable to create application in HQ for this form.',
                ])->withInput();
            }
        }

        $resolvedAccountId = $this->resolvedAccountId($request);

        if (! $this->isAdminOverride($request) && $resolvedAccountId !== null && $data['account_id'] !== $resolvedAccountId) {
            abort(403, 'You can only create forms in your active account.');
        }

        if (! $this->isAdminOverride($request) && ! $planGate->formCreationAllowed($data['account_id'])) {
            return back()->withErrors([
                'plan' => 'Your current plan has reached the maximum number of forms.',
            ])->withInput();
        }

        if (! $hqService->validateApplication($data['account_id'], $applicationId)) {
            return back()->withErrors([
                'application_id' => 'The selected application could not be validated with HQ.',
            ])->withInput();
        }

        $this->authorizeForRequest($request, 'create', [Form::class, $data['account_id']]);

        $form = Form::create([
            'name' => $data['name'],
            'account_id' => $data['account_id'],
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
            'account_id' => ['required', 'uuid'],
            'application_id' => ['required', 'uuid'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        if (! $this->isAdminOverride($request) && $this->resolvedAccountId($request) !== null && $data['account_id'] !== $form->account_id) {
            abort(403, 'You are not allowed to move a form to another account.');
        }

        if (! $hqService->validateApplication($data['account_id'], $data['application_id'])) {
            return back()->withErrors([
                'application_id' => 'The selected application could not be validated with HQ.',
            ])->withInput();
        }

        $settings = $form->settings ?? [];
        $settings['notification_email'] = $data['notification_email'] ?? null;

        $form->update([
            'name' => $data['name'],
            'account_id' => $data['account_id'],
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
}
