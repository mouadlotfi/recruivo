<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications?status=shortlisted')
            ->assertOk()
            ->assertSee('data-application-status-tabs', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee($shortlistedCandidate->name)
            ->assertDontSee($pendingCandidate->name);
    }

    public function test_application_status_select_has_all_five_statuses(): void
    {
        [$recruiter, , , $job] = $this->makePipeline();

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications');

        $html = (string) $response->getContent();

        foreach (['pending', 'shortlisted', 'interview', 'accepted', 'rejected'] as $status) {
            $this->assertStringContainsString(
                'value="'.$status.'"',
                $html,
                "Select should have option for {$status}"
            );
        }
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
            ->assertSee('Interview')
            ->assertSee('bg-violet-100', false);
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
