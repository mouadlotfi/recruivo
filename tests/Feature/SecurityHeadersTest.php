<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Pins the policy sent on HTML responses: a browser enforces whatever is here, so
 * a wildcard, inline script, or dropped font host has to be deliberate.
 */
class SecurityHeadersTest extends TestCase
{
    private function response(string $environment = 'production'): Response
    {
        $this->app['env'] = $environment;

        return (new SecurityHeaders)->handle(Request::create('/en'), fn () => response('ok'));
    }

    public function test_local_development_is_left_alone(): void
    {
        // Vite injects inline scripts/styles and needs a websocket; enforcing the
        // production policy locally would break `npm run dev` outright.
        $response = $this->response('local');

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_scripts_are_restricted_to_same_origin_files(): void
    {
        $csp = (string) $this->response()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringNotContainsString('*', $csp);
        // Only style-src may carry 'unsafe-inline' (runtime-injected <style> tags).
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertSame(1, substr_count($csp, 'unsafe-inline'));
    }

    public function test_allows_no_third_party_hosts_for_styles_or_fonts(): void
    {
        $csp = (string) $this->response()->headers->get('Content-Security-Policy');

        // The fonts are self-hosted, so the policy needs no external hosts at all.
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline';", $csp);
        $this->assertStringContainsString("font-src 'self';", $csp);
        $this->assertStringNotContainsString('googleapis', $csp);
        $this->assertStringNotContainsString('gstatic', $csp);
    }

    public function test_closes_off_framing_plugins_and_form_hijacking(): void
    {
        $csp = (string) $this->response()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_hsts_is_host_scoped(): void
    {
        $headers = $this->response()->headers;

        // No includeSubDomains/preload yet: those commit every subdomain to HTTPS
        // for a year and cannot be withdrawn quickly.
        $this->assertSame('max-age=31536000', $headers->get('Strict-Transport-Security'));
    }

    public function test_does_not_duplicate_the_headers_caddy_already_sends(): void
    {
        // The Caddyfile sets these for every response, including static files that
        // never reach PHP; setting them here as well would emit them twice.
        $headers = $this->response()->headers;

        $this->assertNull($headers->get('X-Frame-Options'));
        $this->assertNull($headers->get('X-Content-Type-Options'));
        $this->assertNull($headers->get('Referrer-Policy'));
    }
}
