<?php

namespace App\Policies\Concerns;

use App\Models\AccountMembership;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

trait ResolvesAccountAuthorization
{
    protected function canReadAccount(Authenticatable $actor, string $accountId): bool
    {
        if (! $this->accessContextEnforced()) {
            return true;
        }

        if ($this->isAdminOverride()) {
            return true;
        }

        if ($actor instanceof Administrator) {
            return false;
        }

        if (! $actor instanceof User) {
            return false;
        }

        return $this->hasMembershipRole($actor, $accountId, ['owner', 'member', 'viewer']);
    }

    protected function canWriteAccount(Authenticatable $actor, string $accountId): bool
    {
        if (! $this->accessContextEnforced()) {
            return true;
        }

        if ($this->isAdminOverride()) {
            return true;
        }

        if (! $actor instanceof User) {
            return false;
        }

        return $this->hasMembershipRole($actor, $accountId, ['owner', 'member']);
    }

    protected function accessContextEnforced(): bool
    {
        return (bool) config('capture.features.enforce_access_context', false);
    }

    protected function isAdminOverride(): bool
    {
        return (bool) $this->request()?->attributes->get('access.is_admin_override', false);
    }

    private function hasMembershipRole(User $user, string $accountId, array $roles): bool
    {
        return AccountMembership::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('account_id', $accountId)
            ->whereNull('removed_at')
            ->whereIn('role', $roles)
            ->exists();
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        return $request instanceof Request ? $request : null;
    }
}
