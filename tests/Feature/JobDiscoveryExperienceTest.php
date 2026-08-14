<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($recruiter)
            ->get('/en/jobs')
            ->assertOk()
            ->assertSee(__('jobs.recruiter_explore_title'))
            ->assertSee(__('jobs.recruiter_explore_subtitle'))
            ->assertDontSee(__('jobs.find_opportunity'))
            ->assertDontSee('data-job-location-filter', false);
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
            ->assertSee('data-job-location-link', false)
            ->assertSee('/en/search?location=Dublin%2C%20Ireland&amp;filter=jobs', false)
            ->assertDontSee('/en/jobs?location=Dublin%2C%20Ireland', false);

        $this->get("/en/jobs/{$job->id}")
            ->assertOk()
            ->assertSee('data-job-location-link', false)
            ->assertSee('/en/search?location=Dublin%2C%20Ireland&amp;filter=jobs', false)
            ->assertDontSee('/en/jobs?location=Dublin%2C%20Ireland', false);

        $this->get('/en/search?location=Dublin%2C%20Ireland&filter=jobs')
            ->assertOk()
            ->assertSee($job->title)
            ->assertSee('<option value="Dublin, Ireland" selected', false)
            ->assertSee('data-search-tab="jobs"', false);
    }

    public function test_search_filter_uses_the_existing_location_dropdown(): void
    {
        Job::factory()->create([
            'location' => 'Dublin, Ireland',
            'status' => JobStatus::Published->value,
        ]);

        $this->get('/en/search?search=engineer')
            ->assertOk()
            ->assertSee('data-search-location-filter', false)
            ->assertSee('<option value="Dublin, Ireland"', false);
    }
}
