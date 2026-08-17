<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CandidateApplicationExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_localized_login_from_my_applications(): void
    {
        $this->get('/en/candidate/applications')
            ->assertRedirect('/en/login');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
    }

    public function test_demo_candidate_cannot_submit_a_new_application_but_keeps_existing_applications(): void
    {
        Notification::fake();
        $candidate = User::factory()->create([
            'email_verified_at' => now(),
            'is_demo' => true,
        ]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/demo.pdf']);
        $existingJob = Job::factory()->create(['status' => JobStatus::Published->value]);
        $newJob = Job::factory()->create(['status' => JobStatus::Published->value]);
        $existing = Application::factory()->for($candidate, 'candidate')->for($existingJob)->create([
            'status' => ApplicationStatus::Accepted->value,
        ]);

        $this->actingAs($candidate)
            ->post("/en/jobs/{$newJob->id}/apply")
            ->assertSessionHasErrors('application');

        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseHas('applications', ['id' => $existing->id]);

        $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Candidate/Applications', false)
                ->has('applications', 1)
                ->where('applications.0.job.id', $existingJob->id)
                ->where('applications.0.job.title', $existingJob->title)
            );
    }

    public function test_demo_candidate_cannot_apply_through_the_api(): void
    {
        $candidate = User::factory()->create(['is_demo' => true]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/demo.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);
        Sanctum::actingAs($candidate);

        $this->postJson(route('api.jobs.apply', $job), [
            'cover_letter' => 'A valid cover letter that reaches the demo-account guard.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('application');

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_candidate_application_tabs_filter_by_status_and_show_counts(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        $pendingJob = Job::factory()->create(['title' => 'Pending Position']);
        $acceptedJob = Job::factory()->create(['title' => 'Accepted Position']);
        $rejectedJob = Job::factory()->create(['title' => 'Rejected Position']);
        $shortlistedJob = Job::factory()->create(['title' => 'Shortlisted Position']);
        $interviewJob = Job::factory()->create(['title' => 'Interview Position']);

        Application::factory()->for($candidate, 'candidate')->for($pendingJob)->create(['status' => ApplicationStatus::Pending->value]);
        Application::factory()->for($candidate, 'candidate')->for($acceptedJob)->create(['status' => ApplicationStatus::Accepted->value]);
        Application::factory()->for($candidate, 'candidate')->for($rejectedJob)->create(['status' => ApplicationStatus::Rejected->value]);
        Application::factory()->for($candidate, 'candidate')->for($shortlistedJob)->create(['status' => ApplicationStatus::Shortlisted->value]);
        Application::factory()->for($candidate, 'candidate')->for($interviewJob)->create(['status' => ApplicationStatus::Interview->value]);

        $response = $this->actingAs($candidate)->get('/en/candidate/applications?status=accepted');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Candidate/Applications', false)
                ->where('status', 'accepted')
                ->has('applications', 1)
                ->where('applications.0.job.title', 'Accepted Position')
                ->where('statusCounts.0.count', 5)
                ->where('statusCounts.4.count', 1)
            );

        $applicationsPage = File::get(resource_path('js/Pages/Candidate/Applications.vue'));
        $this->assertStringContainsString('data-candidate-application-status-tabs', $applicationsPage);
        $this->assertStringContainsString(":aria-current=\"props.status === tab.key ? 'page' : undefined\"", $applicationsPage);

        $this->actingAs($candidate)
            ->get('/en/candidate/applications?status=interview')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Candidate/Applications', false)
                ->where('status', 'interview')
                ->where('applications.0.job.title', 'Interview Position')
            );

        $this->actingAs($candidate)
            ->get('/en/candidate/applications?status=invalid')
            ->assertRedirect('/en/candidate/applications');

        // Dashboard: in-progress count includes pending+shortlisted+interview
        $this->actingAs($candidate)
            ->get('/en/candidate/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Candidate/Dashboard', false)
                ->where('inProgressApplications', 3)
                ->where('labels.in_progress', __('candidate.in_progress'))
                ->where('labels.shortlisted', __('candidate.shortlisted'))
                ->where('labels.interview', __('candidate.interview'))
            );
    }

    public function test_repeated_submission_from_the_same_apply_form_remains_a_success(): void
    {
        Notification::fake();
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/candidate.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);
        $token = 'same-form-submission-token';

        $this->actingAs($candidate)
            ->withSession(["job_application_submission.{$job->id}" => ['token' => $token, 'completed' => false]])
            ->post("/en/jobs/{$job->id}/apply", [
                'submission_token' => $token,
                'cover_letter' => 'I am a strong fit for this role.',
                'resume_source' => 'profile',
            ])
            ->assertSessionHas('success', __('jobs.application_submitted'));

        $this->actingAs($candidate)
            ->post("/en/jobs/{$job->id}/apply", [
                'submission_token' => $token,
                'cover_letter' => 'I am a strong fit for this role.',
                'resume_source' => 'profile',
            ])
            ->assertSessionHas('success', __('jobs.application_submitted'))
            ->assertSessionMissing('error');

        $this->assertDatabaseCount('applications', 1);
    }

    public function test_a_later_application_attempt_still_reports_the_existing_application(): void
    {
        Notification::fake();
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/candidate.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);
        Application::factory()->for($candidate, 'candidate')->for($job)->create();

        $this->actingAs($candidate)
            ->withSession(["job_application_submission.{$job->id}" => ['token' => 'new-form-token', 'completed' => false]])
            ->post("/en/jobs/{$job->id}/apply", ['submission_token' => 'new-form-token'])
            ->assertSessionHas('error', __('jobs.already_applied_error'));
    }

    public function test_existing_application_state_uses_successful_not_error_wording(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/candidate.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);
        Application::factory()->for($candidate, 'candidate')->for($job)->create();

        $this->actingAs($candidate)
            ->get("/en/jobs/{$job->id}")
            ->assertOk()
            ->assertSee(__('jobs.you_have_applied'))
            ->assertDontSee(__('jobs.already_applied_error'));
    }

    public function test_application_requires_cover_letter_and_offers_job_specific_resume_upload(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/profile.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);

        $this->actingAs($candidate)->get("/en/jobs/{$job->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Jobs/Show', false)
                ->where('job.id', $job->id)
                ->where('canApply', true)
                ->where('hasProfileResume', true)
            );

        $show = File::get(resource_path('js/Pages/Jobs/Show.vue'));
        $this->assertStringContainsString('forceFormData: true', $show);
        $this->assertStringContainsString('name="cover_letter"', $show);
        $this->assertStringContainsString('name="resume_source"', $show);
        $this->assertStringContainsString('value="profile"', $show);
        $this->assertStringContainsString('value="upload"', $show);
        $this->assertStringContainsString('type="file"', $show);
        $this->assertStringContainsString('{{ labels.cover_letter }}', $show);

        $this->actingAs($candidate)
            ->post("/en/jobs/{$job->id}/apply", [])
            ->assertSessionHasErrors('cover_letter');
    }

    public function test_application_requires_a_valid_resume_source_and_upload_when_selected(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/profile.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);

        $this->actingAs($candidate)->post("/en/jobs/{$job->id}/apply", [
            'cover_letter' => 'I am a strong fit.',
        ])->assertSessionHasErrors('resume_source');

        $this->actingAs($candidate)->post("/en/jobs/{$job->id}/apply", [
            'cover_letter' => 'I am a strong fit.',
            'resume_source' => 'upload',
        ])->assertSessionHasErrors('resume');
    }

    public function test_uploaded_application_resume_is_job_specific_and_does_not_replace_profile_resume(): void
    {
        Storage::fake('private');
        Notification::fake();
        Storage::disk('private')->put('resumes/profile.pdf', 'profile resume');
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        $profile = CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/profile.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);

        $this->actingAs($candidate)->post("/en/jobs/{$job->id}/apply", [
            'cover_letter' => 'My required cover letter for this role.',
            'resume_source' => 'upload',
            'resume' => UploadedFile::fake()->create('job-specific.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('success', __('jobs.application_submitted'));

        $application = Application::whereBelongsTo($candidate, 'candidate')->whereBelongsTo($job)->firstOrFail();
        $this->assertNotSame('resumes/profile.pdf', $application->resume_path);
        $this->assertSame('resumes/profile.pdf', $profile->fresh()->resume_path);
        Storage::disk('private')->assertExists($application->resume_path);
        Storage::disk('private')->assertExists('resumes/profile.pdf');
    }
}
