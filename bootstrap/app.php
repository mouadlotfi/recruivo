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
        // Cloudflare Tunnel terminates TLS and forwards to the container with
        // X-Forwarded-* headers. Only loopback and private-range peers are
        // proxies: trusting every peer ('*') lets an attacker choose the
        // X-Forwarded-For value that client IPs (and the rate limiters keyed on
        // them) are derived from.
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Reject requests whose effective host is neither the application URL
        // (and its subdomains) nor the loopback names the container healthcheck
        // uses. Without this a spoofed Host/X-Forwarded-Host flows into
        // password-reset links, turning a reset email into an account takeover.
        $middleware->trustHosts(at: ['^127\.0\.0\.1$', '^localhost$', '^\[::1\]$']);

        // Preferences are written by JavaScript (the theme toggle and the cookie
        // banner) and read by the shell, so they arrive unencrypted. Without this
        // exclusion Laravel tries to decrypt them, fails, and replaces them with
        // null - which is how the theme cookie silently stopped working.
        $middleware->encryptCookies(except: ['recruivo:theme', 'recruivo:cookie_consent']);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login', [
            'locale' => $request->route('locale') ?? config('locales.default', 'en'),
        ]));

        $middleware->web(prepend: [
            SetLocale::class,
        ], append: [
            HandleInertiaRequests::class,
        ]);

        // Appended unconditionally: the middleware itself skips local/testing,
        // because config (and therefore the environment) is not yet loaded when
        // this closure runs - the console kernel resolves middleware before it
        // bootstraps the framework.
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
