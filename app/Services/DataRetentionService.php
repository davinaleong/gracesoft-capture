<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\DataAccessLog;
use App\Models\DataSubjectRequest;
use App\Models\Enquiry;

class DataRetentionService
{
    /**
     * @return array<string, int>
     */
    public function cleanup(): array
    {
        $retentionDays = (int) config('capture.features.data_retention_days', 365);

        if ($retentionDays <= 0) {
            return [
                'deleted_audit_logs' => 0,
                'deleted_data_access_logs' => 0,
                'deleted_consents' => 0,
                'deleted_resolved_dsr' => 0,
                'anonymized_enquiries' => 0,
            ];
        }

        $cutoff = now()->subDays($retentionDays);

        $deletedAuditLogs = AuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedDataAccessLogs = DataAccessLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedConsents = Consent::query()
            ->where('accepted_at', '<', $cutoff)
            ->delete();

        $deletedResolvedDsr = DataSubjectRequest::query()
            ->whereIn('status', ['completed', 'rejected'])
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<', $cutoff)
            ->delete();

        $anonymizedEnquiries = 0;

        Enquiry::query()
            ->whereNotNull('closed_at')
            ->where('closed_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($enquiries) use (&$anonymizedEnquiries): void {
                foreach ($enquiries as $enquiry) {
                    $metadata = $enquiry->metadata;

                    if (! is_array($metadata)) {
                        $metadata = [];
                    }

                    $metadata['retention'] = array_merge($metadata['retention'] ?? [], [
                        'anonymized' => true,
                        'anonymized_at' => now()->toIso8601String(),
                        'source' => 'scheduled_cleanup',
                    ]);

                    $enquiry->update([
                        'name' => 'Retained Subject',
                        'email' => sprintf('retained+%d@redacted.local', $enquiry->id),
                        'subject' => 'Retained by policy',
                        'message' => '[REDACTED BY RETENTION POLICY]',
                        'metadata' => $metadata,
                    ]);

                    $anonymizedEnquiries++;
                }
            });

        return [
            'deleted_audit_logs' => $deletedAuditLogs,
            'deleted_data_access_logs' => $deletedDataAccessLogs,
            'deleted_consents' => $deletedConsents,
            'deleted_resolved_dsr' => $deletedResolvedDsr,
            'anonymized_enquiries' => $anonymizedEnquiries,
        ];
    }
}
