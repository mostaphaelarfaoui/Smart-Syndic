<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ─── Global Middleware ────────────────────────────────────────
        $middleware->append(\App\Http\Middleware\SanitizeInput::class);
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // ─── API Middleware Group ─────────────────────────────────────
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // ─── Named/Aliased Middleware ─────────────────────────────────
        $middleware->alias([
            'role'           => \App\Http\Middleware\EnsureUserRole::class,
            'tenant.scope'   => \App\Http\Middleware\EnforceTenantScope::class,
            'tenant.active'  => \App\Http\Middleware\EnsureTenantActive::class,
            'throttle.auth'  => \App\Http\Middleware\ThrottleAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // ─── JSON-only API Error Responses ────────────────────────────
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })
    ->create();
