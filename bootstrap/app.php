<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnforceAdminSessionSecurity;
use App\Http\Middleware\EnsureUserOrAdministrator;
use App\Http\Middleware\EnsureVerifiedGuard;
use App\Http\Middleware\ResolveAccessContext;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.any' => EnsureUserOrAdministrator::class,
            'access.context' => ResolveAccessContext::class,
            'admin.session.secure' => EnforceAdminSessionSecurity::class,
            'verified.guard' => EnsureVerifiedGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
