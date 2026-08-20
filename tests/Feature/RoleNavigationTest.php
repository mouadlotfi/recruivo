<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
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

    public function test_recruiter_navigation_keeps_applications_scoped_to_each_job(): void
    {
        $routes = File::get(base_path('routes/web.php'));

        $this->assertStringNotContainsString("name('applications.index')", $routes);
        $this->assertStringContainsString("name('jobs.applications')", $routes);
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

    public function test_verified_user_cannot_visit_the_verify_email_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');

        $this->actingAs($user)
            ->get('/en/email/verify')
            ->assertRedirect(localized_route('home'));
    }

    public function test_recent_dashboard_applications_link_to_the_job_applications(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create(['name' => 'Clickable Applicant']);
        $candidate->assignRole('Candidate');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();
        $application = Application::factory()->for($candidate, 'candidate')->for($job)->create();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Dashboard', false)
                ->has('recentApplications', 1)
                ->where('recentApplications.0.id', $application->id)
                ->where('recentApplications.0.candidate.name', $candidate->name)
                ->where('recentApplications.0.job.id', $job->id)
                ->where('recentApplications.0.job.title', $job->title)
            );

        $dashboard = File::get(resource_path('js/Pages/Recruiter/Dashboard.vue'));

        $this->assertStringContainsString('localeUrl(`/recruiter/jobs/${application.job.id}/applications`)', $dashboard);
        $this->assertStringNotContainsString('/recruiter/applicants/', $dashboard);
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

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Jobs/Index', false)
            ->has('jobs', 1)
            ->where('jobs.0.id', $job->id)
            ->where('jobs.0.title', $job->title)
        );

        $jobsPage = File::get(resource_path('js/Pages/Recruiter/Jobs/Index.vue'));

        $this->assertStringContainsString('const jobShowUrl = (job: RecruiterJobSummary) => localeUrl(`/recruiter/jobs/${job.id}`)', $jobsPage);
        $this->assertStringContainsString('data-recruiter-job-card', $jobsPage);
        $this->assertStringContainsString('before:absolute before:inset-0', $jobsPage);
        $this->assertStringContainsString('data-job-actions', $jobsPage);
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

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id)
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Jobs/Show', false)
            ->where('job.id', $job->id)
            ->where('job.title', 'Senior Laravel Developer')
            ->where('job.description', 'Build and maintain reliable Laravel applications.')
            ->where('job.location', 'San Francisco')
        );

        $jobPage = File::get(resource_path('js/Pages/Recruiter/Jobs/Show.vue'));

        $this->assertStringContainsString("localeUrl('/recruiter/jobs')", $jobPage);
        $this->assertStringContainsString('/recruiter/jobs/${props.job.id}/applications', $jobPage);
        $this->assertStringContainsString('/recruiter/jobs/${props.job.id}/edit', $jobPage);
        $this->assertStringNotContainsString('/recruiter/applicants/', $jobPage);
    }

    public function test_candidate_navigation_links_to_saved_jobs_but_recruiter_navigation_does_not(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');

        $this->actingAs($candidate)
            ->get('/en')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Home/Index', false)
            );

        foreach ([
            resource_path('js/Components/Layout/Navigation.vue'),
            resource_path('js/Components/Layout/MobileNav.vue'),
        ] as $navigationPath) {
            $navigation = File::get($navigationPath);
            $this->assertStringContainsString("localeUrl('/candidate/saved-jobs')", $navigation);

            $recruiterMarker = str_contains($navigation, '<template v-if="isRecruiter">')
                ? '<template v-if="isRecruiter">'
                : '<template v-else-if="isRecruiter">';
            $recruiterStart = strpos($navigation, $recruiterMarker);
            $candidateStart = strpos($navigation, '<template v-else-if="isCandidate">');

            $this->assertNotFalse($recruiterStart);
            $this->assertNotFalse($candidateStart);
            $recruiterBranch = substr($navigation, $recruiterStart, $candidateStart - $recruiterStart);
            $this->assertStringNotContainsString('/candidate/saved-jobs', $recruiterBranch);
        }

        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $this->actingAs($recruiter)
            ->get('/en/recruiter/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Dashboard', false)
            );
    }
}
