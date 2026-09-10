<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Google Fonts stylesheet discloses the visitor's IP address to Google, so the
 * shell renders it only for visitors who accepted the banner.
 */
class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    private const COOKIE = 'recruivo:cookie_consent';

    private const FONT_LINK = 'rel="stylesheet" href="https://fonts.googleapis.com/css2';

    public function test_fonts_are_not_requested_without_a_choice(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringNotContainsString(self::FONT_LINK, $html);
        $this->assertStringNotContainsString('rel="preconnect" href="https://fonts.gstatic.com"', $html);
        // …but the banner needs the URL to load them once accepted.
        $this->assertStringContainsString('<meta name="font-stylesheet" content="https://fonts.googleapis.com/css2', $html);
    }

    public function test_accepting_loads_the_fonts(): void
    {
        $html = $this->withUnencryptedCookie(self::COOKIE, 'accepted')->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString(self::FONT_LINK, $html);
    }

    public function test_necessary_only_is_honoured(): void
    {
        $html = $this->withUnencryptedCookie(self::COOKIE, 'necessary')->get('/en')->assertOk()->getContent();

        $this->assertStringNotContainsString(self::FONT_LINK, $html);
    }

    public function test_the_theme_cookie_reaches_the_shell(): void
    {
        // JavaScript writes both cookies unencrypted, so they must be excluded from
        // cookie decryption or Laravel replaces them with null.
        $html = $this->withUnencryptedCookie('recruivo:theme', 'light')->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString('class="light"', $html);
    }
}
