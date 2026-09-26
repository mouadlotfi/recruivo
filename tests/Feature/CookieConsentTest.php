<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The webfonts are self-hosted and the only cookies are the ones the site needs,
 * so no visitor request leaves the origin and nothing on the page waits for a
 * consent decision. The banner is a notice.
 */
class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    private const COOKIE = 'recruivo:cookie_consent';

    public function test_the_shell_makes_no_third_party_requests(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('fonts.gstatic.com', $html);
        $this->assertStringNotContainsString('rel="preconnect" href="http://', $html);
    }

    public function test_no_source_file_loads_fonts_from_a_third_party(): void
    {
        $offenders = [];

        foreach (['resources/js', 'resources/views', 'resources/css', 'resources/lang'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir)));
            foreach ($files as $file) {
                if ($file->isFile() && preg_match('/fonts\.(googleapis|gstatic)\.com/', file_get_contents($file->getPathname()))) {
                    $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        $this->assertSame([], $offenders, 'Webfonts must stay self-hosted.');
    }

    public function test_self_hosted_fonts_cover_the_weights_the_markup_uses(): void
    {
        $css = file_get_contents(resource_path('css/fonts.css'));
        $app = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString("@import './fonts.css';", $app, 'The font declarations must reach the bundle.');

        // One variable file per family, declared over the weight range it ships.
        preg_match_all(
            "/font-family: '([^']+)';\n    font-style: normal;\n    font-weight: (\d+) (\d+);/",
            $css,
            $faces,
            PREG_SET_ORDER
        );
        $ranges = array_column($faces, 0);
        $this->assertCount(2, $faces, 'Both families must be declared as variable faces.');
        $this->assertSame(['Plus Jakarta Sans', 'Space Grotesk'], [$faces[0][1], $faces[1][1]]);

        // Weights the markup requests: 400-800 body, 500-700 display.
        $used = ['Plus Jakarta Sans' => [400, 500, 600, 700, 800], 'Space Grotesk' => [500, 600, 700]];
        foreach ($faces as [, $family, $min, $max]) {
            foreach ($used[$family] as $weight) {
                $this->assertGreaterThanOrEqual((int) $min, $weight, "{$family} does not cover {$weight}.");
                $this->assertLessThanOrEqual((int) $max, $weight, "{$family} does not cover {$weight}.");
            }
        }
        $this->assertNotEmpty($ranges);

        preg_match_all("#url\('/fonts/([^']+)'\)#", $css, $matches);
        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $file) {
            // Served by the web server straight from public/, so the same URL
            // works in dev (Vite is not involved) and in production.
            $this->assertFileExists(public_path('fonts/'.$file), "Missing font file: {$file}");
        }
    }

    public function test_the_theme_cookie_reaches_the_shell(): void
    {
        // JavaScript writes this cookie unencrypted, so it must be excluded from
        // cookie decryption or Laravel replaces it with null.
        $html = $this->withUnencryptedCookie('recruivo:theme', 'light')->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString('class="light"', $html);
    }
}
