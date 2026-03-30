<?php

namespace App\Http\Middleware;

use App\Models\AccountMembership;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveAccessContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $enforce = (bool) config('capture.features.enforce_access_context', false);

        $isAdmin = Auth::guard('admin')->check();
        $isAdminOverride = $isAdmin && $this->adminOverrideRequested($request);

        $request->attributes->set('access.is_admin', $isAdmin);
        $request->attributes->set('access.is_admin_override', $isAdminOverride);

        if ($isAdminOverride) {
            $request->attributes->set('access.account_id', $this->extractAccountId($request));

            return $next($request);
        }

        if ($isAdmin) {
            $request->attributes->set('access.account_id', $this->extractAccountId($request));

            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if (! $user) {
            if (! $enforce) {
                $request->attributes->set('access.account_id', $this->extractAccountId($request));

                return $next($request);
            }

            abort(401);
        }

        $accountId = $this->extractAccountId($request);

        if (! $accountId) {
            $accountId = (string) ($request->session()->get('active_account_id') ?? '');
        }

        if ($accountId === '') {
            $accountId = (string) AccountMembership::query()
                ->where('user_id', $user->getAuthIdentifier())
                ->whereNull('removed_at')
                ->value('account_id');
        }

        if ($accountId === '') {
            if (! $enforce) {
                return $next($request);
            }

            abort(403, 'No account membership found for the authenticated user.');
        }

        $hasMembership = AccountMembership::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('account_id', $accountId)
            ->whereNull('removed_at')
            ->exists();

        if (! $hasMembership) {
            if (! $enforce) {
                return $next($request);
            }

            abort(403, 'You are not allowed to access this account.');
        }

        $request->session()->put('active_account_id', $accountId);
        $request->attributes->set('access.account_id', $accountId);

        return $next($request);
    }

    private function adminOverrideRequested(Request $request): bool
    {
        if ($request->boolean('admin_override')) {
            return true;
        }

        return in_array((string) $request->header('X-Admin-Override', ''), ['1', 'true', 'yes'], true);
    }

    private function extractAccountId(Request $request): ?string
    {
        $candidate = $request->route('account_id')
            ?? $request->query('account_id')
            ?? $request->input('account_id');

        if (! is_string($candidate) || $candidate === '') {
            return null;
        }

        return $candidate;
    }
}
