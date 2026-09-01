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

    public function test_filter_only_search_returns_matching_jobs_without_a_text_query(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Labs']);
        Job::factory()->for($company)->create([
            'title' => 'Remote Engineer',
            'remote_type' => 'remote',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);
        Job::factory()->for($company)->create([
            'title' => 'Onsite Designer',
            'remote_type' => 'onsite',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->get('/en/search?remote_type=remote')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Search/Index', false)
                ->where('jobsCount', 1)
                ->where('companiesCount', 0)
                ->where('jobs.0.title', 'Remote Engineer')
            );
    }

    public function test_suggested_correction_only_appears_when_the_whole_search_is_empty(): void
    {
        $company = Company::factory()->create(['name' => 'Acme Labs']);
        Job::factory()->for($company)->create([
            'title' => 'Laravel Developer',
            'remote_type' => 'onsite',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        // The typo-tolerant query matches the title, but the remote_type
        // filter excludes that row — so the search is empty and the
        // vocabulary scan offers a correction.
        $this->get('/en/search?search=larvel&remote_type=remote')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Search/Index', false)
                ->where('jobsCount', 0)
                ->where('suggestedCorrection', 'laravel')
            );
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
        $autocompletePath = resource_path('js/Components/Search/Autocomplete.vue');
        $this->assertFileExists($autocompletePath);
        $autocomplete = File::get($autocompletePath);

        $this->assertStringContainsString('role="combobox"', $autocomplete);
        $this->assertStringContainsString('aria-autocomplete="list"', $autocomplete);
        $this->assertStringContainsString('aria-expanded', $autocomplete);
        $this->assertStringContainsString('aria-controls', $autocomplete);
        $this->assertStringContainsString('aria-activedescendant', $autocomplete);
        $this->assertStringContainsString('role="listbox"', $autocomplete);
        $this->assertStringContainsString('aria-live="polite"', $autocomplete);
    }

    public function test_search_suggestions_match_the_width_of_their_search_container(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        $this->assertStringContainsString('absolute inset-x-0 top-full', $autocomplete);
        $this->assertStringContainsString('w-full overflow-y-auto', $autocomplete);
    }

    public function test_vue_search_surfaces_share_the_autocomplete_component(): void
    {
        $modal = File::get(resource_path('js/Components/Layout/SearchModal.vue'));
        $page = File::get(resource_path('js/Pages/Search/Index.vue'));

        $this->assertStringContainsString("import SearchAutocomplete from '../Search/Autocomplete.vue'", $modal);
        $this->assertStringContainsString('<SearchAutocomplete', $modal);
        $this->assertStringContainsString("import SearchAutocomplete from '../../Components/Search/Autocomplete.vue'", $page);
        $this->assertStringContainsString('<SearchAutocomplete', $page);
        $this->assertStringNotContainsString('autocomplete integration from resources/js/search.js is a separate follow-up', $page);
    }

    public function test_search_autocomplete_drops_the_navbar_special_case_and_keeps_the_page_width_contract(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        $this->assertStringNotContainsString('isNavbarSearch', $autocomplete);
        $this->assertStringNotContainsString("dataset.searchSurface === 'navbar'", $autocomplete);
        $this->assertStringNotContainsString('left-1/2', $autocomplete);
        $this->assertStringNotContainsString('w-[min(44rem', $autocomplete);

        $this->assertStringContainsString('absolute inset-x-0 top-full', $autocomplete);
        $this->assertStringContainsString('w-full overflow-y-auto', $autocomplete);
    }

    public function test_recent_searches_have_individual_remove_actions(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        $this->assertStringContainsString('data-remove-recent-search', $autocomplete);
        $this->assertStringContainsString('removeRecentSearch(', $autocomplete);
        $this->assertStringContainsString('recruivo:recent-searches', $autocomplete);
        $this->assertStringContainsString('slice(0, 5)', $autocomplete);
        $this->assertStringContainsString("'remove_recent_search'", file_get_contents(resource_path('lang/en/common.php')));
        $this->assertStringContainsString("'remove_recent_search'", file_get_contents(resource_path('lang/fr/common.php')));
    }

    public function test_clear_control_remains_an_unboxed_icon_at_rest_and_on_hover(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        foreach ([$autocomplete] as $markup) {
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
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        $this->assertStringNotContainsString('crypto.randomUUID()', $autocomplete);
        $this->assertStringContainsString('nextSearchId(', $autocomplete);
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
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        $this->assertStringContainsString('data-search-actions', $autocomplete);
        $this->assertStringContainsString('absolute inset-y-0 right-2', $autocomplete);
        $this->assertStringContainsString('gap-1', $autocomplete);
        $this->assertStringContainsString('pr-36', $autocomplete);
        $this->assertStringNotContainsString('data-search-clear class="absolute', $autocomplete);
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

    public function test_search_dual_lists_use_scoped_partial_reloads(): void
    {
        $searchPage = File::get(resource_path('js/Pages/Search/Index.vue'));

        $this->assertStringContainsString("only: ['jobs', 'jobsPagination']", $searchPage);
        $this->assertStringContainsString("only: ['companies', 'companiesPagination']", $searchPage);
        $this->assertStringNotContainsString('hasCompaniesPageParam', $searchPage);
        $this->assertStringNotContainsString('hasJobsPageParam', $searchPage);
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

    public function test_search_autocomplete_labels_are_wired_from_laravel_translations(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));
        $modal = File::get(resource_path('js/Components/Layout/SearchModal.vue'));
        $middleware = File::get(app_path('Http/Middleware/HandleInertiaRequests.php'));

        // No hardcoded English fallback strings survive in the component.
        foreach (['Search all results', 'Recent searches', 'Remove recent search', 'No matching suggestions', 'Search jobs, companies'] as $english) {
            $this->assertStringNotContainsString($english, $autocomplete);
        }

        // Autocomplete label keys match the backend's SEARCH_PAGE_LABEL_KEYS
        // entries (search_all_results, search_error, ...) plus the shell keys.
        $this->assertStringContainsString('search_all_results?: string', $autocomplete);
        $this->assertStringContainsString('search_error?: string', $autocomplete);
        $this->assertStringContainsString('suggestions_available?: string', $autocomplete);
        $this->assertStringContainsString("label('search_all_results')", $autocomplete);
        $this->assertStringContainsString("label('search_error')", $autocomplete);
        $this->assertStringContainsString("label('suggestions_available')", $autocomplete);

        // The modal passes every autocomplete label through t() from the
        // shared shell translations — nothing falls back to English.
        foreach ([
            'search', 'search_placeholder', 'clear_search', 'search_all_results',
            'recent_searches', 'remove_recent_search', 'no_search_suggestions',
            'search_error', 'suggestions_available', 'loading',
        ] as $key) {
            $this->assertStringContainsString("$key: t('$key')", $modal, "Modal must pass the $key label through t().");
        }

        // The shell translation sources carry every key the autocomplete needs.
        foreach ([
            'search_all_results', 'no_search_suggestions', 'recent_searches',
            'remove_recent_search', 'search_error', 'suggestions_available', 'loading',
        ] as $key) {
            $this->assertStringContainsString("'$key' => 'common.$key'", $middleware);
        }
    }

    public function test_autocomplete_labels_are_available_in_both_locales(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $lang = file_get_contents(resource_path("lang/$locale/common.php"));
            $this->assertStringContainsString("'suggestions_available'", $lang);
            $this->assertStringContainsString("'loading'", $lang);
            $this->assertStringContainsString("'recent_searches'", $lang);
            $this->assertStringContainsString("'remove_recent_search'", $lang);
            $this->assertStringContainsString("'search_all_results'", $lang);
            $this->assertStringContainsString("'search_error'", $lang);
            $this->assertStringContainsString("'no_search_suggestions'", $lang);
        }
    }

    public function test_search_autocomplete_reports_loading_and_error_states(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        // Typed loading and error state.
        $this->assertStringContainsString('const isLoading = ref', $autocomplete);
        $this->assertStringContainsString('const errorMessage = ref', $autocomplete);
        $this->assertStringContainsString('isLoading.value = true', $autocomplete);
        $this->assertStringContainsString('errorMessage.value = label(\'search_error\')', $autocomplete);

        // Both states surface localized text in the listbox.
        $this->assertStringContainsString('v-if="isLoading"', $autocomplete);
        $this->assertStringContainsString('v-else-if="errorMessage"', $autocomplete);
        $this->assertStringContainsString("label('loading')", $autocomplete);

        // Aborts never surface as an error; only the newest request clears
        // the loading flag.
        $this->assertStringContainsString("error.name === 'AbortError'", $autocomplete);
        $this->assertStringContainsString('if (controller === current) isLoading.value = false', $autocomplete);
    }

    public function test_search_modal_focuses_the_autocomplete_input_on_open(): void
    {
        $modal = File::get(resource_path('js/Components/Layout/SearchModal.vue'));

        // focusInput targets the first input inside the dialog (the shared
        // autocomplete field), falling back to the generic focusable set.
        $this->assertStringContainsString("querySelector<HTMLElement>('input:not([disabled])')", $modal);
        $this->assertStringContainsString('firstInput() ?? focusableElements()[0] ?? dialog.value', $modal);
    }

    public function test_search_submit_controls_reach_the_parent_submit_handler(): void
    {
        $autocomplete = File::get(resource_path('js/Components/Search/Autocomplete.vue'));

        // The Search button submits through a native form (its own click used
        // to be a no-op) and the search-all / recent-row actions emit submit.
        $this->assertStringContainsString('<form class="relative w-full" @submit.prevent="submitCurrent">', $autocomplete);
        $this->assertStringContainsString('type="submit"', $autocomplete);
        $this->assertStringContainsString('@click="submitCurrent"', $autocomplete);
        $this->assertStringContainsString('@click="chooseRecent(unescapeHtml(term))"', $autocomplete);
        $this->assertStringContainsString("emit('submit')", $autocomplete);
        $this->assertStringContainsString('event.preventDefault()', $autocomplete);
    }
}
