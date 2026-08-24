<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Services\AdminDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

        Cache::flush();
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
                ->missing('dashboard.metrics.applications.period_count')
                ->where('dashboard.metrics.recruiters.value', 1)
                ->where('dashboard.metrics.recruiters.period_count', 1)
                ->where('dashboard.marketplace.average_applications_per_live_job', 1)
                ->where('dashboard.recentActivity.0.kind', 'application_submitted')
                ->where('dashboard.recentActivity.0.id', 'application:'.$application->id)
                ->where('dashboard.recentActivity.0.detail', $liveJob->title)
                ->where('dashboard.recentActivity.0.url', localized_route('admin.jobs', ['job' => $liveJob->id]))
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
                ->where('dashboard.attention.0.title', '1 job has no applications')
                ->where('dashboard.attention.0.action_label', 'View jobs')
                ->where('dashboard.attention.0.action_url', localized_route('admin.jobs', ['no_applications' => 1]))
            );
    }

    public function test_application_pipeline_is_selected_period_stable_and_zero_filled_for_every_status(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(2),
        ]);

        foreach ([ApplicationStatus::Pending, ApplicationStatus::Pending, ApplicationStatus::Rejected] as $status) {
            $candidate = User::factory()->create();
            $candidate->assignRole('Candidate');
            Application::factory()->for($candidate, 'candidate')->for($job)->create([
                'status' => $status->value,
                'created_at' => now()->subDays(2),
            ]);
        }

        $oldCandidate = User::factory()->create();
        $oldCandidate->assignRole('Candidate');
        Application::factory()->for($oldCandidate, 'candidate')->for($job)->create([
            'status' => ApplicationStatus::Accepted->value,
            'created_at' => now()->subDays(40),
        ]);

        $statusKeys = array_map(
            fn (ApplicationStatus $status): string => $status->value,
            ApplicationStatus::cases(),
        );

        $this->actingAs($admin)
            ->get('/en/admin/dashboard?range=7')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.pipeline.range', 7)
                ->has('dashboard.pipeline.period.start')
                ->has('dashboard.pipeline.period.end')
                ->where('dashboard.pipeline.status_keys', $statusKeys)
                ->where('dashboard.pipeline.counts.pending', 2)
                ->where('dashboard.pipeline.counts.shortlisted', 0)
                ->where('dashboard.pipeline.counts.interview', 0)
                ->where('dashboard.pipeline.counts.accepted', 0)
                ->where('dashboard.pipeline.counts.rejected', 1)
                ->where('dashboard.pipeline.counts.withdrawn', 0)
                ->where('dashboard.pipeline.statuses.5.key', 'withdrawn')
            );
    }

    public function test_marketplace_health_uses_live_recruiter_accounts_and_selected_candidate_cohort(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $liveRecruiter = User::factory()->for($company)->create();
        $liveRecruiter->assignRole('Recruiter');
        $draftRecruiter = User::factory()->for(Company::factory())->create();
        $draftRecruiter->assignRole('Recruiter');
        $unlistedRecruiter = User::factory()->for(Company::factory())->create();
        $unlistedRecruiter->assignRole('Recruiter');

        $liveJob = Job::factory()->for($company)->for($liveRecruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(3),
        ]);
        Job::factory()->for($draftRecruiter->company)->for($draftRecruiter, 'recruiter')->create([
            'status' => JobStatus::Draft->value,
            'published_at' => null,
        ]);

        $activatedCandidate = User::factory()->create(['created_at' => now()->subDays(4)]);
        $activatedCandidate->assignRole('Candidate');
        $inactiveCandidate = User::factory()->create(['created_at' => now()->subDays(4)]);
        $inactiveCandidate->assignRole('Candidate');
        $previousCandidate = User::factory()->create(['created_at' => now()->subDays(40)]);
        $previousCandidate->assignRole('Candidate');
        Application::factory()->for($activatedCandidate, 'candidate')->for($liveJob)->create([
            'created_at' => now()->subDays(3),
        ]);
        Application::factory()->for($previousCandidate, 'candidate')->for($liveJob)->create([
            'created_at' => now()->subDays(40),
        ]);

        $this->actingAs($admin)
            ->get('/en/admin/dashboard?range=30')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.marketplace.candidate_activation', 50)
                ->where('dashboard.marketplace.recruiters_with_live_jobs', 33.3)
                ->missing('dashboard.marketplace.recruiter_activation')
            );
    }

    public function test_failed_jobs_are_a_separate_attention_item_and_share_the_system_health_count(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(8),
        ]);

        DB::table('failed_jobs')->insert([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/en/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.attention', function ($items): bool {
                    $failed = $items->first(fn (array $item): bool => $item['key'] === 'failed_jobs');

                    return $failed !== null
                        && $failed['count'] === 1
                        && $failed['title'] === '1 failed job'
                        && $failed['action_label'] === 'Requires review'
                        && $failed['action_url'] === null
                        && $failed['status'] === 'requires_review';
                })
                ->where('dashboard.systemHealth.2.key', 'failed_jobs')
                ->where('dashboard.systemHealth.2.value', 1)
                ->where('dashboard.systemHealth.2.status', 'error')
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
                ->where('dashboard.marketplace.recruiters_with_live_jobs', null)
                ->where('dashboard.systemHealth.1.key', 'database')
                ->where('dashboard.systemHealth.1.status', 'healthy')
            );
    }

    public function test_dashboard_exposes_the_synchronized_recruiter_translation_key_in_both_locales(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/en/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('labels.recruiters_with_live_jobs', 'Recruiters with live jobs')
            );

        $this->actingAs($admin)
            ->get('/fr/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('labels.recruiters_with_live_jobs', 'Recruteurs avec des emplois en ligne')
            );
    }

    public function test_attention_titles_use_singular_and_plural_translations(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');

        Job::factory()->for($company)->for($recruiter, 'recruiter')->count(2)->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(8),
        ]);

        foreach (range(1, 2) as $index) {
            DB::table('failed_jobs')->insert([
                'id' => (string) Str::uuid(),
                'uuid' => (string) Str::uuid(),
                'connection' => 'sync',
                'queue' => 'default',
                'payload' => '{}',
                'exception' => 'Test failure '.$index,
                'failed_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get('/en/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.attention', function ($items): bool {
                    $jobs = $items->first(fn (array $item): bool => $item['key'] === 'jobs_without_applications');
                    $failed = $items->first(fn (array $item): bool => $item['key'] === 'failed_jobs');

                    return $jobs !== null
                        && $jobs['title'] === '2 jobs have no applications'
                        && $failed !== null
                        && $failed['title'] === '2 failed jobs';
                })
            );
    }

    public function test_analytics_cache_is_range_scoped_and_does_not_cache_attention_or_failed_jobs(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(8),
        ]);

        $service = app(AdminDashboardService::class);
        $first = $service->build(7);

        $periodEnd = CarbonImmutable::now()->endOfDay();
        $periodStart = $periodEnd->subDays(6)->startOfDay();
        $periodKey = $periodStart->toDateString().':'.$periodEnd->toDateString();
        $growthKey = 'admin-dashboard:analytics:v1:growth:aggregation:day:period:'.$periodKey.':buckets:'.$periodKey;
        $pipelineKey = 'admin-dashboard:analytics:v1:pipeline:aggregation:day:period:'.$periodKey.':buckets:'.$periodKey;
        $marketplaceKey = 'admin-dashboard:analytics:v1:marketplace:aggregation:day:period:'.$periodKey.':buckets:'.$periodKey;

        $this->assertTrue(Cache::has($growthKey));
        $this->assertTrue(Cache::has($pipelineKey));
        $this->assertTrue(Cache::has($marketplaceKey));
        $this->assertFalse(Cache::has('admin-dashboard:analytics:v1:growth:range:7'));

        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(8),
        ]);
        DB::table('failed_jobs')->insert([
            'id' => (string) Str::uuid(),
            'uuid' => (string) Str::uuid(),
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now(),
        ]);

        $second = $service->build(7);

        $this->assertSame($first['growth'], $second['growth']);
        $this->assertSame($first['pipeline']['counts'], $second['pipeline']['counts']);
        $this->assertSame(2, $second['attention']['jobs_without_applications']);
        $this->assertSame(1, $second['attention']['failed_jobs']);
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

    public function test_jobs_without_applications_attention_links_to_admin_jobs_filtered_not_public_jobs(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');

        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(8),
        ]);

        $this->actingAs($admin)
            ->get('/en/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('dashboard.attention.0.key', 'jobs_without_applications')
                ->where('dashboard.attention.0.action_url', localized_route('admin.jobs', ['no_applications' => 1]))
            );
    }

    public function test_dashboard_kpi_cards_resolve_to_real_admin_destinations(): void
    {
        $this->actingAs($this->admin())
            ->get('/en/admin/dashboard')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('labels.users', 'Registered users')
                ->where('labels.live_jobs', 'Live jobs')
                ->where('labels.recruiters', 'Recruiters')
            );

        // The KPI drill-down destinations must exist and be admin-scoped.
        $this->get('/en/admin/users')->assertOk();
        $this->get('/en/admin/jobs?status=published')->assertOk();
        $this->get('/en/admin/users?role=Recruiter')->assertOk();

        // A non-admin must never reach these (drill-down safety).
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $this->actingAs($candidate)->get('/en/admin/users?role=Recruiter')->assertForbidden();
        $this->actingAs($candidate)->get('/en/admin/jobs?status=published')->assertForbidden();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }
}
