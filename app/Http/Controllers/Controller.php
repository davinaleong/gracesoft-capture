<?php

namespace App\Http\Controllers;

use App\Models\AccountMembership;
use Illuminate\Support\Facades\Auth;
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

    protected function resolvedMembershipRole(Request $request, ?string $accountId = null): ?string
    {
        if (! (bool) config('capture.features.enforce_access_context', false)) {
            return null;
        }

        if ($this->isAdminOverride($request)) {
            return 'administrator';
        }

        $user = Auth::guard('web')->user();

        if (! $user) {
            return null;
        }

        $resolvedAccountId = $accountId ?? $this->resolvedAccountId($request);

        if (! is_string($resolvedAccountId) || $resolvedAccountId === '') {
            return null;
        }

        return AccountMembership::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('account_id', $resolvedAccountId)
            ->whereNull('removed_at')
            ->value('role');
    }

    protected function authorizeAnyRole(Request $request, array $allowedRoles, ?string $accountId = null): void
    {
        if (! (bool) config('capture.features.enforce_access_context', false)) {
            return;
        }

        if ($this->isAdminOverride($request)) {
            return;
        }

        $role = $this->resolvedMembershipRole($request, $accountId);

        if (! is_string($role) || ! in_array($role, $allowedRoles, true)) {
            abort(403, 'Your collaborator role is not allowed to perform this action.');
        }
    }
}
