<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends the browser-facing security policy for HTML responses.
 *
 * Registered for the `web` group in bootstrap/app.php for every environment
 * except `local`/`testing`: the policy has to be strict in production, while the
 * Vite dev server injects inline scripts/styles and connects over a websocket
 * that a strict policy would block.
 *
 * Static files (assets, /storage/*) never pass through PHP; the Caddyfile sets
 * the framing/content-type/referrer headers at the edge for those.
 */
class SecurityHeaders
{
    /**
     * Content policy for the Inertia shell.
     *
     * - scripts are same-origin files only (the Vite bundle); Inertia ships page
     *   data as a non-executable `<script type="application/json">` block, which
     *   browsers do not subject to script-src
     * - `style-src` also needs `'unsafe-inline'`: the bundle injects <style>
     *   elements at runtime (verified in a browser - 4+ distinct blocks per page,
     *   hashes change on every build and the injected elements cannot carry a
     *   nonce). Inline *style* is not a script-execution vector; script-src stays
     *   strict
     * - the Google Fonts hosts are what resources/css/app.css imports
     * - images allow data URIs (inline SVG) and https (remote portraits)
     */
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

    /**
     * One year, host-only: no `includeSubDomains`/`preload` yet, because those
     * commit every subdomain of the domain to HTTPS for the lifetime of the
     * policy and cannot be withdrawn quickly. See docs/docker.md.
     */
    private const STRICT_TRANSPORT_SECURITY = 'max-age=31536000';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Development is exempt: the Vite dev server injects inline scripts and
        // styles and talks to the browser over a websocket, all of which the
        // production policy deliberately blocks. The check is runtime (not at
        // middleware registration) because the environment is not yet loaded when
        // bootstrap/app.php builds the middleware stack.
        if (app()->environment(['local', 'testing'])) {
            return $response;
        }

        $response->headers->set('Content-Security-Policy', self::CONTENT_SECURITY_POLICY);

        // Browsers ignore HSTS outside HTTPS, so it is sent unconditionally.
        // X-Frame-Options / X-Content-Type-Options / Referrer-Policy /
        // Permissions-Policy are set by the Caddyfile, which also covers static
        // files that never reach PHP - setting them here too duplicates them.
        $response->headers->set('Strict-Transport-Security', self::STRICT_TRANSPORT_SECURITY);

        return $response;
    }
}
