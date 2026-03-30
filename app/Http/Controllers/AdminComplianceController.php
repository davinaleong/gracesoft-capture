<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DataAccessLog;
use App\Models\DataSubjectRequest;
use App\Services\DataSubjectRequestProcessor;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminComplianceController extends Controller
{
    public function index(Request $request, PlanGate $planGate): View
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

        return view('admin.compliance.index', [
            'accountId' => $accountId,
            'auditLogs' => $auditLogs,
            'dataAccessLogs' => $dataAccessLogs,
            'dsrRequests' => $dsrRequests,
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
}
