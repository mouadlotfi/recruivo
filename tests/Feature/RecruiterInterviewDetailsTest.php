<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationStatusUpdatedNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruiterInterviewDetailsTest extends TestCase
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

    private function makeInterviewContext(): array
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['title' => 'Backend Engineer']);
        $application = Application::factory()->for($candidate, 'candidate')->for($job)->create([
            'status' => ApplicationStatus::Pending->value,
        ]);

        return [$recruiter, $candidate, $application];
    }

    // Task 4: columns round-trip, interview_at casts to Carbon
    public function test_interview_fields_persist_and_interview_at_casts_to_carbon(): void
    {
        [, , $application] = $this->makeInterviewContext();

        $application->update([
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => '2026-09-01 10:00:00',
            'interview_location' => 'Casablanca HQ',
            'interview_url' => 'https://meet.example.com/abc',
            'interview_instructions' => 'Bring your laptop.',
        ]);

        $fresh = $application->fresh();

        $this->assertInstanceOf(Carbon::class, $fresh->interview_at);
        $this->assertSame('2026-09-01 10:00:00', $fresh->interview_at->format('Y-m-d H:i:s'));
        $this->assertSame('Casablanca HQ', $fresh->interview_location);
        $this->assertSame('https://meet.example.com/abc', $fresh->interview_url);
        $this->assertSame('Bring your laptop.', $fresh->interview_instructions);
    }

    // Task 5: interview requires a future date
    public function test_moving_to_interview_without_interview_at_fails_validation(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_location' => 'Casablanca HQ',
            ])
            ->assertSessionHasErrors('interview_at');
    }

    // Interview requires a location for onsite (default) or a URL for online
    public function test_moving_to_interview_requires_location_or_url(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('interview_location');

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'interview_mode' => 'online',
            ])
            ->assertSessionHasErrors('interview_url');
    }

    // Task 5: url must be http(s)
    public function test_moving_to_interview_rejects_malformed_or_non_http_url(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'interview_url' => 'ftp://files.example.com/meeting',
            ])
            ->assertSessionHasErrors('interview_url');

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'interview_url' => 'not a url',
            ])
            ->assertSessionHasErrors('interview_url');
    }

    // Task 5: date + location is accepted
    public function test_moving_to_interview_with_date_and_location_succeeds(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $interviewAt = now()->addDays(2)->format('Y-m-d H:i:s');

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_at' => $interviewAt,
                'interview_location' => 'Casablanca HQ, Room 4',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => $interviewAt,
            'interview_location' => 'Casablanca HQ, Room 4',
            'interview_url' => null,
        ]);
    }

    // Interview with date + online mode + https url is accepted
    public function test_moving_to_interview_with_date_and_https_url_succeeds(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $interviewAt = now()->addDays(2)->format('Y-m-d H:i:s');

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_mode' => 'online',
                'interview_at' => $interviewAt,
                'interview_url' => 'https://meet.example.com/interview',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => $interviewAt,
            'interview_url' => 'https://meet.example.com/interview',
            'interview_location' => null,
        ]);
    }

    // Task 5: instructions are optional and persist
    public function test_interview_instructions_are_optional_and_persist(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_mode' => 'online',
                'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'interview_url' => 'https://meet.example.com/interview',
                'interview_instructions' => 'Bring your laptop and be on time.',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'interview_instructions' => 'Bring your laptop and be on time.',
        ]);
    }

    // Task 5: moving away from interview clears all four interview fields
    public function test_moving_away_from_interview_clears_interview_fields(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $application->update([
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
            'interview_url' => 'https://meet.example.com/abc',
            'interview_instructions' => 'Bring your laptop.',
        ]);

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Pending->value,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'status' => ApplicationStatus::Pending->value,
            'interview_at' => null,
            'interview_location' => null,
            'interview_url' => null,
            'interview_instructions' => null,
        ]);
    }

    // Task 6: recruiter review form renders the interview fieldset with Alpine wiring
    public function test_recruiter_review_form_renders_interview_fields_with_alpine_wiring(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $response = $this->actingAs($recruiter)
            ->get("/en/recruiter/jobs/{$application->job_id}/applications");

        $html = (string) $response->getContent();

        $this->assertStringContainsString('x-data="{ status:', $html);
        $this->assertStringContainsString('x-model="status"', $html);
        $this->assertStringContainsString("x-show=\"status === 'interview'\"", $html);
        $this->assertStringContainsString('name="interview_at"', $html);
        $this->assertStringContainsString('type="datetime-local"', $html);
        $this->assertStringContainsString('name="interview_location"', $html);
        $this->assertStringContainsString('name="interview_url"', $html);
        $this->assertStringContainsString('type="url"', $html);
        $this->assertStringContainsString('name="interview_instructions"', $html);
    }

    // Task 7: candidate sees the interview panel only for scheduled interviews
    public function test_candidate_sees_interview_panel_with_details_only_for_scheduled_interviews(): void
    {
        [, $candidate, $application] = $this->makeInterviewContext();

        $interviewAt = now()->addDays(2);
        $application->update([
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => $interviewAt->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
            'interview_url' => 'https://meet.example.com/interview',
            'interview_instructions' => "Bring your laptop.\nBe on time.",
        ]);

        $otherJob = Job::factory()->create(['title' => 'Other Position']);
        Application::factory()->for($candidate, 'candidate')->for($otherJob)->create([
            'status' => ApplicationStatus::Pending->value,
        ]);

        $response = $this->actingAs($candidate)->get('/en/candidate/applications');
        $html = (string) $response->getContent();

        $this->assertSame(1, substr_count($html, __('applications.interview_scheduled')));
        $this->assertStringContainsString($interviewAt->format('l, F j, Y \a\t g:i A'), $html);
        $this->assertStringContainsString('Casablanca HQ', $html);
        $this->assertStringContainsString('https://meet.example.com/interview', $html);
        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $html);
        $this->assertStringContainsString("Bring your laptop.\nBe on time.", $html);
    }

    // Task 7: notification carries interview details in mail + stored array
    public function test_interview_notification_contains_interview_details_in_mail_and_array(): void
    {
        [, $candidate, $application] = $this->makeInterviewContext();

        $interviewAt = now()->addDays(2);
        $application->update([
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => $interviewAt->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
            'interview_url' => 'https://meet.example.com/interview',
            'interview_instructions' => 'Bring your laptop.',
        ]);

        $candidate->notify(new ApplicationStatusUpdatedNotification($application));

        $stored = $candidate->fresh()->notifications()->first()->data;
        $this->assertSame('interview', $stored['status']);
        $this->assertSame($interviewAt->format('Y-m-d H:i'), $stored['interview_at']);
        $this->assertSame('Casablanca HQ', $stored['interview_location']);
        $this->assertSame('https://meet.example.com/interview', $stored['interview_url']);
        $this->assertSame('Bring your laptop.', $stored['interview_instructions']);

        $mail = (new ApplicationStatusUpdatedNotification($application))->toMail($candidate);
        $lines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('Interview scheduled for', $lines);
        $this->assertStringContainsString('Location: Casablanca HQ', $lines);
        $this->assertStringContainsString('Meeting link: https://meet.example.com/interview', $lines);
        $this->assertStringContainsString('Bring your laptop.', $lines);
    }

    // Task 7: notification center derives the title from the stored status
    public function test_notification_center_renders_interview_title_for_interview_updates(): void
    {
        [, $candidate, $application] = $this->makeInterviewContext();

        $application->update([
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
        ]);

        $candidate->notify(new ApplicationStatusUpdatedNotification($application));

        $this->actingAs($candidate)
            ->get('/en/candidate/dashboard')
            ->assertOk()
            ->assertSee(__('common.application_interview'))
            ->assertDontSee(__('common.application_rejected'));
    }

    // Task 5: the after:now rule rejects a past interview date
    public function test_moving_to_interview_with_a_past_date_fails_validation(): void
    {
        [$recruiter, , $application] = $this->makeInterviewContext();

        $this->actingAs($recruiter)
            ->patch("/en/recruiter/applications/{$application->id}", [
                'status' => ApplicationStatus::Interview->value,
                'interview_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'interview_location' => 'Casablanca HQ',
            ])
            ->assertSessionHasErrors('interview_at');
    }

    // Task 5: an update that does not change the status (notes-only payload, or
    // same-status re-PATCH) must NOT clear existing interview details.
    public function test_notes_only_update_on_an_interview_application_preserves_interview_details(): void
    {
        [, , $application] = $this->makeInterviewContext();

        $application->update([
            'status' => ApplicationStatus::Interview->value,
            'interview_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'interview_location' => 'Casablanca HQ',
            'interview_url' => 'https://meet.example.com/interview',
            'interview_instructions' => 'Bring your laptop.',
        ]);

        $application->applyStatusUpdate(['notes' => 'A quick note without a status change.']);

        $fresh = $application->fresh();
        $this->assertSame(ApplicationStatus::Interview->value, $fresh->status->value);
        $this->assertNotNull($fresh->interview_at);
        $this->assertSame('Casablanca HQ', $fresh->interview_location);
        $this->assertSame('https://meet.example.com/interview', $fresh->interview_url);
        $this->assertSame('Bring your laptop.', $fresh->interview_instructions);

        // Same-status re-PATCH keeps interview data too.
        $application->applyStatusUpdate(['status' => ApplicationStatus::Interview->value]);
        $this->assertSame('Casablanca HQ', $application->fresh()->interview_location);
    }
}
