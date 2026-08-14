<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UiDefaultsTest extends TestCase
{
    public function test_all_layouts_default_to_dark_unless_light_was_explicitly_selected(): void
    {
        foreach (['app', 'guest'] as $layout) {
            $contents = File::get(resource_path("views/layouts/{$layout}.blade.php"));

            $this->assertStringContainsString("theme !== 'light'", $contents, $layout);
            $this->assertStringContainsString("partials.scroll-to-top", $contents, $layout);
        }
    }

    public function test_scroll_to_top_behavior_is_loaded_site_wide(): void
    {
        $entrypoint = File::get(resource_path('js/app.js'));

        $this->assertStringContainsString("import './scroll-to-top';", $entrypoint);
        $this->assertFileExists(resource_path('js/scroll-to-top.js'));
        $this->assertFileExists(resource_path('views/partials/scroll-to-top.blade.php'));
    }
}
