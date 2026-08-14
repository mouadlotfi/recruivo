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

    public function test_notes_are_optional_when_accepting(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Accepted->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Application updated successfully.');
        $response->assertJsonPath('data.status', ApplicationStatus::Accepted->value);
        $response->assertJsonPath('data.status_changed', true);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Accepted->value,
            'notes' => null,
            'status_changed' => true,
            'notes_added' => false,
        ]);

        Notification::assertSentTo($candidate, ApplicationStatusUpdatedNotification::class);
    }

    public function test_recruiter_can_change_status_once_with_note_and_notifies_candidate(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Accepted->value,
            'notes' => 'The candidate has been accepted.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Application updated successfully.');
        $response->assertJsonPath('data.status', ApplicationStatus::Accepted->value);
        $response->assertJsonPath('data.notes', 'The candidate has been accepted.');
        $response->assertJsonPath('data.status_changed', true);
        $response->assertJsonPath('data.notes_added', true);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Accepted->value,
            'notes' => 'The candidate has been accepted.',
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
            'status' => ApplicationStatus::Accepted->value,
            'notes' => 'The candidate has been accepted.',
        ])->assertOk();

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Rejected->value,
            'notes' => 'The decision changed.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
        $response->assertJsonPath('errors.status.0', 'Once an application is accepted, you cannot change the decision.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Accepted->value,
            'notes' => 'The candidate has been accepted.',
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
            'status' => ApplicationStatus::Rejected->value,
            'notes' => 'The candidate was not selected.',
        ])->assertOk();

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Rejected->value,
            'notes' => 'Adding another note after the fact.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('notes');
        $response->assertJsonPath('errors.notes.0', 'Notes have already been added and cannot be modified.');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'notes' => 'The candidate was not selected.',
            'notes_added' => true,
        ]);
    }

    // Contract: enum order matches recruiting pipeline, Withdrawn appended at the end
    public function test_application_statuses_include_the_recruiting_pipeline(): void
    {
        $this->assertSame(
            ['pending', 'shortlisted', 'interview', 'accepted', 'rejected', 'withdrawn'],
            array_map(fn (ApplicationStatus $status) => $status->value, ApplicationStatus::cases()),
        );
    }

    // Negative bookkeeping: workflow stages must NOT set final-decision flags
    public function test_workflow_stage_moves_do_not_mark_final_decision_bookkeeping(): void
    {
        Notification::fake();
        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();
        Sanctum::actingAs($recruiter);

        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ])->assertOk();

        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
        ])->assertOk();

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Interview->value,
            'status_changed' => false,
            'status_changed_at' => null,
        ]);
    }

    // Task 1: shortlisted and interview exist as valid statuses
    public function test_recruiter_can_change_status_to_shortlisted(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', ApplicationStatus::Shortlisted->value);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Shortlisted->value,
        ]);
    }

    public function test_recruiter_can_change_status_to_interview(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', ApplicationStatus::Interview->value);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Interview->value,
        ]);
    }

    // Task 1: shortlisted/interview don't require notes (non-final statuses)
    public function test_notes_not_required_for_shortlisted_or_interview(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        // Shortlisted without notes
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ]);
        $response->assertOk();

        // Interview without notes (from shortlisted)
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
        ]);
        $response->assertOk();
        $response->assertJsonPath('data.status', ApplicationStatus::Interview->value);
    }

    // Task 2: reversible transitions between pending, shortlisted, interview
    public function test_can_move_from_shortlisted_back_to_pending(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        // Pending -> Shortlisted
        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ])->assertOk();

        // Shortlisted -> Pending (reversible)
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Pending->value,
        ]);
        $response->assertOk();
        $response->assertJsonPath('data.status', ApplicationStatus::Pending->value);
    }

    public function test_can_move_from_interview_back_to_shortlisted(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        // Pending -> Shortlisted -> Interview
        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ])->assertOk();

        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
        ])->assertOk();

        // Interview -> Shortlisted (reversible)
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ]);
        $response->assertOk();
        $response->assertJsonPath('data.status', ApplicationStatus::Shortlisted->value);
    }

    public function test_can_move_from_interview_back_to_pending(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        // Pending -> Shortlisted -> Interview
        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ])->assertOk();

        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
        ])->assertOk();

        // Interview -> Pending (reversible)
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Pending->value,
        ]);
        $response->assertOk();
        $response->assertJsonPath('data.status', ApplicationStatus::Pending->value);
    }

    // Task 2: Accepted/Rejected remain final — cannot leave them
    public function test_cannot_change_from_accepted_to_anything(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        // Accept first
        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Accepted->value,
            'notes' => 'Accepted',
        ])->assertOk();

        // Try to change to pending
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Pending->value,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');

        // Try to change to shortlisted
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');

        // Try to change to interview
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_cannot_change_from_rejected_to_anything(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        // Reject first
        $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Rejected->value,
            'notes' => 'Rejected',
        ])->assertOk();

        // Try to change to pending
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Pending->value,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');

        // Try to change to shortlisted
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    // Task 2: notes cannot be added for non-final statuses
    public function test_notes_cannot_be_added_for_shortlisted_or_interview(): void
    {
        Notification::fake();

        [$recruiter, $candidate, $application] = $this->createApplicationForRecruiter();

        Sanctum::actingAs($recruiter);

        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Shortlisted->value,
            'notes' => 'Some note',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('notes');

        // Same rejection for interview
        $response = $this->patchJson("/api/recruiter/applications/{$application->id}", [
            'status' => ApplicationStatus::Interview->value,
            'notes' => 'Some note',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('notes');
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
