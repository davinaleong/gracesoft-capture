<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\AccountMembership;
use App\Models\AuditLog;
use App\Models\BreakGlassApproval;
use App\Models\DataAccessLog;
use App\Models\DataSubjectRequest;
use App\Models\Enquiry;
use App\Models\SecurityEventSnapshot;
use App\Services\DataSubjectRequestProcessor;
use App\Support\AuditLogger;
use App\Support\PlanGate;
use App\Support\SecurityEventMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminComplianceController extends Controller
{
    public function index(Request $request, PlanGate $planGate, SecurityEventMetrics $securityEventMetrics): View
    {
        $admin = $this->requireAdministrator('compliance.view');

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

        $canViewSensitiveData = $admin->hasCapability('compliance.view_sensitive');
        $showSensitiveData = $canViewSensitiveData && $request->boolean('show_sensitive');

        $dsrRequests = DataSubjectRequest::query()
            ->when($accountId !== '', fn ($query) => $query->where('account_id', $accountId))
            ->latest('requested_at')
            ->limit(100)
            ->get()
            ->map(function (DataSubjectRequest $item) use ($showSensitiveData): DataSubjectRequest {
                $subjectIdentifier = is_string($item->subject_email) && $item->subject_email !== ''
                    ? $item->subject_email
                    : $item->subject_user_id;

                $item->setAttribute(
                    'subject_display',
                    $this->maskSensitiveIdentifier(is_string($subjectIdentifier) ? $subjectIdentifier : null, $showSensitiveData)
                );

                return $item;
            });

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

        $recertificationDueBefore = now()->subDays((int) config('capture.features.admin_access_recertification_days', 90));

        $administratorRecertifications = Administrator::query()
            ->where('status', 'active')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (Administrator $administrator) use ($recertificationDueBefore): Administrator {
                $administrator->setAttribute(
                    'recertification_due',
                    $administrator->compliance_recertified_at === null
                    || $administrator->compliance_recertified_at->lt($recertificationDueBefore)
                );

                return $administrator;
            });

        $globalMetrics = [
            'total_accounts' => AccountMembership::query()
                ->select('account_id')
                ->distinct()
                ->count('account_id'),
            'total_enquiries' => Enquiry::query()->count(),
            'open_enquiries' => Enquiry::query()
                ->whereIn('status', ['new', 'contacted'])
                ->count(),
            'pending_dsr' => DataSubjectRequest::query()
                ->where('status', 'pending')
                ->count(),
        ];

        $tenantHealth = Enquiry::query()
            ->select('account_id')
            ->selectRaw('count(*) as enquiries_total')
            ->selectRaw("sum(case when status in ('new','contacted') then 1 else 0 end) as open_enquiries")
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as enquiries_7d', [now()->subDays(7)])
            ->whereNotNull('account_id')
            ->groupBy('account_id')
            ->orderByDesc(DB::raw('open_enquiries'))
            ->limit(20)
            ->get();

        $abuseQueueThreshold = max((int) config('capture.features.abuse_queue_threshold', 3), 2);

        $abuseQueue = Enquiry::query()
            ->select('account_id', 'email')
            ->selectRaw('count(*) as submissions_count')
            ->whereNotNull('email')
            ->groupBy('account_id', 'email')
            ->havingRaw('count(*) >= ?', [$abuseQueueThreshold])
            ->orderByDesc(DB::raw('submissions_count'))
            ->limit(50)
            ->get();

        return view('admin.compliance.index', [
            'accountId' => $accountId,
            'auditLogs' => $auditLogs,
            'dataAccessLogs' => $dataAccessLogs,
            'dsrRequests' => $dsrRequests,
            'breakGlassApprovals' => $breakGlassApprovals,
            'verificationBlockedSummary' => $verificationBlockedSummary,
            'recentSecuritySnapshots' => $recentSecuritySnapshots,
            'administratorRecertifications' => $administratorRecertifications,
            'globalMetrics' => $globalMetrics,
            'tenantHealth' => $tenantHealth,
            'abuseQueue' => $abuseQueue,
            'abuseQueueThreshold' => $abuseQueueThreshold,
            'canViewSensitiveData' => $canViewSensitiveData,
            'showSensitiveData' => $showSensitiveData,
        ]);
    }

    public function recertifyAdministratorAccess(Request $request, Administrator $administrator, AuditLogger $auditLogger): RedirectResponse
    {
        $actor = $this->requireAdministrator('compliance.recertify_admin_access');

        if ($administrator->status !== 'active') {
            abort(422, 'Only active administrators can be recertified.');
        }

        if ($administrator->uuid === $actor->uuid) {
            abort(403, 'Administrator access recertification must be performed by a different administrator.');
        }

        $administrator->update([
            'compliance_recertified_at' => now(),
        ]);

        $auditLogger->log(
            $request,
            'admin.access.recertified',
            'administrator',
            (string) $administrator->id,
            null,
            [
                'administrator_uuid' => $administrator->uuid,
                'administrator_role' => $administrator->roleName(),
                'reviewer_uuid' => $actor->uuid,
            ]
        );

        return back()->with('status', 'Administrator access recertified.');
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

    private function maskSensitiveIdentifier(?string $value, bool $showSensitiveData): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if ($showSensitiveData) {
            return $value;
        }

        if (str_contains($value, '@')) {
            [$localPart, $domainPart] = explode('@', $value, 2);
            $visible = $localPart === '' ? '' : mb_substr($localPart, 0, 1);

            return $visible . '***@' . $domainPart;
        }

        $visiblePrefix = mb_substr($value, 0, 4);

        return $visiblePrefix . '***';
    }
}
