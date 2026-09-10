<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The legal documents are public pages in both locales, and social crawlers see
 * only what the shell renders server-side.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string, 1: string, 2: string}> url, locale, expected title
     */
    private const DOCUMENTS = [
        ['/en/privacy', 'en', 'Privacy Policy'],
        ['/fr/privacy', 'fr', 'Politique de confidentialité'],
        ['/en/terms', 'en', 'Terms of Service'],
        ['/fr/terms', 'fr', "Conditions d'utilisation"],
    ];

    public function test_both_documents_render_in_both_locales_with_shareable_metadata(): void
    {
        foreach (self::DOCUMENTS as [$url, $locale, $expectedTitle]) {
            $response = $this->get($url)->assertOk();

            $response->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Legal/Show', false)
                ->where('document.title', $expectedTitle)
                ->has('document.sections')
                ->where('labels.back_home', fn ($value) => is_string($value) && $value !== '')
                ->where('contact_email', fn ($value) => str_contains((string) $value, '@')));

            // Crawlers do not run JavaScript, so the shell has to carry this.
            $html = $response->getContent();
            $this->assertStringContainsString(
                '<meta property="og:title" content="'.e($expectedTitle).' — Recruivo">',
                $html,
                "{$url} must expose its own title to crawlers"
            );
            $this->assertMatchesRegularExpression(
                '/<link rel="canonical" href="https?:\/\/[^"]+\/'.$locale.'\/(privacy|terms)">/',
                $html,
                "{$url} must declare a canonical URL"
            );
        }
    }

    public function test_documents_are_complete_and_numbered_in_order(): void
    {
        foreach (self::DOCUMENTS as [$url]) {
            $sections = $this->get($url)
                ->assertOk()
                ->viewData('page')['props']['document']['sections'];

            $this->assertGreaterThan(8, count($sections), "{$url} needs at least nine sections.");

            foreach ($sections as $section) {
                $this->assertNotEmpty($section['heading'], "{$url} has an empty heading.");
                $this->assertNotEmpty($section['body'], "{$url} has an empty body.");
            }

            $numbers = array_map(fn (array $section) => (int) strtok($section['heading'], '.'), $sections);
            $this->assertSame(range(1, count($sections)), $numbers, "Section numbering in {$url} must be sequential.");
        }
    }
}
