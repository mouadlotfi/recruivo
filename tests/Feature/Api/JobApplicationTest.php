<?php

namespace Tests\Feature\Api;

use App\Enums\JobStatus;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_candidate_can_apply_to_published_job_with_saved_resume(): void
    {
        Notification::fake();

        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        CandidateProfile::factory()->for($candidate)->create([
            'resume_path' => 'resumes/profile.pdf',
        ]);

        $job = Job::factory()->create([
            'status' => JobStatus::Published->value,
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson(route('api.jobs.apply', $job), [
            'cover_letter' => 'I would love to join the team.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Application submitted successfully.');
        $response->assertJsonPath('data.candidate.id', $candidate->id);
        $response->assertJsonPath('data.job.id', $job->id);
        $response->assertJsonPath('data.cover_letter', 'I would love to join the team.');

        $this->assertDatabaseHas('applications', [
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'cover_letter' => 'I would love to join the team.',
        ]);

        Notification::assertSentTo(
            $job->recruiter,
            NewApplicationNotification::class
        );
    }

    public function test_candidate_without_resume_receives_validation_error(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $job = Job::factory()->create([
            'status' => JobStatus::Published->value,
        ]);

        Sanctum::actingAs($candidate);

        $response = $this->postJson(route('api.jobs.apply', $job));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('resume');
    }

    public function test_non_candidate_cannot_apply_to_job(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Recruiter');

        $job = Job::factory()->create([
            'status' => JobStatus::Published->value,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.jobs.apply', $job), [
            'cover_letter' => 'Interested in the role.',
        ]);

        $response->assertForbidden();
    }
}
