<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SmartSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_normalizes_whitespace_and_ranks_exact_title_matches_first(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Labs']);
        Job::factory()->for($company)->create([
            'title' => 'Senior Laravel Developer',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);
        Job::factory()->for($company)->create([
            'title' => 'Product Designer',
            'description' => 'Works with a senior Laravel developer.',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $response = $this->get('/en/search?search=%20%20senior%20%20laravel%20%20developer%20');

        $response->assertOk();
        $response->assertSeeInOrder(['Senior Laravel Developer', 'Product Designer']);
    }

    public function test_search_tolerates_a_small_typo(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Labs']);
        Job::factory()->for($company)->create([
            'title' => 'Laravel Developer',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->get('/en/search?search=larvel')
            ->assertOk()
            ->assertSee('Laravel Developer');
    }

    public function test_suggestions_expose_accessible_sections_and_a_search_all_action(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Labs']);
        Job::factory()->for($company)->create([
            'title' => 'Laravel Developer',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->getJson('/api/search/suggestions?q=larvel')
            ->assertOk()
            ->assertJsonPath('query', 'larvel')
            ->assertJsonStructure([
                'query',
                'sections' => [['type', 'label', 'items']],
                'search_url',
            ]);
    }

    public function test_search_markup_uses_the_combobox_and_listbox_contract(): void
    {
        $header = file_get_contents(resource_path('views/partials/header.blade.php'));

        $this->assertStringContainsString('role="combobox"', $header);
        $this->assertStringContainsString('aria-autocomplete="list"', $header);
        $this->assertStringContainsString('data-search-clear', $header);
        $this->assertStringContainsString("setAttribute('role', 'listbox')", file_get_contents(resource_path('js/search.js')));
    }

    public function test_search_suggestions_match_the_width_of_their_search_container(): void
    {
        $script = file_get_contents(resource_path('js/search.js'));

        $this->assertStringContainsString('absolute inset-x-0 top-full', $script);
        $this->assertStringContainsString('w-full overflow-y-auto', $script);
    }

    public function test_navbar_search_uses_a_single_always_visible_search_icon_without_an_inline_bar(): void
    {
        $header = file_get_contents(resource_path('views/partials/header.blade.php'));
        $view = file_get_contents(resource_path('views/search.blade.php'));

        // The inline navbar search bar is gone.
        $this->assertStringNotContainsString('id="nav-search"', $header);
        $this->assertStringNotContainsString('data-search-surface="navbar"', $header);

        // Exactly one search entry point in the navbar: the icon toggle, visible at every viewport and role.
        $this->assertSame(1, substr_count($header, 'id="mobile-search-toggle"'));
        $this->assertStringContainsString('id="mobile-search-toggle"', $header);
        $toggle = substr($header, strpos($header, 'id="mobile-search-toggle"'), 2000);
        $this->assertStringNotContainsString('sm:hidden', $toggle, 'The search icon must not be hidden on desktop.');
        $this->assertStringContainsString('aria-label', $toggle);

        // The modal is the single navbar search surface; the page search surface remains.
        $this->assertStringContainsString('data-search-surface="mobile"', $header);
        $this->assertStringContainsString('id="mobile-search-modal"', $header);
        $this->assertStringContainsString('data-search-surface="page"', $view);
        $this->assertStringContainsString('role="combobox"', $header);
    }

    public function test_search_autocomplete_drops_the_navbar_special_case_and_keeps_the_page_width_contract(): void
    {
        $script = file_get_contents(resource_path('js/search.js'));

        // No dead navbar branch remains.
        $this->assertStringNotContainsString('isNavbarSearch', $script);
        $this->assertStringNotContainsString("dataset.searchSurface === 'navbar'", $script);
        $this->assertStringNotContainsString('left-1/2', $script);
        $this->assertStringNotContainsString('w-[min(44rem', $script);

        // The page surface keeps its full-width listbox.
        $this->assertStringContainsString('absolute inset-x-0 top-full', $script);
        $this->assertStringContainsString('w-full overflow-y-auto', $script);
    }

    public function test_recent_searches_have_individual_remove_actions(): void
    {
        $script = file_get_contents(resource_path('js/search.js'));
        $header = file_get_contents(resource_path('views/partials/header.blade.php'));
        $view = file_get_contents(resource_path('views/search.blade.php'));

        $this->assertStringContainsString('data-remove-recent-search', $script);
        $this->assertStringContainsString('removeRecentSearch(', $script);
        $this->assertStringContainsString('data-search-remove-recent=', $header);
        $this->assertStringContainsString('data-search-remove-recent=', $view);
        $this->assertStringContainsString("'remove_recent_search'", file_get_contents(resource_path('lang/en/common.php')));
        $this->assertStringContainsString("'remove_recent_search'", file_get_contents(resource_path('lang/fr/common.php')));
    }

    public function test_clear_control_remains_an_unboxed_icon_at_rest_and_on_hover(): void
    {
        $view = file_get_contents(resource_path('views/search.blade.php'));
        $header = file_get_contents(resource_path('views/partials/header.blade.php'));

        foreach ([$view, $header] as $markup) {
            $this->assertDoesNotMatchRegularExpression(
                '/data-search-clear[^>]*class="[^"]*(?:hover:|dark:hover:)?bg-/',
                $markup,
                'The clear control must not gain a filled background at rest or on hover.'
            );
            $this->assertDoesNotMatchRegularExpression(
                '/data-search-clear[^>]*class="[^"]*(?:hover:|dark:hover:)?border-(?!transparent)/',
                $markup,
                'The clear control must not gain a visible border at rest or on hover.'
            );
            $this->assertStringContainsString('focus-visible:ring-2', $markup);
        }
    }

    public function test_search_autocomplete_works_on_lan_http_origins_without_secure_context_apis(): void
    {
        $script = file_get_contents(resource_path('js/search.js'));

        $this->assertStringNotContainsString('crypto.randomUUID()', $script);
        $this->assertStringContainsString('nextSearchId(', $script);
    }

    public function test_search_page_shows_result_counts_on_the_type_tabs(): void
    {
        $company = Company::factory()->create(['name' => 'Cloudy Corp']);
        Job::factory()->for($company)->create([
            'title' => 'Cloud Engineer',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $response = $this->get('/en/search?search=cloud');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Search/Index', false)
                ->where('totalCount', 2)
                ->where('jobsCount', 1)
                ->where('companiesCount', 1)
            );

        $searchPage = File::get(resource_path('js/Pages/Search/Index.vue'));
        $this->assertStringContainsString('data-search-tab', $searchPage);
        $this->assertStringContainsString('tab.count', $searchPage);
    }

    public function test_search_page_clear_and_submit_actions_share_a_non_overlapping_control_group(): void
    {
        $view = file_get_contents(resource_path('views/search.blade.php'));

        $this->assertStringContainsString('data-search-actions', $view);
        $this->assertStringContainsString('absolute inset-y-0 right-2', $view);
        $this->assertStringContainsString('gap-1', $view);
        $this->assertStringContainsString('pr-36', $view);
        $this->assertStringNotContainsString('data-search-clear class="absolute', $view);
    }

    public function test_search_page_surfaces_active_filters_as_removable_chips(): void
    {
        $response = $this->get('/en/search?search=cloud&remote_type=remote&location=Dublin');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Search/Index', false)
                ->where('remoteType', 'remote')
                ->where('location', 'Dublin')
            );

        $searchPage = File::get(resource_path('js/Pages/Search/Index.vue'));
        $this->assertStringContainsString('data-active-filter-chip', $searchPage);
        $this->assertStringContainsString('remove_filter', $searchPage);
    }

    public function test_search_page_empty_state_offers_popular_suggestions(): void
    {
        Job::factory()->for(Company::factory())->create([
            'title' => 'DevOps Specialist',
            'category' => 'DevOps',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $response = $this->get('/en/search')->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Search/Index', false)
            ->where('popularSearches.0', 'DevOps')
        );

        $searchPage = File::get(resource_path('js/Pages/Search/Index.vue'));
        $this->assertStringContainsString('data-popular-search', $searchPage);
    }
}
