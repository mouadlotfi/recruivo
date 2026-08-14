<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InterviewModeTest extends TestCase
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

    public function test_application_casts_interview_mode(): void
    {
        $application = Application::factory()->create(['interview_mode' => 'online']);

        $this->assertSame('online', $application->interview_mode);
    }

    public function test_interview_mode_defaults_to_onsite_for_existing_rows(): void
    {
        $application = Application::factory()->create();

        $this->assertSame('onsite', $application->interview_mode);
    }

    public function test_online_interview_requires_meeting_url(): void
    {
        [$recruiter, $application] = $this->makeRecruiterWithApplication();

        $this->actingAs($recruiter)
            ->patch('/en/recruiter/applications/'.$application->id, [
                'status' => 'interview',
                'interview_mode' => 'online',
                'interview_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'interview_location' => 'Somewhere',
                'interview_url' => '',
            ])
            ->assertSessionHasErrors('interview_url');
    }

    public function test_onsite_interview_requires_location(): void
    {
        [$recruiter, $application] = $this->makeRecruiterWithApplication();

        $this->actingAs($recruiter)
            ->patch('/en/recruiter/applications/'.$application->id, [
                'status' => 'interview',
                'interview_mode' => 'onsite',
                'interview_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'interview_url' => 'https://meet.example.com/x',
                'interview_location' => '',
            ])
            ->assertSessionHasErrors('interview_location');
    }

    public function test_onsite_interview_accepts_url_as_location(): void
    {
        [$recruiter, $application] = $this->makeRecruiterWithApplication();

        $this->actingAs($recruiter)
            ->patch('/en/recruiter/applications/'.$application->id, [
                'status' => 'interview',
                'interview_mode' => 'onsite',
                'interview_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'interview_location' => 'https://maps.example.com/office',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_online_interview_requires_url_and_accepts_optional_location(): void
    {
        [$recruiter, $application] = $this->makeRecruiterWithApplication();

        $this->actingAs($recruiter)
            ->patch('/en/recruiter/applications/'.$application->id, [
                'status' => 'interview',
                'interview_mode' => 'online',
                'interview_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'interview_url' => 'https://meet.example.com/x',
                'interview_location' => '',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_notes_are_optional_when_accepting(): void
    {
        [$recruiter, $application] = $this->makeRecruiterWithApplication();

        $this->actingAs($recruiter)
            ->patch('/en/recruiter/applications/'.$application->id, [
                'status' => 'accepted',
                'notes' => '',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_review_panel_has_interview_mode_selector(): void
    {
        [$recruiter, $application] = $this->makeRecruiterWithApplication();

        $html = (string) $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$application->job_id.'/applications')
            ->getContent();

        $this->assertStringContainsString('name="interview_mode"', $html);
        $this->assertStringContainsString('value="onsite"', $html);
        $this->assertStringContainsString('value="online"', $html);
    }

    public function test_candidate_sees_online_interview_mode_with_link(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Interview->value,
            'interview_mode' => 'online',
            'interview_at' => now()->addDays(2),
            'interview_url' => 'https://meet.example.com/interview',
        ]);

        $html = (string) $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->getContent();

        $this->assertStringContainsString(__('applications.interview_online'), $html);
        $this->assertStringContainsString('meet.example.com', $html);
    }

    public function test_candidate_sees_onsite_interview_mode_with_location(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Interview->value,
            'interview_mode' => 'onsite',
            'interview_at' => now()->addDays(2),
            'interview_location' => 'Live HQ, Floor 3',
        ]);

        $html = (string) $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->getContent();

        $this->assertStringContainsString(__('applications.interview_onsite'), $html);
        $this->assertStringContainsString('Live HQ', $html);
    }

    public function test_api_resource_includes_interview_mode(): void
    {
        $application = Application::factory()->create(['interview_mode' => 'online']);

        $resource = (new \App\Http\Resources\ApplicationResource($application))->resolve(request());

        $this->assertSame('online', $resource['interview_mode']);
    }

    private function makeRecruiterWithApplication(): array
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');

        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
        ]);

        $application = Application::factory()->for($candidate, 'candidate')->for($job)->create();

        return [$recruiter, $application];
    }
}
