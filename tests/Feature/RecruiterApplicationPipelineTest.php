<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruiterApplicationPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_shortlisted_tab_shows_shortlisted_candidates_and_hides_others(): void
    {
        [$recruiter, $shortlistedCandidate, $pendingCandidate, $job] = $this->makePipeline();

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications?status=shortlisted')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Applications/Index', false)
            ->where('status', 'shortlisted')
            ->has('applications', 1)
            ->where('applications.0.candidate.name', $shortlistedCandidate->name)
        );

        $applicationsPage = File::get(resource_path('js/Pages/Recruiter/Applications/Index.vue'));
        $this->assertStringContainsString('data-application-status-tabs', $applicationsPage);
        $this->assertStringContainsString(":aria-current=\"props.status === tab.key ? 'page' : undefined\"", $applicationsPage);
    }

    public function test_application_status_select_has_all_five_statuses(): void
    {
        [$recruiter, , , $job] = $this->makePipeline();

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Applications/Index', false)
        );

        $review = File::get(resource_path('js/Components/Applications/ApplicationReviewPanel.vue'));
        $this->assertStringContainsString("const STATUS_OPTIONS = ['pending', 'shortlisted', 'interview', 'accepted', 'rejected']", $review);
    }

    public function test_tabs_include_all_five_statuses(): void
    {
        [$recruiter, , , $job] = $this->makePipeline();

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications');

        $response->assertOk();
        foreach (['Shortlisted', 'Interview'] as $label) {
            $response->assertSee($label);
        }
    }

    public function test_recruiter_dashboard_shows_interview_badge_with_violet_color(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['title' => 'DevOps Engineer']);
        Application::factory()->for($candidate, 'candidate')->for($job)->create([
            'status' => ApplicationStatus::Interview->value,
        ]);

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/dashboard');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Dashboard', false)
                ->where('recentApplications.0.status', 'interview')
            );

        $dashboard = File::get(resource_path('js/Pages/Recruiter/Dashboard.vue'));
        $this->assertStringContainsString("interview: 'bg-violet-100", $dashboard);
    }

    private function makePipeline(): array
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $shortlistedCandidate = User::factory()->create(['name' => 'Shortlisted Candidate', 'email_verified_at' => now()]);
        $shortlistedCandidate->assignRole('Candidate');

        $pendingCandidate = User::factory()->create(['name' => 'Pending Candidate', 'email_verified_at' => now()]);
        $pendingCandidate->assignRole('Candidate');

        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['title' => 'Full-Stack Developer']);

        Application::factory()->for($shortlistedCandidate, 'candidate')->for($job)->create(['status' => ApplicationStatus::Shortlisted->value]);
        Application::factory()->for($pendingCandidate, 'candidate')->for($job)->create(['status' => ApplicationStatus::Pending->value]);

        return [$recruiter, $shortlistedCandidate, $pendingCandidate, $job];
    }
}
