<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Cloudflare Tunnel terminates TLS and forwards to the container with
        // X-Forwarded-* headers. Trust it so HTTPS detection, URL generation,
        // and client-IP rate limiting work correctly behind the tunnel.
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(fn (Request $request) => route('login', [
            'locale' => $request->route('locale') ?? config('locales.default', 'en'),
        ]));

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'guest' => RedirectIfAuthenticated::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
