<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the browser-facing security policy for HTML responses. Skipped in local
 * and testing, where the Vite dev server needs inline assets and a websocket;
 * docs/docker.md explains what each directive is for.
 */
class SecurityHeaders
{
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; "
        ."base-uri 'self'; "
        ."form-action 'self'; "
        ."frame-ancestors 'self'; "
        ."object-src 'none'; "
        ."script-src 'self'; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        ."font-src 'self' https://fonts.gstatic.com; "
        ."img-src 'self' data: https:; "
        ."connect-src 'self'";

    /** Host-only for now; docs/docker.md covers the includeSubDomains/preload ramp. */
    private const STRICT_TRANSPORT_SECURITY = 'max-age=31536000';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Runtime check rather than a registration-time one: the environment is
        // not loaded yet when bootstrap/app.php builds the middleware stack.
        if (app()->environment(['local', 'testing'])) {
            return $response;
        }

        // The framing/referrer/content-type headers are the Caddyfile's: it covers
        // static files too, and setting them here as well duplicated each one.
        $response->headers->set('Content-Security-Policy', self::CONTENT_SECURITY_POLICY);
        $response->headers->set('Strict-Transport-Security', self::STRICT_TRANSPORT_SECURITY);

        return $response;
    }
}
