<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter', 'Admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_primary_navigation_uses_role_specific_task_links_without_blog(): void
    {
        $header = File::get(resource_path('views/partials/header.blade.php'));

        $this->assertStringContainsString("localized_route('recruiter.dashboard')", $header);
        $this->assertStringContainsString("localized_route('recruiter.jobs.index')", $header);
        $this->assertStringNotContainsString("localized_route('recruiter.applications.index')", $header);
        $this->assertStringContainsString("localized_route('recruiter.jobs.create')", $header);
        $this->assertStringContainsString("__('recruiter.manage_jobs')", $header);
        $this->assertStringContainsString('data-recruiter-explore-menu', $header);
        $this->assertStringContainsString("__('common.explore')", $header);
        $this->assertStringNotContainsString("localized_route('posts.index')", $header);

        $explorePosition = strpos($header, 'data-recruiter-explore-menu');
        $dashboardPosition = strpos($header, "localized_route('recruiter.dashboard')");

        $this->assertNotFalse($explorePosition);
        $this->assertNotFalse($dashboardPosition);
        $this->assertLessThan($dashboardPosition, $explorePosition);
    }

    public function test_recruiter_navigation_keeps_applications_scoped_to_each_job(): void
    {
        $routes = File::get(base_path('routes/web.php'));
        $mobileNavigation = File::get(resource_path('views/partials/mobile-nav.blade.php'));

        $this->assertStringNotContainsString("name('applications.index')", $routes);
        $this->assertStringContainsString("name('jobs.applications')", $routes);
        $this->assertStringNotContainsString("localized_route('recruiter.applications.index')", $mobileNavigation);

        $explorePosition = strpos($mobileNavigation, 'data-recruiter-mobile-explore-menu');
        $dashboardPosition = strpos($mobileNavigation, "'recruiter.dashboard'");

        $this->assertNotFalse($explorePosition);
        $this->assertNotFalse($dashboardPosition);
        $this->assertLessThan($dashboardPosition, $explorePosition);
    }

    public function test_recruiter_dashboard_is_the_recruiter_home_page(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $this->actingAs($recruiter)
            ->get('/en')
            ->assertRedirect('/en/recruiter/dashboard');

        auth()->logout();

        $this->withSession(['_token' => 'test-token'])->post('/en/login', [
            '_token' => 'test-token',
            'email' => $recruiter->email,
            'password' => 'password',
        ])->assertRedirect('/en/recruiter/dashboard');
    }

    public function test_recruiter_dashboard_does_not_render_the_tips_panel(): void
    {
        $dashboard = File::get(resource_path('views/recruiter/dashboard.blade.php'));

        $this->assertStringNotContainsString("__('recruiter.tips_for_success')", $dashboard);
        $this->assertStringNotContainsString("__('recruiter.tip_1')", $dashboard);
    }

    public function test_recent_dashboard_applications_link_to_the_applicant_profile(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create(['name' => 'Clickable Applicant']);
        $candidate->assignRole('Candidate');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();
        Application::factory()->for($candidate, 'candidate')->for($job)->create();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/dashboard')
            ->assertOk()
            ->assertSee('data-recent-applicant-link', false)
            ->assertSee('href="'.localized_route('recruiter.applicants.show', $candidate).'"', false)
            ->assertSee('href="'.localized_route('recruiter.jobs.applications', $job).'"', false);
    }

    public function test_recruiter_job_cards_render_the_application_count_once(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();
        Application::factory()->for($candidate, 'candidate')->for($job)->create();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs')
            ->assertOk()
            ->assertSee('1 application')
            ->assertDontSee('1 1 application');
    }

    public function test_recruiter_job_card_opens_the_job_without_covering_its_actions(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs')
            ->assertOk()
            ->assertSee('href="'.localized_route('recruiter.jobs.show', $job).'"', false)
            ->assertSee('data-recruiter-job-card', false)
            ->assertSee('before:absolute before:inset-0', false)
            ->assertSee('data-job-actions', false);
    }

    public function test_recruiter_can_open_an_owned_job_detail_page_from_manage_jobs(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'title' => 'Senior Laravel Developer',
            'description' => 'Build and maintain reliable Laravel applications.',
            'location' => 'San Francisco',
            'salary_min' => 8000,
            'salary_max' => 12000,
        ]);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id)
            ->assertOk()
            ->assertViewIs('recruiter.jobs.show')
            ->assertSee('Senior Laravel Developer')
            ->assertSee('Build and maintain reliable Laravel applications.')
            ->assertSee('San Francisco')
            ->assertSee(localized_route('recruiter.jobs.applications', $job), false)
            ->assertSee(localized_route('recruiter.jobs.edit', $job), false)
            ->assertSee(localized_route('recruiter.jobs.index'), false);
    }

    public function test_candidate_navigation_links_to_saved_jobs_but_recruiter_navigation_does_not(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');

        $this->actingAs($candidate)
            ->get('/en')
            ->assertOk()
            ->assertSee('href="'.localized_route('candidate.saved-jobs.index').'"', false);

        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $this->actingAs($recruiter)
            ->get('/en/recruiter/dashboard')
            ->assertOk()
            ->assertDontSee('href="'.localized_route('candidate.saved-jobs.index').'"', false);
    }

    public function test_candidate_mobile_navigation_keeps_four_columns_with_saved_jobs(): void
    {
        $mobileNav = File::get(resource_path('views/partials/mobile-nav.blade.php'));

        $this->assertStringContainsString("localized_route('candidate.saved-jobs.index')", $mobileNav);
        $this->assertStringContainsString('grid-cols-4', $mobileNav);
    }

    public function test_mobile_layout_uses_truncating_text_and_a_viewport_clamped_explore_menu(): void
    {
        $dashboard = File::get(resource_path('views/recruiter/dashboard.blade.php'));
        $mobileNav = File::get(resource_path('views/partials/mobile-nav.blade.php'));

        $this->assertStringContainsString('truncate', $dashboard);
        $this->assertMatchesRegularExpression(
            '/recruiter\.pending.*truncate|truncate.*recruiter\.pending/s',
            $dashboard,
            'Recent Applications rows must keep badges clear of the job title.'
        );
        $this->assertMatchesRegularExpression(
            '/fixed bottom-16 left-4 right-4.*max-w-\[calc\(100vw-1rem\)\]/s',
            $mobileNav,
            'The mobile Explore menu must be clamped inside the viewport.'
        );
    }
}
