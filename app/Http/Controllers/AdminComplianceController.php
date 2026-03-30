<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BreakGlassApproval;
use App\Models\DataAccessLog;
use App\Models\DataSubjectRequest;
use App\Models\SecurityEventSnapshot;
use App\Services\DataSubjectRequestProcessor;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use App\Support\SecurityEventMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminComplianceController extends Controller
{
    public function index(Request $request, PlanGate $planGate, SecurityEventMetrics $securityEventMetrics): View
    {
        $this->requireAdministrator('compliance.view');

        $accountId = $request->string('account_id')->toString();

        if (! $planGate->complianceViewsEnabled($accountId)) {
            abort(403, 'Advanced compliance views are available on Pro plan accounts only.');
        }

        $auditLogs = AuditLog::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->latest('created_at')
            ->limit(100)
            ->get();

        $dataAccessLogs = DataAccessLog::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->latest('created_at')
            ->limit(100)
            ->get();

        $dsrRequests = DataSubjectRequest::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->latest('requested_at')
            ->limit(100)
            ->get();

        $breakGlassApprovals = BreakGlassApproval::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->latest('requested_at')
            ->limit(20)
            ->get();

        $verificationBlockedSummary = $securityEventMetrics->verificationBlockedSummary();
        $recentSecuritySnapshots = SecurityEventSnapshot::query()
            ->where('metric_key', 'like', 'verification_blocked:%')
            ->latest('snapshot_date')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.compliance.index', [
            'accountId' => $accountId,
            'auditLogs' => $auditLogs,
            'dataAccessLogs' => $dataAccessLogs,
            'dsrRequests' => $dsrRequests,
            'breakGlassApprovals' => $breakGlassApprovals,
            'verificationBlockedSummary' => $verificationBlockedSummary,
            'recentSecuritySnapshots' => $recentSecuritySnapshots,
        ]);
    }

    public function updateDsrStatus(
        Request $request,
        DataSubjectRequest $dataSubjectRequest,
        AuditLogger $auditLogger,
        PlanGate $planGate
    ): RedirectResponse
    {
        $admin = $this->requireAdministrator('compliance.manage_dsr_status');

        if (! $planGate->complianceViewsEnabled($dataSubjectRequest->account_id)) {
            abort(403, 'Advanced compliance views are available on Pro plan accounts only.');
        }

        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,rejected'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $dataSubjectRequest->update([
            'status' => $data['status'],
            'reason' => $data['reason'] ?? $dataSubjectRequest->reason,
            'resolved_at' => in_array($data['status'], ['completed', 'rejected'], true) ? now() : null,
            'resolved_by_administrator_uuid' => $admin?->uuid,
        ]);

        $auditLogger->log(
            $request,
            'dsr.status.update',
            'data_subject_request',
            (string) $dataSubjectRequest->id,
            $dataSubjectRequest->account_id,
            [
                'status' => $dataSubjectRequest->status,
            ]
        );

        return back()->with('status', 'Data subject request status updated.');
    }

    public function processDsr(
        Request $request,
        DataSubjectRequest $dataSubjectRequest,
        DataSubjectRequestProcessor $processor,
        AuditLogger $auditLogger,
        PlanGate $planGate
    ): RedirectResponse {
        $admin = $this->requireAdministrator('compliance.process_dsr');

        if (! $planGate->complianceViewsEnabled($dataSubjectRequest->account_id)) {
            abort(403, 'Advanced compliance views are available on Pro plan accounts only.');
        }

        $this->enforceBreakGlassIfRequired($dataSubjectRequest, $admin->uuid);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $metadata = $processor->process(
            $dataSubjectRequest,
            (string) $admin?->uuid,
            $data['reason'] ?? null,
        );

        $auditLogger->log(
            $request,
            sprintf('dsr.process.%s', $dataSubjectRequest->request_type),
            'data_subject_request',
            (string) $dataSubjectRequest->id,
            $dataSubjectRequest->account_id,
            [
                'status' => $dataSubjectRequest->status,
                'processed_operation' => data_get($metadata, 'processed_operation'),
            ]
        );

        return back()->with('status', 'Data subject request processed.');
    }

    public function requestBreakGlass(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $admin = $this->requireAdministrator('compliance.break_glass.request');

        $data = $request->validate([
            'account_id' => ['nullable', 'uuid'],
            'scope' => ['required', 'string', 'max:120'],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $approval = BreakGlassApproval::query()->create([
            'account_id' => $data['account_id'] ?? null,
            'scope' => $data['scope'],
            'requested_by_administrator_uuid' => (string) $admin->uuid,
            'reason' => $data['reason'],
            'requested_at' => now(),
        ]);

        $auditLogger->log(
            $request,
            'break_glass.requested',
            'break_glass_approval',
            (string) $approval->id,
            $approval->account_id,
            [
                'scope' => $approval->scope,
                'reason_length' => strlen((string) $approval->reason),
            ]
        );

        return back()->with('status', 'Break-glass request submitted.');
    }

    public function approveBreakGlass(Request $request, BreakGlassApproval $breakGlassApproval, AuditLogger $auditLogger): RedirectResponse
    {
        $admin = $this->requireAdministrator('compliance.break_glass.approve');

        if ($breakGlassApproval->requested_by_administrator_uuid === (string) $admin->uuid) {
            abort(403, 'Break-glass approval must be performed by a different administrator.');
        }

        $data = $request->validate([
            'expires_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);

        $expiryMinutes = (int) ($data['expires_minutes'] ?? config('capture.features.break_glass_default_expiry_minutes', 30));

        $breakGlassApproval->update([
            'approved_by_administrator_uuid' => (string) $admin->uuid,
            'approved_at' => now(),
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        $auditLogger->log(
            $request,
            'break_glass.approved',
            'break_glass_approval',
            (string) $breakGlassApproval->id,
            $breakGlassApproval->account_id,
            [
                'scope' => $breakGlassApproval->scope,
                'expires_at' => $breakGlassApproval->expires_at?->toIso8601String(),
                'requested_by' => $breakGlassApproval->requested_by_administrator_uuid,
            ]
        );

        return back()->with('status', 'Break-glass request approved.');
    }

    private function enforceBreakGlassIfRequired(DataSubjectRequest $dataSubjectRequest, string $adminUuid): void
    {
        if (! (bool) config('capture.features.require_break_glass_for_sensitive_dsr', false)) {
            return;
        }

        if (! in_array($dataSubjectRequest->request_type, ['delete', 'restrict'], true)) {
            return;
        }

        $hasActiveApproval = BreakGlassApproval::query()
            ->active()
            ->where('scope', 'dsr_sensitive')
            ->where('account_id', $dataSubjectRequest->account_id)
            ->where('approved_by_administrator_uuid', '!=', $adminUuid)
            ->exists();

        if (! $hasActiveApproval) {
            abort(403, 'Active break-glass approval is required for sensitive DSR processing.');
        }
    }
}
