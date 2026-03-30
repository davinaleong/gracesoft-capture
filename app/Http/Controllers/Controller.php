<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    protected function resolvedAccountId(Request $request): ?string
    {
        $accountId = $request->attributes->get('access.account_id');

        return is_string($accountId) && $accountId !== '' ? $accountId : null;
    }

    protected function isAdminOverride(Request $request): bool
    {
        return (bool) $request->attributes->get('access.is_admin_override', false);
    }

    protected function authorizeAccountAccess(Request $request, string $accountId): void
    {
        if (! (bool) config('capture.features.enforce_access_context', false)) {
            return;
        }

        if ($this->isAdminOverride($request)) {
            return;
        }

        if ($this->resolvedAccountId($request) !== $accountId) {
            abort(403, 'You are not allowed to access this account data.');
        }
    }

    protected function requireAdminAccessReason(Request $request): void
    {
        if (! $this->isAdminOverride($request)) {
            return;
        }

        $reason = trim((string) $request->input('access_reason', ''));

        if ($reason === '') {
            abort(422, 'access_reason is required for administrator override access.');
        }
    }
}
