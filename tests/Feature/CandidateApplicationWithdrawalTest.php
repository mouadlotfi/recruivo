<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesRoleUsers;
use Tests\TestCase;

class CandidateApplicationWithdrawalTest extends TestCase
{
    use CreatesRoleUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        foreach (['Candidate', 'Recruiter'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    /**
     * Every status a candidate may withdraw from, with the interview details set
     * on the application that the withdrawal must clear.
     *
     * @return array<string, array{0: ApplicationStatus, 1: array<string, mixed>}>
     */
    public static function withdrawableApplications(): array
    {
        return [
            'pending' => [ApplicationStatus::Pending, []],
            'shortlisted' => [ApplicationStatus::Shortlisted, []],
            'interview' => [ApplicationStatus::Interview, [
                'interview_at' => now()->addDays(2),
                'interview_location' => 'Casablanca HQ',
                'interview_url' => 'https://meet.example.com/abc',
                'interview_instructions' => 'Prepare a demo.',
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $interviewDetails
     */
    #[DataProvider('withdrawableApplications')]
    public function test_candidate_can_withdraw_own_application(ApplicationStatus $status, array $interviewDetails): void
    {
        $candidate = $this->makeCandidate();
        $application = $this->makeApplication($candidate, $status, $interviewDetails);

        $response = $this->actingAs($candidate)
            ->patch("/en/candidate/applications/{$application->id}/withdraw");

        $response->assertRedirect();
        $response->assertSessionHas('success', __('applications.withdrawn_success'));

        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseHas('applications', array_merge([
            'id' => $application->id,
            'status' => ApplicationStatus::Withdrawn->value,
            'status_changed' => false,
        ], array_fill_keys(array_keys($interviewDetails), null)));
    }

    /**
     * Statuses and ownership that must be refused, plus the status the row keeps.
     *
     * @return array<string, array{0: ApplicationStatus, 1: bool}>
     */
    public static function nonWithdrawableApplications(): array
    {
        return [
            'accepted' => [ApplicationStatus::Accepted, false],
            'rejected' => [ApplicationStatus::Rejected, false],
            'already withdrawn' => [ApplicationStatus::Withdrawn, false],
            'owned by another candidate' => [ApplicationStatus::Pending, true],
        ];
    }

    #[DataProvider('nonWithdrawableApplications')]
    public function test_candidate_cannot_withdraw_application(ApplicationStatus $status, bool $ownedByAnotherCandidate): void
    {
        $owner = $this->makeCandidate();
        $application = $this->makeApplication($owner, $status);
        $actor = $ownedByAnotherCandidate ? $this->makeCandidate() : $owner;

        $this->actingAs($actor)
            ->patch("/en/candidate/applications/{$application->id}/withdraw")
            ->assertForbidden();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => $status->value,
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

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Candidate/Applications', false)
            ->has('applications', 2)
        );

        // assertEquals, not assertSame: the page orders by created_at, and two
        // fixtures created inside the same second can come back either way.
        $this->assertEquals(
            [$pending->id => true, $accepted->id => false],
            collect($response->inertiaProps('applications'))
                ->mapWithKeys(fn (array $application) => [$application['id'] => $application['status'] !== ApplicationStatus::Accepted->value])
                ->all()
        );

        $card = File::get(resource_path('js/Components/Applications/CandidateApplicationCard.vue'));
        $applicationsPage = File::get(resource_path('js/Pages/Candidate/Applications.vue'));
        $this->assertStringContainsString("['pending', 'shortlisted', 'interview'].includes", $card);
        $this->assertStringContainsString('router.patch(withdrawUrl, {}, {', $card);
        $this->assertStringContainsString("only: ['applications', 'statusCounts', 'pagination', 'flash']", $card);
        $this->assertStringContainsString('min-h-11', $card);
        $this->assertStringContainsString('window.confirm', $card);
        $this->assertStringContainsString('const pageStart = Math.max', $applicationsPage);
        $this->assertStringContainsString('const incomingIds = new Set', $applicationsPage);
    }

    public function test_withdrawn_badge_renders_with_neutral_gray_classes_on_candidate_page(): void
    {
        $candidate = $this->makeCandidate();
        $this->makeApplication($candidate, ApplicationStatus::Withdrawn);

        $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Candidate/Applications', false)
                ->where('applications.0.status', ApplicationStatus::Withdrawn->value)
                ->where('applications.0.status_label', __('applications.withdrawn'))
            );

        $badge = File::get(resource_path('js/Components/Applications/ApplicationStatusBadge.vue'));
        $this->assertStringContainsString("withdrawn: 'bg-stone-200 text-stone-700", $badge);
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

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Applications/Index', false)
            ->has('applications', 2)
        );

        $this->assertSame(
            [
                $withdrawn->id => ['is_withdrawn' => true, 'can_review' => false],
                $shortlisted->id => ['is_withdrawn' => false, 'can_review' => true],
            ],
            collect($response->inertiaProps('applications'))
                ->mapWithKeys(fn (array $application) => [
                    $application['id'] => [
                        'is_withdrawn' => $application['is_withdrawn'],
                        'can_review' => $application['can_review'],
                    ],
                ])
                ->all()
        );

        $card = File::get(resource_path('js/Components/Applications/RecruiterApplicationCard.vue'));
        $review = File::get(resource_path('js/Components/Applications/ApplicationReviewPanel.vue'));
        $this->assertStringContainsString('withdrawn_by_candidate', $card);
        $this->assertStringContainsString('v-else', $card);
        $this->assertStringContainsString(':application="application"', $card);
        $this->assertStringContainsString("const STATUS_OPTIONS = ['pending', 'shortlisted', 'interview', 'accepted', 'rejected']", $review);
        $this->assertStringNotContainsString("'withdrawn'", $review);
    }

    public function test_candidate_dashboard_in_progress_count_excludes_withdrawn(): void
    {
        $candidate = $this->makeCandidate();
        $this->makeApplication($candidate, ApplicationStatus::Pending);
        $this->makeApplication($candidate, ApplicationStatus::Withdrawn);

        $response = $this->actingAs($candidate)->get('/en/candidate/dashboard');
        $response->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Candidate/Dashboard', false)
            ->where('inProgressApplications', 1)
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

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Applications/Index', false)
            ->where('statusCounts.6.key', 'withdrawn')
            ->where('statusCounts.6.count', 1)
        );

        $applicationsPage = File::get(resource_path('js/Pages/Recruiter/Applications/Index.vue'));
        $badge = File::get(resource_path('js/Components/Applications/ApplicationStatusBadge.vue'));
        $this->assertStringContainsString('status=${tab.key}', $applicationsPage);
        $this->assertStringContainsString("key === 'all' ? props.labels.all_statuses : props.labels[key]", $applicationsPage);
        $this->assertStringContainsString("withdrawn: 'bg-stone-200 text-stone-700", $badge);
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
