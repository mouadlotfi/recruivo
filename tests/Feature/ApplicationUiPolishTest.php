<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
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

        $this->actingAs($candidate)
            ->get('/en/candidate/applications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Candidate/Applications', false)
                ->where('applications.0.cover_letter', $application->cover_letter)
            );

        $card = File::get(resource_path('js/Components/Applications/CandidateApplicationCard.vue'));
        $disclosure = File::get(resource_path('js/Components/Applications/CoverLetterDisclosure.vue'));

        $this->assertStringContainsString('<details data-application-card-collapsible', $card);
        $this->assertStringNotContainsString('<details data-application-card-collapsible open', $card);
        $this->assertStringContainsString('<details data-cover-letter-collapsible', $disclosure);
        $this->assertStringNotContainsString('<details data-cover-letter-collapsible open', $disclosure);
    }

    public function test_recruiter_application_card_is_collapsed_by_default(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['status' => JobStatus::Published->value]);
        $application = Application::factory()->for($job)->create([
            'cover_letter' => 'A cover letter for the collapsed card test.',
        ]);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Applications/Index', false)
                ->where('applications.0.cover_letter', $application->cover_letter)
            );

        $card = File::get(resource_path('js/Components/Applications/RecruiterApplicationCard.vue'));
        $disclosure = File::get(resource_path('js/Components/Applications/CoverLetterDisclosure.vue'));

        $this->assertStringContainsString('<details data-application-card-collapsible', $card);
        $this->assertStringContainsString(':open="hasPageErrors"', $card);
        $this->assertStringContainsString('data-application-review-panel', $card);
        $this->assertStringContainsString('<details data-cover-letter-collapsible', $disclosure);
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

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Applications/Index', false)
                ->where('applications.0.status', 'shortlisted')
                ->where('applications.0.notes', $application->notes)
            );

        $review = File::get(resource_path('js/Components/Applications/ApplicationReviewPanel.vue'));
        $this->assertStringContainsString("status: ''", $review);
        $this->assertStringContainsString("notes: ''", $review);
        $this->assertStringContainsString("interview_at: ''", $review);
        $this->assertStringNotContainsString('application.interview_at', $review);
        $this->assertStringNotContainsString('application.interview_location', $review);
    }

    public function test_apply_cover_letter_textarea_is_autosize_enabled(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/r.pdf']);
        $job = Job::factory()->create(['status' => JobStatus::Published->value]);

        $this->actingAs($candidate)
            ->get('/en/jobs/'.$job->id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Jobs/Show', false)
                ->where('job.id', $job->id)
                ->where('canApply', true)
            );

        $show = File::get(resource_path('js/Pages/Jobs/Show.vue'));
        $this->assertStringContainsString('<ExpandedTextarea', $show);
        $this->assertStringContainsString('name="cover_letter"', $show);
        $this->assertStringContainsString('rows="4"', $show);
    }

    public function test_recruiter_notes_textarea_is_autosize_enabled(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['status' => JobStatus::Published->value]);
        Application::factory()->for($job)->create();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Applications/Index', false)
                ->has('applications', 1)
            );

        $review = File::get(resource_path('js/Components/Applications/ApplicationReviewPanel.vue'));
        $this->assertStringContainsString('name="notes"', $review);
        $this->assertStringContainsString('const autosizeNotes', $review);
        $this->assertStringContainsString('@input="autosizeNotes"', $review);
    }

    public function test_company_location_links_to_location_search(): void
    {
        $company = Company::factory()->create([
            'name' => 'Linked Location Co',
            'slug' => 'linked-location-co',
            'location' => 'Dublin, Ireland',
        ]);

        $this->get('/en/companies')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Companies/Index', false)
                ->where('companies.0.location', 'Dublin, Ireland')
            );

        $companyCard = File::get(resource_path('js/Components/Companies/CompanyCard.vue'));
        $this->assertStringContainsString('data-company-location-link', $companyCard);
        $this->assertStringContainsString(':data="{ location: company.location, filter: \'jobs\' }"', $companyCard);
    }
}
