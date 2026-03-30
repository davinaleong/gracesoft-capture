<?php

namespace App\Support;

use App\Models\AuditLog;
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

        $admin = Auth::guard('admin')->user();
        $user = Auth::guard('web')->user();

        $actorType = 'system';
        $actorSourceTable = 'system';
        $actorId = null;

        if ($admin) {
            $actorType = 'administrator';
            $actorSourceTable = 'administrators';
            $actorId = $admin->uuid;
        } elseif ($user) {
            $actorType = 'user';
            $actorSourceTable = 'users';
            $actorId = (string) $user->getAuthIdentifier();
        }

        AuditLog::query()->create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_source_table' => $actorSourceTable,
            'account_id' => $accountId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'access_reason' => $request->input('access_reason'),
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
