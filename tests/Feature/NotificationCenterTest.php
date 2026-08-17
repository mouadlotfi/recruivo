<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationStatusUpdatedNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_recruiter_sees_a_navbar_notification_when_a_candidate_applies(): void
    {
        $company = Company::factory()->create(['name' => 'Northstar Labs']);
        $recruiter = User::factory()->create([
            'company_id' => $company->id,
            'is_recruiter' => true,
            'email_verified_at' => now(),
        ]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create([
            'name' => 'Ada Candidate',
            'email_verified_at' => now(),
        ]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/ada.pdf']);
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'recruiter_id' => $recruiter->id,
            'title' => 'Platform Engineer',
            'status' => JobStatus::Published->value,
        ]);

        $this->actingAs($candidate)
            ->post("/en/jobs/{$job->id}/apply", [
                'cover_letter' => 'I would like to join the platform team.',
                'resume_source' => 'profile',
            ])
            ->assertSessionHas('success');

        $this->assertCount(1, $recruiter->fresh()->unreadNotifications);

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/dashboard')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Dashboard', false)
            ->where('notificationCount', 1)
        );

        $notification = File::get(resource_path('js/Components/Layout/NotificationCenter.vue'));
        $this->assertStringContainsString('data-notification-center', $notification);
        $this->assertStringContainsString('unreadCount', $notification);
    }

    public function test_candidate_sees_accepted_or_rejected_application_updates_in_the_navbar(): void
    {
        $company = Company::factory()->create(['name' => 'Northstar Labs']);
        $recruiter = User::factory()->create([
            'company_id' => $company->id,
            'is_recruiter' => true,
            'email_verified_at' => now(),
        ]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create([
            'name' => 'Ada Candidate',
            'email_verified_at' => now(),
        ]);
        $candidate->assignRole('Candidate');
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'recruiter_id' => $recruiter->id,
            'title' => 'Platform Engineer',
        ]);
        $application = Application::factory()
            ->for($candidate, 'candidate')
            ->for($job)
            ->create(['status' => ApplicationStatus::Pending->value]);

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Accepted->value,
                'notes' => 'We would like to move forward.',
            ])
            ->assertSessionHas('success');

        $this->assertCount(1, $candidate->fresh()->unreadNotifications);

        $response = $this->actingAs($candidate)
            ->get('/en/candidate/dashboard')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Candidate/Dashboard', false)
            ->where('notificationCount', 1)
        );

        $notification = File::get(resource_path('js/Components/Layout/NotificationCenter.vue'));
        $this->assertStringContainsString('data-notification-center', $notification);
        $this->assertStringContainsString('unreadCount', $notification);
    }

    public function test_user_can_open_their_notification_and_it_is_marked_as_read(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->create([
            'company_id' => $company->id,
            'is_recruiter' => true,
        ]);
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        $job = Job::factory()->create([
            'company_id' => $company->id,
            'recruiter_id' => $recruiter->id,
        ]);
        $application = Application::factory()
            ->for($candidate, 'candidate')
            ->for($job)
            ->create(['status' => ApplicationStatus::Accepted->value]);
        $candidate->notify(new ApplicationStatusUpdatedNotification($application));

        $notification = $candidate->fresh()->unreadNotifications->first();

        $this->actingAs($candidate)
            ->post("/en/notifications/{$notification->id}")
            ->assertRedirect('/en/candidate/applications');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        $otherCandidate = User::factory()->create(['email_verified_at' => now()]);
        $otherCandidate->assignRole('Candidate');
        $job = Job::factory()->create();
        $application = Application::factory()
            ->for($otherCandidate, 'candidate')
            ->for($job)
            ->create(['status' => ApplicationStatus::Rejected->value]);
        $otherCandidate->notify(new ApplicationStatusUpdatedNotification($application));
        $notification = $otherCandidate->fresh()->unreadNotifications->first();

        $this->actingAs($candidate)
            ->post("/en/notifications/{$notification->id}")
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_of_their_notifications_as_read(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        foreach ([ApplicationStatus::Accepted, ApplicationStatus::Rejected] as $status) {
            $job = Job::factory()->create();
            $application = Application::factory()
                ->for($candidate, 'candidate')
                ->for($job)
                ->create(['status' => $status->value]);
            $candidate->notify(new ApplicationStatusUpdatedNotification($application));
        }

        $this->assertCount(2, $candidate->fresh()->unreadNotifications);

        $this->actingAs($candidate)
            ->post('/en/notifications/read-all')
            ->assertRedirect();

        $this->assertCount(0, $candidate->fresh()->unreadNotifications);
    }

    public function test_workflow_stage_updates_serialize_and_render_for_shortlisted_and_interview(): void
    {
        $company = Company::factory()->create(['name' => 'Northstar Labs']);
        $recruiter = User::factory()->create([
            'company_id' => $company->id,
            'is_recruiter' => true,
            'email_verified_at' => now(),
        ]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');

        foreach ([ApplicationStatus::Shortlisted, ApplicationStatus::Interview] as $status) {
            $job = Job::factory()->create([
                'company_id' => $company->id,
                'recruiter_id' => $recruiter->id,
                'title' => 'Platform Engineer',
                'status' => JobStatus::Published->value,
            ]);

            $application = Application::factory()
                ->for($candidate, 'candidate')
                ->for($job)
                ->create(['status' => $status->value]);

            $candidate->notify(new ApplicationStatusUpdatedNotification($application));

            $mail = (new ApplicationStatusUpdatedNotification($application))->toMail($candidate);
            $this->assertStringContainsString(ucfirst($status->value), implode(' ', $mail->introLines));
            $this->assertStringContainsString('candidate/applications', $mail->actionUrl);
        }

        $storedStatuses = DB::table('notifications')
            ->where('type', ApplicationStatusUpdatedNotification::class)
            ->where('notifiable_id', $candidate->id)
            ->pluck('data')
            ->map(fn ($data) => json_decode($data, true)['status'])
            ->sort()
            ->values()
            ->all();

        $this->assertEquals(['interview', 'shortlisted'], $storedStatuses);
    }
}
