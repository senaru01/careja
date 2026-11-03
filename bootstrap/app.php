<?php

use App\Http\Middleware\EnsurePaymentDemoKey;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsApproved;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Honour X-Forwarded-* headers from tunnels / reverse proxies so URLs
        // (CSS, JS, links) use the public host and https.
        $middleware->trustProxies(at: '*');

        // Midtrans calls this from its servers; requests are verified by signature instead.
        $middleware->validateCsrfTokens(except: ['midtrans/notification']);

        $middleware->web(append: [
            EnsureUserIsApproved::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'payment-demo' => EnsurePaymentDemoKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
