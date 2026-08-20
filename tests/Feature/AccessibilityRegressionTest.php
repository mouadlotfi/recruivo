<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AccessibilityRegressionTest extends TestCase
{
    public function test_alpine_is_not_loaded_from_a_cdn_in_any_view(): void
    {
        $views = collect(File::allFiles(resource_path('views')))
            ->map(fn ($file) => $file->getContents())
            ->implode("\n");

        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/alpinejs', $views);
    }

    public function test_mobile_search_modal_traps_focus_and_isolates_background_content(): void
    {
        $modal = File::get(resource_path('js/Components/Layout/SearchModal.vue'));

        $this->assertStringContainsString("event.key !== 'Tab'", $modal);
        $this->assertStringContainsString("window.addEventListener('focusin'", $modal);
        $this->assertStringContainsString("document.body.style.overflow = 'hidden'", $modal);
        $this->assertStringContainsString('aria-modal="true"', $modal);
    }

    public function test_search_suggestions_are_announced_and_urls_are_validated(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        $this->assertStringContainsString('aria-live="polite"', $autocomplete);
        $this->assertStringContainsString('safeUrl(', $autocomplete);
        $this->assertStringContainsString('router.visit(', $autocomplete);
        $this->assertStringNotContainsString('innerHTML', $autocomplete);
        $this->assertStringNotContainsString('v-html', $autocomplete);
        $this->assertStringNotContainsString(' as any', $autocomplete);
        $this->assertStringNotContainsString('unknown as', $autocomplete);
    }
}
