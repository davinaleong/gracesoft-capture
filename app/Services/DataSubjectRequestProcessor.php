<?php

namespace App\Services;

use App\Models\DataSubjectRequest;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DataSubjectRequestProcessor
{
    /**
     * @return array<string, mixed>
     */
    public function process(DataSubjectRequest $request, string $administratorUuid, ?string $reason = null): array
    {
        return DB::transaction(function () use ($request, $administratorUuid, $reason): array {
            $operation = $request->request_type;

            $evidence = match ($operation) {
                'export' => $this->processExport($request),
                'delete' => $this->processDelete($request),
                'restrict' => $this->processRestrict($request),
                default => ['message' => 'Unsupported request type.'],
            };

            $metadata = array_merge($request->resolution_metadata ?? [], [
                'processed_operation' => $operation,
                'processed_at' => now()->toIso8601String(),
                'processed_by_administrator_uuid' => $administratorUuid,
                'provided_reason' => $reason,
                'evidence' => $evidence,
            ]);

            $request->update([
                'status' => 'completed',
                'reason' => $reason ?? $request->reason,
                'resolved_at' => now(),
                'resolved_by_administrator_uuid' => $administratorUuid,
                'resolution_metadata' => $metadata,
            ]);

            return $metadata;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function processExport(DataSubjectRequest $request): array
    {
        $query = $this->subjectEnquiriesQuery($request);

        $matched = (clone $query)->count();
        $statusBreakdown = (clone $query)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(static fn ($count): int => (int) $count)
            ->all();

        $sampleEnquiryUuids = (clone $query)
            ->latest('created_at')
            ->limit(10)
            ->pluck('uuid')
            ->values()
            ->all();

        return [
            'matched_enquiries' => $matched,
            'status_breakdown' => $statusBreakdown,
            'sample_enquiry_uuids' => $sampleEnquiryUuids,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function processDelete(DataSubjectRequest $request): array
    {
        $enquiries = $this->subjectEnquiriesQuery($request)->get(['id', 'metadata']);
        $processed = 0;

        foreach ($enquiries as $enquiry) {
            $metadata = $enquiry->metadata;

            if (! is_array($metadata)) {
                $metadata = [];
            }

            $metadata['dsr'] = array_merge($metadata['dsr'] ?? [], [
                'action' => 'delete',
                'request_id' => $request->id,
                'processed_at' => now()->toIso8601String(),
            ]);

            $enquiry->update([
                'name' => 'Deleted Subject',
                'email' => sprintf('deleted+%d@redacted.local', $enquiry->id),
                'subject' => 'Deleted by data subject request',
                'message' => '[REDACTED]',
                'metadata' => $metadata,
            ]);

            $processed++;
        }

        return [
            'anonymized_enquiries' => $processed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function processRestrict(DataSubjectRequest $request): array
    {
        $enquiries = $this->subjectEnquiriesQuery($request)->get(['id', 'metadata']);
        $processed = 0;

        foreach ($enquiries as $enquiry) {
            $metadata = $enquiry->metadata;

            if (! is_array($metadata)) {
                $metadata = [];
            }

            $metadata['dsr'] = array_merge($metadata['dsr'] ?? [], [
                'action' => 'restrict',
                'request_id' => $request->id,
                'restricted' => true,
                'processed_at' => now()->toIso8601String(),
            ]);

            $enquiry->update([
                'metadata' => $metadata,
            ]);

            $processed++;
        }

        return [
            'restricted_enquiries' => $processed,
        ];
    }

    private function subjectEnquiriesQuery(DataSubjectRequest $request): Builder
    {
        $query = Enquiry::query();

        if (is_string($request->account_id) && $request->account_id !== '') {
            $query->where('account_id', $request->account_id);
        }

        if (is_string($request->subject_email) && $request->subject_email !== '') {
            $query->where('email', $request->subject_email);

            return $query;
        }

        // Process only explicitly identified subjects.
        return $query->whereRaw('1 = 0');
    }
}
