<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PaginationRemovalTest extends TestCase
{
    public function test_no_blade_view_renders_pagination_controls(): void
    {
        foreach (File::allFiles(resource_path('views')) as $view) {
            $contents = $view->getContents();

            $this->assertStringNotContainsString('<x-pagination', $contents, $view->getRelativePathname());
            $this->assertDoesNotMatchRegularExpression('/\$[A-Za-z_][A-Za-z0-9_]*->links\s*\(/', $contents, $view->getRelativePathname());
        }
    }

    public function test_every_paginated_listing_uses_a_show_more_container(): void
    {
        $views = [
            'home.blade.php',
            'companies/index.blade.php',
            'posts/index.blade.php',
            'search.blade.php',
            'candidate/applications.blade.php',
            'recruiter/jobs/index.blade.php',
            'recruiter/applications/index.blade.php',

            'admin/users.blade.php',
        ];

        foreach ($views as $view) {
            $contents = File::get(resource_path('views/'.$view));

            $this->assertStringContainsString('data-infinite-scroll', $contents, $view);
            $this->assertStringContainsString('data-infinite-items', $contents, $view);
            $this->assertStringContainsString('data-show-more-label', $contents, $view);
        }
    }

    public function test_progressive_loading_requires_an_explicit_button_click(): void
    {
        $script = File::get(resource_path('js/infinite-scroll.js'));

        $this->assertStringNotContainsString('IntersectionObserver', $script);
        $this->assertStringContainsString('data-show-more', $script);
        $this->assertStringContainsString("addEventListener('click'", $script);
    }
}
