<?php

namespace App\Http\Middleware;

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
}
