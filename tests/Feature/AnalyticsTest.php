<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Umami runs on our own infrastructure and its script is proxied under this
 * origin, so the browser never talks to a third party and the content security
 * policy keeps script-src/connect-src at 'self'. Tracking is off unless a website
 * ID is configured, which local development leaves empty.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_tracker_is_absent_unless_configured(): void
    {
        config(['services.umami.website_id' => null]);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-website-id', $html);
        $this->assertStringNotContainsString('/u/script.js', $html);
    }

    public function test_the_tracker_is_served_from_our_own_origin(): void
    {
        config([
            'services.umami.website_id' => '11111111-2222-3333-4444-555555555555',
            'services.umami.domains' => null,
        ]);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<script defer src="/u/script.js" data-website-id="11111111-2222-3333-4444-555555555555"></script>',
            $html
        );
        $this->assertStringNotContainsString('data-domains', $html);
    }

    public function test_the_tracker_emits_the_configured_domain_scope(): void
    {
        // Per-environment because prod and the demo stack share one image but
        // report to different websites. An unset value must not scope at all.
        config([
            'services.umami.website_id' => '11111111-2222-3333-4444-555555555555',
            'services.umami.domains' => 'recruivo.work',
        ]);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString('data-domains="recruivo.work"', $html);
    }

    public function test_the_tracker_url_stays_on_our_origin(): void
    {
        // An absolute URL here would reintroduce a third-party request, break
        // script-src 'self', and fail the no-third-party assertions in
        // CookieConsentTest - so the constraint is pinned rather than assumed.
        $this->assertStringStartsWith('/', (string) config('services.umami.script_url'));
    }
}
