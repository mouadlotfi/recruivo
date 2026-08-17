<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter', 'Admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_only_the_admin_role_can_open_the_dashboard(): void
    {
        $this->get('/en/admin/dashboard')->assertRedirect('/en/login');

        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $this->actingAs($candidate)->get('/en/admin/dashboard')->assertForbidden();

        $recruiter = User::factory()->create();
        $recruiter->assignRole('Recruiter');
        $this->actingAs($recruiter)->get('/en/admin/dashboard')->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)
            ->get('/en/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard', false)
                ->where('labels.title', 'Platform Overview')
                ->has('dashboard.metrics')
                ->has('dashboard.growth')
                ->has('dashboard.marketplace')
                ->has('dashboard.recentActivity')
                ->has('dashboard.systemHealth')
            );
    }

    public function test_dashboard_uses_real_platform_metrics_and_live_job_status(): void
    {
        $admin = $this->admin();
        $candidate = User::factory()->create(['created_at' => now()->subDays(3)]);
        $candidate->assignRole('Candidate');
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['created_at' => now()->subDays(4)]);
        $recruiter->assignRole('Recruiter');

        $liveJob = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(2),
            'created_at' => now()->subDays(2),
        ]);
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Draft->value,
            'published_at' => null,
        ]);
        $application = Application::factory()->for($candidate, 'candidate')->for($liveJob)->create([
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get('/en/admin/dashboard?range=30')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.range', 30)
                ->where('dashboard.metrics.users.value', 2)
                ->where('dashboard.metrics.users.period_count', 2)
                ->where('dashboard.metrics.live_jobs.value', 1)
                ->where('dashboard.metrics.live_jobs.period_count', 1)
                ->where('dashboard.metrics.applications.value', 1)
                ->where('dashboard.metrics.applications.period_count', 1)
                ->where('dashboard.metrics.recruiters.value', 1)
                ->where('dashboard.metrics.recruiters.period_count', 1)
                ->where('dashboard.marketplace.average_applications_per_live_job', 1)
                ->where('dashboard.recentActivity.0.kind', 'application_submitted')
                ->where('dashboard.recentActivity.0.detail', $liveJob->title)
                ->where('dashboard.recentActivity.0.url', localized_route('jobs.show', ['job' => $liveJob->id]))
                ->where('dashboard.recentActivity.0.occurred_at', $application->created_at->toIso8601String())
            );
    }

    public function test_existing_admin_user_management_route_keeps_rendering_with_the_admin_layout(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $user->assignRole('Candidate');

        $this->actingAs($admin)
            ->get('/en/admin/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Users', false)
                ->where('labels.sidebar_overview', 'Overview')
                ->where('labels.sidebar_users', 'Users')
                ->where('users', fn ($users): bool => $users->contains(
                    fn (array $listedUser): bool => $listedUser['id'] === $user->id,
                ))
            );
    }

    public function test_date_range_filters_applications_and_comparison_uses_the_previous_equivalent_period(): void
    {
        $admin = $this->admin();
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $currentJob = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();
        $currentJobTwo = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();
        $previousJob = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();

        Application::factory()->for($candidate, 'candidate')->for($currentJob)->create(['created_at' => now()->subDays(2)]);
        Application::factory()->for(User::factory()->create(), 'candidate')->for($currentJobTwo)->create(['created_at' => now()->subDay()]);
        Application::factory()->for($candidate, 'candidate')->for($previousJob)->create(['created_at' => now()->subDays(10)]);

        $this->actingAs($admin)
            ->get('/en/admin/dashboard?range=7')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.range', 7)
                ->where('dashboard.metrics.applications.value', 2)
                ->where('dashboard.metrics.applications.comparison.percentage', 100)
            );

        $this->actingAs($admin)
            ->get('/en/admin/dashboard?range=invalid')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.range', 30)
                ->where('dashboard.metrics.applications.value', 3)
            );
    }

    public function test_jobs_without_applications_only_include_older_live_jobs(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');

        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(8),
        ]);
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(2),
        ]);
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Draft->value,
            'published_at' => null,
        ]);

        $this->actingAs($admin)
            ->get('/en/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('dashboard.attention', 1)
                ->where('dashboard.attention.0.key', 'jobs_without_applications')
                ->where('dashboard.attention.0.count', 1)
            );
    }

    public function test_empty_installation_has_intentional_empty_states_and_no_fake_comparisons(): void
    {
        $this->actingAs($this->admin())
            ->get('/en/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('dashboard.attention', 0)
                ->has('dashboard.recentActivity', 0)
                ->where('dashboard.metrics.users.comparison', null)
                ->where('dashboard.metrics.applications.comparison', null)
                ->where('dashboard.marketplace.average_applications_per_live_job', null)
                ->where('dashboard.marketplace.candidate_activation', null)
                ->where('dashboard.marketplace.recruiter_activation', null)
                ->where('dashboard.systemHealth.1.key', 'database')
                ->where('dashboard.systemHealth.1.status', 'healthy')
            );
    }

    public function test_dashboard_query_count_does_not_scale_with_activity_rows(): void
    {
        $admin = $this->admin();
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $jobs = Job::factory()->count(8)->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(2),
        ]);

        foreach ($jobs as $job) {
            Application::factory()->for($candidate, 'candidate')->for($job)->create();
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/en/admin/dashboard')->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(50, $queryCount);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }
}
