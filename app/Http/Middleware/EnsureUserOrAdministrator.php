<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserOrAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('capture.features.enforce_access_context', false)) {
            return $next($request);
        }

        if (Auth::guard('web')->check() || Auth::guard('admin')->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(401);
        }

        return redirect('/login');
    }
}
