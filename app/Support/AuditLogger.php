<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\DataAccessLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(
        Request $request,
        string $action,
        string $targetType,
        ?string $targetId = null,
        ?string $accountId = null,
        array $metadata = []
    ): void {
        if (! (bool) config('capture.features.admin_audit_log_enabled', true)) {
            return;
        }

        [$actorType, $actorSourceTable, $actorId] = $this->resolveActor();

        AuditLog::query()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_source_table' => $actorSourceTable,
            'account_id' => $accountId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'access_reason' => $request->input('access_reason'),
            'metadata' => $this->redactMetadata($metadata),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function logDataAccess(
        Request $request,
        string $targetType,
        ?string $targetId = null,
        ?string $accountId = null,
        array $metadata = []
    ): void {
        if (! (bool) config('capture.features.admin_audit_log_enabled', true)) {
            return;
        }

        [$actorType, $actorSourceTable, $actorId] = $this->resolveActor();

        DataAccessLog::query()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_source_table' => $actorSourceTable,
            'account_id' => $accountId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'access_reason' => $request->input('access_reason'),
            'metadata' => $this->redactMetadata($metadata),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array{string, string, string|null}
     */
    private function resolveActor(): array
    {
        $admin = Auth::guard('admin')->user();
        $user = Auth::guard('web')->user();

        if ($admin) {
            return ['administrator', 'administrators', $admin->uuid];
        }

        if ($user) {
            return ['user', 'users', (string) $user->getAuthIdentifier()];
        }

        return ['system', 'system', null];
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function redactMetadata(array $metadata): array
    {
        $redactKeys = collect((array) config('capture.features.audit_metadata_redact_keys', []))
            ->map(fn ($key): string => strtolower((string) $key))
            ->filter()
            ->values()
            ->all();

        if ($redactKeys === []) {
            return $metadata;
        }

        return $this->redactMetadataRecursively($metadata, $redactKeys);
    }

    /**
     * @param array<string, mixed> $metadata
     * @param array<int, string> $redactKeys
     * @return array<string, mixed>
     */
    private function redactMetadataRecursively(array $metadata, array $redactKeys): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $redactKeys, true)) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->redactMetadataRecursively($value, $redactKeys);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
