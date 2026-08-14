<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AccessibilityRegressionTest extends TestCase
{
    public function test_alpine_is_loaded_only_from_the_vite_bundle(): void
    {
        $views = collect(File::allFiles(resource_path('views')))
            ->map(fn ($file) => $file->getContents())
            ->implode("\n");

        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/alpinejs', $views);
    }

    public function test_password_reveal_buttons_have_accessible_names(): void
    {
        foreach (['auth/login.blade.php', 'auth/register.blade.php', 'auth/reset-password.blade.php'] as $view) {
            $contents = File::get(resource_path("views/{$view}"));
            preg_match_all('/<button(?:(?!<\/button>).)*@click="[^"]*show[^\"]*"(?:(?!<\/button>).)*>/s', $contents, $matches);

            foreach ($matches[0] as $button) {
                $this->assertStringContainsString('aria-label=', $button, "Password toggle in {$view} needs an accessible name.");
            }
        }
    }

    public function test_primary_layouts_have_a_keyboard_skip_link_and_main_target(): void
    {
        foreach (['layouts/app.blade.php', 'layouts/guest.blade.php'] as $view) {
            $contents = File::get(resource_path("views/{$view}"));
            $this->assertStringContainsString('href="#main-content"', $contents, "{$view} needs a skip link.");
            $this->assertStringContainsString('id="main-content"', $contents, "{$view} needs a main-content target.");
        }
    }

    public function test_admin_user_filters_have_programmatic_labels(): void
    {
        $contents = File::get(resource_path('views/admin/users.blade.php'));

        $this->assertStringContainsString('for="admin-user-search"', $contents);
        $this->assertStringContainsString('id="admin-user-search"', $contents);
        $this->assertStringContainsString('for="admin-role-filter"', $contents);
        $this->assertStringContainsString('id="admin-role-filter"', $contents);
    }

    public function test_confirmation_overlays_use_dialog_semantics(): void
    {
        foreach (['profile/edit.blade.php', 'recruiter/jobs/index.blade.php', 'admin/users.blade.php'] as $view) {
            $contents = File::get(resource_path("views/{$view}"));
            $this->assertStringContainsString('role="dialog"', $contents, "{$view} needs dialog semantics.");
            $this->assertStringContainsString('aria-modal="true"', $contents, "{$view} needs modal semantics.");
            $this->assertStringContainsString('@keydown.escape.window', $contents, "{$view} needs an Escape close action.");
            $this->assertStringContainsString('x-trap.noscroll="showModal"', $contents, "{$view} needs a keyboard focus trap.");
        }
    }

    public function test_alert_component_does_not_inject_a_script_for_each_instance(): void
    {
        $contents = File::get(resource_path('views/components/alert.blade.php'));

        $this->assertStringNotContainsString('<script>', $contents);
        $this->assertStringContainsString('x-init=', $contents);
    }

    public function test_alert_component_uses_compact_balanced_spacing(): void
    {
        $contents = File::get(resource_path('views/components/alert.blade.php'));

        $this->assertStringContainsString('data-alert', $contents);
        $this->assertStringContainsString('px-4 py-2.5', $contents);
        $this->assertStringContainsString('items-center gap-3', $contents);
        $this->assertStringContainsString('h-9 w-9', $contents);
        $this->assertStringNotContainsString("'rounded-lg border p-4 relative ", $contents);
    }

    public function test_add_language_button_has_no_hover_fill(): void
    {
        $contents = File::get(resource_path('views/profile/partials/structured-profile-builder.blade.php'));
        preg_match('/<button[^>]+@click="add\(\)"[^>]*>\{\{ __\(\'profile\.add_language\'\) \}\}<\/button>/', $contents, $button);

        $this->assertNotEmpty($button, 'The Add language button should remain present.');
        $this->assertStringNotContainsString('hover:bg-', $button[0]);
        $this->assertStringContainsString('focus-visible:ring-2', $button[0]);
    }

    public function test_job_card_does_not_nest_filter_links_inside_the_job_link(): void
    {
        $contents = File::get(resource_path('views/components/job-card.blade.php'));

        preg_match("/<a[^>]+localized_route\('jobs\.show'[^>]*>([\s\S]*?)<\/a>/", $contents, $jobLink);

        $this->assertNotEmpty($jobLink, 'The job detail link should remain present.');
        $this->assertStringNotContainsString('<a ', $jobLink[1], 'The job detail link must not contain another link.');
    }

    public function test_mobile_search_modal_traps_focus_and_isolates_background_content(): void
    {
        $script = File::get(resource_path('js/mobile-nav.js'));

        $this->assertStringContainsString("e.key !== 'Tab'", $script);
        $this->assertStringContainsString("setAttribute('inert', '')", $script);
        $this->assertStringContainsString("querySelectorAll('.search-suggestions')", $script);
    }

    public function test_search_results_controls_expose_their_current_state(): void
    {
        $view = File::get(resource_path('views/search.blade.php'));

        $this->assertStringContainsString('aria-current=', $view);
        $this->assertStringContainsString(':aria-expanded="showFilters.toString()"', $view);
    }

    public function test_search_suggestions_are_announced_and_urls_are_validated(): void
    {
        $script = File::get(resource_path('js/search.js'));

        $this->assertStringContainsString("setAttribute('aria-live', 'polite')", $script);
        $this->assertStringContainsString('safeUrl(', $script);
    }
}
