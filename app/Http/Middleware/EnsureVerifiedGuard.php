<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use App\Support\SecurityEventMetrics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedGuard
{
    public function handle(Request $request, Closure $next, string $guard, string $scope): Response
    {
        if (! $this->scopeEnabled($scope)) {
            return $next($request);
        }

        $actor = Auth::guard($guard)->user();

        if (! $actor) {
            abort(401);
        }

        if (! method_exists($actor, 'hasVerifiedEmail') || $actor->hasVerifiedEmail()) {
            return $next($request);
        }

        $this->recordBlockedEvent($request, $guard, $scope);

        if ($guard === 'admin') {
            return redirect()->route('admin.verification.notice')
                ->withErrors(['email' => 'Administrator email verification is required for this action.']);
        }

        return redirect()->route('verification.notice')
            ->withErrors(['email' => 'Email verification is required for this action.']);
    }

    private function scopeEnabled(string $scope): bool
    {
        return match ($scope) {
            'collaborator_acceptance' => (bool) config('capture.features.require_verified_email_for_collaborator_acceptance', false),
            'sensitive_admin_operation' => (bool) config('capture.features.require_verified_email_for_sensitive_admin_operations', false),
            default => false,
        };
    }

    private function recordBlockedEvent(Request $request, string $guard, string $scope): void
    {
        if ((bool) config('capture.features.verification_block_metrics_enabled', true)) {
            app(SecurityEventMetrics::class)->incrementVerificationBlocked($guard, $scope);
        }

        if ((bool) config('capture.features.admin_audit_log_enabled', true)) {
            app(AuditLogger::class)->log(
                $request,
                'auth.verification.blocked',
                'route',
                (string) ($request->route()?->getName() ?? 'unknown'),
                null,
                [
                    'guard' => $guard,
                    'scope' => $scope,
                ]
            );
        }
    }
}
