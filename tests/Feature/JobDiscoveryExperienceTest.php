<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JobDiscoveryExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_page_has_no_duplicate_search_panel_but_still_accepts_location_filters(): void
    {
        $company = Company::factory()->create();
        Job::factory()->for($company)->create([
            'title' => 'Dublin Role',
            'location' => 'Dublin, Ireland',
            'status' => JobStatus::Published->value,
        ]);
        Job::factory()->for($company)->create([
            'title' => 'Toronto Role',
            'location' => 'Toronto, Canada',
            'status' => JobStatus::Published->value,
        ]);
        Job::factory()->for($company)->create([
            'title' => 'Draft Location Role',
            'location' => 'Hidden City',
            'status' => JobStatus::Draft->value,
        ]);

        $response = $this->get('/en/jobs?location=Dublin%2C%20Ireland');

        $response->assertOk()
            ->assertDontSee('data-job-location-filter', false)
            ->assertSee(__('jobs.find_opportunity'))
            ->assertSee(__('jobs.discover_jobs'))
            ->assertDontSee(__('jobs.filter_jobs'))
            ->assertDontSee('Hidden City')
            ->assertSee('Dublin Role')
            ->assertDontSee('Toronto Role');
    }

    public function test_recruiters_see_recruiter_focused_jobs_page_copy(): void
    {
        Role::findOrCreate('Recruiter', 'web');
        $recruiter = User::factory()->create(['is_recruiter' => true]);
        $recruiter->assignRole('Recruiter');

        $response = $this->actingAs($recruiter)
            ->get('/en/jobs')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Jobs/Index', false)
            ->where('labels.recruiter_explore_title', __('jobs.recruiter_explore_title'))
            ->where('labels.recruiter_explore_subtitle', __('jobs.recruiter_explore_subtitle'))
        );

        $jobsPage = File::get(resource_path('js/Pages/Jobs/Index.vue'));
        $this->assertStringContainsString('isRecruiter', $jobsPage);
        $this->assertStringContainsString('props.labels.recruiter_explore_title', $jobsPage);
        $this->assertStringContainsString('props.labels.find_opportunity', $jobsPage);
    }

    public function test_job_locations_link_to_location_filtered_search(): void
    {
        $company = Company::factory()->create();
        $job = Job::factory()->for($company)->create([
            'location' => 'Dublin, Ireland',
            'status' => JobStatus::Published->value,
        ]);

        $this->get('/en/jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Jobs/Index', false)
                ->where('jobs.0.location', 'Dublin, Ireland')
            );

        $this->get("/en/jobs/{$job->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Jobs/Show', false)
                ->where('job.location', 'Dublin, Ireland')
            );

        $this->get('/en/search?location=Dublin%2C%20Ireland&filter=jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Search/Index', false)
                ->where('location', 'Dublin, Ireland')
                ->where('locations.0', 'Dublin, Ireland')
            );

        $jobCard = File::get(resource_path('js/Components/Jobs/JobCard.vue'));
        $jobShow = File::get(resource_path('js/Pages/Jobs/Show.vue'));
        $searchPage = File::get(resource_path('js/Pages/Search/Index.vue'));
        $this->assertStringContainsString('data-job-location-link', $jobCard);
        $this->assertStringContainsString("filter: 'jobs'", $jobCard);
        $this->assertStringContainsString('data-job-location-link', $jobShow);
        $this->assertStringContainsString('data-search-tab', $searchPage);
    }

    public function test_search_filter_uses_the_existing_location_dropdown(): void
    {
        Job::factory()->create([
            'location' => 'Dublin, Ireland',
            'status' => JobStatus::Published->value,
        ]);

        $response = $this->get('/en/search?search=engineer')->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Search/Index', false)
            ->where('locations.0', 'Dublin, Ireland')
        );

        $searchPage = File::get(resource_path('js/Pages/Search/Index.vue'));
        $this->assertStringContainsString('data-search-location-filter', $searchPage);
    }
}
