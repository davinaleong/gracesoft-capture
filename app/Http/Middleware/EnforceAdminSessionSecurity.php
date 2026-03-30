<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceAdminSessionSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('capture.features.harden_admin_sessions', true)) {
            return $next($request);
        }

        if (! Auth::guard('admin')->check()) {
            return $next($request);
        }

        $timeoutMinutes = (int) config('capture.features.admin_session_idle_timeout_minutes', 20);
        $lastActivityAt = (int) $request->session()->get('admin.last_activity_at', 0);

        if ($timeoutMinutes > 0 && $lastActivityAt > 0) {
            $idleSeconds = now()->timestamp - $lastActivityAt;

            if ($idleSeconds > ($timeoutMinutes * 60)) {
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                abort(403, 'Administrator session expired due to inactivity.');
            }
        }

        $request->session()->put('admin.last_activity_at', now()->timestamp);

        return $next($request);
    }
}
