<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationStatusTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_application_has_ordered_status_events_relationship(): void
    {
        $application = Application::factory()->create();

        $this->assertCount(1, $application->statusEvents);
        $this->assertSame('pending', $application->statusEvents->first()->to_status);
    }

    public function test_deleting_application_deletes_status_events(): void
    {
        $application = Application::factory()->create();

        $application->delete();

        $this->assertDatabaseMissing('application_status_events', ['application_id' => $application->id]);
    }

    public function test_events_are_ordered_oldest_first(): void
    {
        $application = Application::factory()->create();

        $application->statusEvents()->create([
            'changed_by_user_id' => null,
            'from_status' => 'pending',
            'to_status' => 'shortlisted',
            'created_at' => now()->addHour(),
        ]);

        $application->refresh();

        $this->assertSame(
            ['pending', 'shortlisted'],
            $application->statusEvents()->pluck('to_status')->all()
        );
    }

    public function test_events_with_same_created_at_are_ordered_by_id(): void
    {
        $application = Application::factory()->create();

        $application->statusEvents()->create([
            'changed_by_user_id' => null,
            'from_status' => 'pending',
            'to_status' => 'shortlisted',
            'created_at' => now(),
        ]);

        $application->refresh();

        $this->assertSame(
            ['pending', 'shortlisted'],
            $application->statusEvents()->pluck('to_status')->all()
        );
    }

    public function test_creating_application_records_one_initial_event(): void
    {
        $application = Application::factory()->create();

        $events = $application->statusEvents()->orderBy('id')->get();

        $this->assertCount(1, $events);
        $this->assertNull($events->first()->from_status);
        $this->assertSame('pending', $events->first()->to_status);
    }

    public function test_status_change_appends_second_event(): void
    {
        $application = Application::factory()->create();

        $application->applyStatusUpdate(['status' => ApplicationStatus::Shortlisted->value]);

        $events = $application->statusEvents()->orderBy('id')->get();

        $this->assertCount(2, $events);
        $this->assertSame('pending', $events[0]->to_status);
        $this->assertSame('shortlisted', $events[1]->to_status);
        $this->assertSame('pending', $events[1]->from_status);
    }

    public function test_saving_same_status_does_not_append_event(): void
    {
        $application = Application::factory()->create();

        $application->applyStatusUpdate(['status' => ApplicationStatus::Pending->value]);

        $this->assertCount(1, $application->statusEvents()->get());
    }

    public function test_notes_only_update_does_not_append_event(): void
    {
        $application = Application::factory()->create();

        $application->applyStatusUpdate(['notes' => 'A note without status change']);

        $this->assertCount(1, $application->statusEvents()->get());
    }

    public function test_recruiter_status_change_records_actor(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $application = Application::factory()->create();

        $this->actingAs($recruiter);
        $application->applyStatusUpdate(['status' => ApplicationStatus::Interview->value]);

        $lastEvent = $application->statusEvents()->orderBy('id')->get()->last();
        $this->assertSame('interview', $lastEvent->to_status);
        $this->assertSame($recruiter->id, $lastEvent->changed_by_user_id);
    }

    public function test_status_change_without_authenticated_user_still_records_event(): void
    {
        $application = Application::factory()->create();

        $application->applyStatusUpdate(['status' => ApplicationStatus::Rejected->value, 'notes' => 'Decision']);

        $lastEvent = $application->statusEvents()->orderBy('id')->get()->last();
        $this->assertSame('rejected', $lastEvent->to_status);
        $this->assertNull($lastEvent->changed_by_user_id);
    }

    public function test_api_application_resource_exposes_status_history_when_loaded(): void
    {
        $application = Application::factory()->create();
        $application->applyStatusUpdate(['status' => ApplicationStatus::Shortlisted->value]);
        $application->load('statusEvents');

        $resource = (new \App\Http\Resources\ApplicationResource($application))->resolve(request());

        $this->assertArrayHasKey('status_history', $resource);
        $this->assertCount(2, $resource['status_history']);
        $this->assertSame('pending', $resource['status_history'][0]['to_status']);
        $this->assertSame('shortlisted', $resource['status_history'][1]['to_status']);
        $this->assertNull($resource['status_history'][0]['from_status']);
        $this->assertArrayNotHasKey('email', $resource['status_history'][0]);
    }

    public function test_candidate_application_page_renders_status_timeline_in_order(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $application = Application::factory()->for($candidate, 'candidate')->create();
        $application->applyStatusUpdate(['status' => ApplicationStatus::Shortlisted->value]);
        $application->applyStatusUpdate(['status' => ApplicationStatus::Interview->value]);

        $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->assertOk()
            ->assertSee('data-application-status-timeline', false);
    }

    public function test_recruiter_application_page_renders_status_timeline(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['status' => JobStatus::Published->value]);
        $application = Application::factory()->for($job)->create();
        $application->applyStatusUpdate(['status' => ApplicationStatus::Shortlisted->value]);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->assertOk()
            ->assertSee('data-application-status-timeline', false);
    }

    public function test_timeline_renders_translation_backed_labels_not_raw_statuses(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $application = Application::factory()->for($candidate, 'candidate')->create();
        $application->applyStatusUpdate(['status' => ApplicationStatus::Shortlisted->value]);

        $html = (string) $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->getContent();

        $this->assertStringContainsString(__('applications.status_pending'), $html);
        $this->assertStringContainsString(__('applications.status_shortlisted'), $html);
        $this->assertStringNotContainsString('data-status="shortlisted"', $html); // no raw leak through data attributes if we avoid them
    }
}
