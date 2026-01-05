<?php

namespace Tests\Feature\Api\Recruiter;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_recruiter_must_include_note_when_changing_status(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Review->value,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('notes');
        $response->assertJsonPath('errors.notes.0', 'Please include a note when you change the application status.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Pending->value,
            'notes' => null,
            'status_changed' => false,
            'notes_added' => false,
        ]);
    }

    public function test_recruiter_can_change_status_once_with_note_and_notifies_candidate(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Review->value,
            'notes' => 'Moving the candidate to review.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Application updated successfully.');
        $response->assertJsonPath('data.status', ApplicationStatus::Review->value);
        $response->assertJsonPath('data.notes', 'Moving the candidate to review.');
        $response->assertJsonPath('data.status_changed', true);
        $response->assertJsonPath('data.notes_added', true);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Review->value,
            'notes' => 'Moving the candidate to review.',
            'status_changed' => true,
            'notes_added' => true,
        ]);

        Notification::assertSentTo($candidate, ApplicationStatusUpdatedNotification::class);
    }

    public function test_recruiter_cannot_change_status_more_than_once(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Review->value,
            'notes' => 'Initial review in progress.',
        ])->assertOk();

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Hired->value,
            'notes' => 'Candidate hired.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
        $response->assertJsonPath('errors.status.0', 'Application status can only be changed once.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Review->value,
            'notes' => 'Initial review in progress.',
            'status_changed' => true,
        ]);

        Notification::assertSentToTimes($candidate, ApplicationStatusUpdatedNotification::class, 1);
    }

    public function test_notes_cannot_be_added_after_status_change(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'notes' => 'Inviting the candidate to interview.',
        ])->assertOk();

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'notes' => 'Adding another note after the fact.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('notes');
        $response->assertJsonPath('errors.notes.0', 'Notes can only be added before status is changed and only once per application.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'notes' => 'Inviting the candidate to interview.',
            'notes_added' => true,
        ]);
    }

    private function createApplicationForRecruiter(): array
    {
        $recruiter = User::factory()->create();
        $recruiter->assignRole('Recruiter');

        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $job = Job::factory()
            ->for($recruiter, 'recruiter')
            ->create([
                'status' => JobStatus::Published->value,
            ]);

        $application = Application::factory()
            ->for($candidate, 'candidate')
            ->for($job, 'job')
            ->create([
                'status' => ApplicationStatus::Pending->value,
                'original_status' => ApplicationStatus::Pending->value,
                'notes' => null,
                'status_changed' => false,
                'notes_added' => false,
            ]);

        return [$recruiter, $candidate, $application];
    }
}
