<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
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
        // Cloudflare Tunnel forwards X-Forwarded-* headers. Only loopback and
        // private-range peers count as proxies: trusting every peer ('*') lets an
        // attacker pick the client IP that rate limiters key on.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Reject hosts that are neither the app URL (and its subdomains) nor the
        // loopback names the healthcheck uses: a spoofed Host reaches reset links.
        $middleware->trustHosts(at: ['^127\.0\.0\.1$', '^localhost$', '^\[::1\]$']);

        // Written by JavaScript, so they arrive unencrypted: without this Laravel
        // tries to decrypt them and replaces them with null.
        $middleware->encryptCookies(except: ['recruivo:theme', 'recruivo:cookie_consent']);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login', [
            'locale' => $request->route('locale') ?? config('locales.default', 'en'),
        ]));

        $middleware->web(prepend: [
            SetLocale::class,
        ], append: [
            HandleInertiaRequests::class,
        ]);

        // Appended unconditionally; the middleware itself skips local/testing.
        $middleware->web(append: [SecurityHeaders::class]);

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
