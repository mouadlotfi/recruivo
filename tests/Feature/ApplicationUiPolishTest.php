<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicationUiPolishTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_candidate_cover_letter_is_collapsed_by_default(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'cover_letter' => 'A fairly long cover letter for the candidate view.',
        ]);

        $html = (string) $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->getContent();

        $this->assertStringContainsString('data-cover-letter-collapsible', $html);
        // No `open` attribute => collapsed by default
        $this->assertStringNotContainsString('<details data-cover-letter-collapsible open', $html);
    }

    public function test_recruiter_application_card_is_collapsed_by_default(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['status' => JobStatus::Published->value]);
        Application::factory()->for($job)->create([
            'cover_letter' => 'A cover letter for the collapsed card test.',
        ]);

        $html = (string) $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->getContent();

        $this->assertStringContainsString('data-application-card-collapsible', $html);
        // No `open` attribute => collapsed by default
        $this->assertStringNotContainsString('<details data-application-card-collapsible open', $html);
        // The review panel lives inside the collapsed card
        $this->assertStringContainsString('data-application-review-panel', $html);
        // Cover letter stays collapsible within the card
        $this->assertStringContainsString('data-cover-letter-collapsible', $html);
    }

    public function test_recruiter_review_form_starts_at_default_state(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['status' => JobStatus::Published->value]);
        $application = Application::factory()->for($job)->create([
            'status' => 'shortlisted',
            'notes' => 'Existing recruiter notes',
            'cover_letter' => 'A cover letter.',
            'interview_at' => now()->addDays(2),
            'interview_location' => 'Paris Office',
        ]);

        $html = (string) $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->getContent();

        // Form must NOT pre-select the application's current status
        $this->assertStringContainsString("status: ''", $html);
        // Notes field starts empty, not pre-filled with existing notes
        $this->assertStringContainsString("notes: ''", $html);
        // Interview fields are not pre-filled from the application
        $this->assertStringNotContainsString('value="'.now()->addDays(2)->format('Y-m-d\TH:i').'"', $html);
        $this->assertStringNotContainsString('value="Paris Office"', $html);
    }

    public function test_apply_cover_letter_textarea_is_autosize_enabled(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        \App\Models\CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/r.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);

        $html = (string) $this->actingAs($candidate)->get('/en/jobs/'.$job->id)->getContent();

        $this->assertStringContainsString('x-autosize', $html);
        $this->assertStringContainsString('name="cover_letter"', $html);
    }

    public function test_recruiter_notes_textarea_is_autosize_enabled(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['status' => JobStatus::Published->value]);
        Application::factory()->for($job)->create();

        $html = (string) $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->getContent();

        $this->assertStringContainsString('x-autosize', $html);
        $this->assertStringContainsString('name="notes"', $html);
    }

    public function test_company_location_links_to_location_search(): void
    {
        $company = Company::factory()->create([
            'name' => 'Linked Location Co',
            'slug' => 'linked-location-co',
            'location' => 'Dublin, Ireland',
        ]);

        $html = (string) $this->get('/en/companies')->getContent();

        $this->assertStringContainsString('data-company-location-link', $html);
        $this->assertStringContainsString('/en/search?location=Dublin%2C%20Ireland&amp;filter=jobs', $html);
    }
}
