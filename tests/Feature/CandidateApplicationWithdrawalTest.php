<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CandidateApplicationWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        foreach (['Candidate', 'Recruiter'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_candidate_can_withdraw_own_pending_application_and_row_is_not_deleted(): void
    {
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($candidate, ApplicationStatus::Pending);

        $response = $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw");

        $response->assertRedirect();
        $response->assertSessionHas('success', __('applications.withdrawn_success'));

        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Withdrawn->value,
        ]);
    }

    public function test_candidate_can_withdraw_own_shortlisted_application(): void
    {
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($candidate, ApplicationStatus::Shortlisted);

        $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Withdrawn->value,
        ]);
    }

    public function test_candidate_can_withdraw_own_interview_application_and_interview_fields_are_cleared(): void
    {
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($candidate, ApplicationStatus::Interview, [
            'interview_at' => now()->addDays(2),
            'interview_location' => 'Casablanca HQ',
            'interview_url' => 'https://meet.example.com/abc',
            'interview_instructions' => 'Prepare a demo.',
        ]);

        $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Withdrawn->value,
            'interview_at' => null,
            'interview_location' => null,
            'interview_url' => null,
            'interview_instructions' => null,
            'status_changed' => false,
        ]);
    }

    public function test_candidate_cannot_withdraw_accepted_application(): void
    {
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($candidate, ApplicationStatus::Accepted);

        $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Accepted->value,
        ]);
    }

    public function test_candidate_cannot_withdraw_rejected_application(): void
    {
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($candidate, ApplicationStatus::Rejected);

        $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Rejected->value,
        ]);
    }

    public function test_candidate_cannot_withdraw_another_candidates_application(): void
    {
        $owner = $this->makeCandidate();
        $other = $this->makeCandidate();
        $application = $this->makeApplication($owner, ApplicationStatus::Pending);

        $this->actingAs($other)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Pending->value,
        ]);
    }

    public function test_candidate_cannot_withdraw_an_already_withdrawn_application(): void
    {
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($candidate, ApplicationStatus::Withdrawn);

        $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Withdrawn->value,
        ]);
    }

    public function test_recruiter_cannot_change_a_withdrawn_application_but_other_statuses_still_work(): void
    {
        Notification::fake();
        [$recruiter, $job] = $this->makeRecruiterEnvironment();
        $withdrawn = $this->makeApplicationForJob($job, ApplicationStatus::Withdrawn);
        $pending = $this->makeApplicationForJob($job, ApplicationStatus::Pending);

        // Withdrawn is terminal for the recruiter
        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$withdrawn->id}", [
                'status' => ApplicationStatus::Pending->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('applications', [
            'id' => $withdrawn->id,
            'status' => ApplicationStatus::Withdrawn->value,
        ]);

        // Other statuses still transition normally
        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$pending->id}", [
                'status' => ApplicationStatus::Shortlisted->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'id' => $pending->id,
            'status' => ApplicationStatus::Shortlisted->value,
        ]);
    }

    public function test_recruiter_cannot_set_an_application_to_withdrawn(): void
    {
        Notification::fake();
        [$recruiter, $job] = $this->makeRecruiterEnvironment();
        $pending = $this->makeApplicationForJob($job, ApplicationStatus::Pending);

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$pending->id}", [
                'status' => ApplicationStatus::Withdrawn->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('applications', [
            'id' => $pending->id,
            'status' => ApplicationStatus::Pending->value,
        ]);
    }

    public function test_demo_candidate_cannot_withdraw_and_database_is_unchanged(): void
    {
        $candidate = $this->makeCandidate(['is_demo' => true]);
        $application = $this->makeApplication($candidate, ApplicationStatus::Pending);

        $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertSessionHasErrors('candidate_action');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Pending->value,
        ]);
    }

    public function test_candidate_page_renders_withdraw_button_only_for_eligible_statuses(): void
    {
        $candidate = $this->makeCandidate();
        $pending = $this->makeApplication($candidate, ApplicationStatus::Pending);
        $accepted = $this->makeApplication($candidate, ApplicationStatus::Accepted);

        $response = $this->actingAs($candidate)->get('/en/candidate/applications');
        $response->assertOk();

        $html = (string) $response->getContent();

        $this->assertStringContainsString(
            '/en/candidate/applications/'.$pending->id.'/withdraw',
            $html,
            'Pending application must offer a withdraw action'
        );
        $this->assertStringNotContainsString(
            '/en/candidate/applications/'.$accepted->id.'/withdraw',
            $html,
            'Accepted application must not offer a withdraw action'
        );
        $this->assertStringContainsString('min-h-11', $html);
        $this->assertStringContainsString('return confirm(', $html);
    }

    public function test_withdrawn_badge_renders_with_neutral_gray_classes_on_candidate_page(): void
    {
        $candidate = $this->makeCandidate();
        $this->makeApplication($candidate, ApplicationStatus::Withdrawn);

        $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->assertOk()
            ->assertSee('bg-stone-200', false)
            ->assertSee('text-stone-700', false)
            ->assertSee('Withdrawn');
    }

    public function test_recruiter_page_shows_withdrawn_read_only_state_and_select_excludes_withdrawn(): void
    {
        Notification::fake();
        [$recruiter, $job] = $this->makeRecruiterEnvironment();
        $withdrawn = $this->makeApplicationForJob($job, ApplicationStatus::Withdrawn);
        $shortlisted = $this->makeApplicationForJob($job, ApplicationStatus::Shortlisted);

        $response = $this->actingAs($recruiter)
            ->get("/en/recruiter/jobs/{$job->id}/applications");
        $response->assertOk();

        $html = (string) $response->getContent();

        $this->assertStringContainsString(__('recruiter.withdrawn_by_candidate'), $html);
        $this->assertStringNotContainsString(
            'action="'.localized_route('recruiter.applications.update', $withdrawn).'"',
            $html,
            'Withdrawn application must not render the review form'
        );
        $this->assertStringContainsString(
            'action="'.localized_route('recruiter.applications.update', $shortlisted).'"',
            $html,
            'Non-withdrawn application keeps its review form'
        );
        $this->assertStringNotContainsString(
            'value="withdrawn"',
            $html,
            'Recruiter status select must not offer Withdrawn'
        );
    }

    public function test_candidate_dashboard_in_progress_count_excludes_withdrawn(): void
    {
        $candidate = $this->makeCandidate();
        $this->makeApplication($candidate, ApplicationStatus::Pending);
        $this->makeApplication($candidate, ApplicationStatus::Withdrawn);

        $response = $this->actingAs($candidate)->get('/en/candidate/dashboard');
        $response->assertOk();

        $html = (string) $response->getContent();
        $this->assertSame(
            '1',
            $this->inProgressCountFromDashboard($html),
            'In-progress must count only pending+shortlisted+interview'
        );
    }

    public function test_recruiter_tabs_include_withdrawn_count(): void
    {
        Notification::fake();
        [$recruiter, $job] = $this->makeRecruiterEnvironment();
        $this->makeApplicationForJob($job, ApplicationStatus::Withdrawn);

        $response = $this->actingAs($recruiter)
            ->get("/en/recruiter/jobs/{$job->id}/applications");
        $response->assertOk();

        $html = (string) $response->getContent();

        // Withdrawn tab exists and links to the status filter.
        $this->assertStringContainsString('?status=withdrawn', $html, 'Withdrawn tab must exist');
        // The withdrawn tab link itself carries the translated label.
        $this->assertMatchesRegularExpression(
            '/href="[^"]*\?status=withdrawn[^"]*"[^>]*>\s*'.preg_quote(__('recruiter.withdrawn'), '/').'/',
            $html,
            'Withdrawn tab must be labeled'
        );
        // The application badge shows the neutral gray withdrawn style.
        $this->assertMatchesRegularExpression(
            '/bg-stone-200[^>]*>'.preg_quote(__('recruiter.withdrawn'), '/').'<\/span>/',
            $html,
            'Withdrawn application must render a gray badge'
        );
    }

    private function inProgressCountFromDashboard(string $html): ?string
    {
        preg_match(
            '/'.preg_quote(__('candidate.in_progress'), '/').'<\/p>\s*<p[^>]*>(\d+)<\/p>/s',
            $html,
            $matches
        );

        return $matches[1] ?? null;
    }

    private function makeCandidate(array $attributes = []): User
    {
        $candidate = User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
        $candidate->assignRole('Candidate');

        return $candidate;
    }

    private function makeApplication(User $candidate, ApplicationStatus $status, array $extra = []): Application
    {
        return Application::factory()
            ->for($candidate, 'candidate')
            ->for(Job::factory()->create(['title' => 'Withdraw Test Job']))
            ->create(array_merge(['status' => $status->value], $extra));
    }

    private function makeApplicationForJob(Job $job, ApplicationStatus $status): Application
    {
        return Application::factory()
            ->for($this->makeCandidate(), 'candidate')
            ->for($job)
            ->create(['status' => $status->value]);
    }

    private function makeRecruiterEnvironment(): array
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $job = Job::factory()
            ->for($company)
            ->for($recruiter, 'recruiter')
            ->create(['title' => 'Withdraw Test Job']);

        return [$recruiter, $job];
    }
}
