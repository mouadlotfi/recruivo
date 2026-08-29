<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter', 'Admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_candidate_cannot_view_another_candidates_resume(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('resumes/target_resume.pdf', 'private content');

        $candidate1 = User::factory()->create(['email_verified_at' => now()]);
        $candidate1->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate1, 'user')->create([
            'resume_path' => 'resumes/target_resume.pdf',
        ]);

        $candidate2 = User::factory()->create(['email_verified_at' => now()]);
        $candidate2->assignRole('Candidate');

        // Candidate 2 accessing their own resume endpoint cannot see Candidate 1's resume
        $this->actingAs($candidate2)->get('/en/candidate/resume')->assertNotFound();
    }

    public function test_recruiter_cannot_download_resumes_for_other_companies_jobs(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('resumes/applicant.pdf', 'resume content');

        $companyA = Company::factory()->create();
        $recruiterA = User::factory()->for($companyA)->create(['email_verified_at' => now(), 'is_recruiter' => true]);
        $recruiterA->assignRole('Recruiter');

        $companyB = Company::factory()->create();
        $recruiterB = User::factory()->for($companyB)->create(['email_verified_at' => now(), 'is_recruiter' => true]);
        $recruiterB->assignRole('Recruiter');

        $jobB = Job::factory()->for($companyB)->for($recruiterB, 'recruiter')->create();
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $applicant->assignRole('Candidate');

        $applicationB = Application::factory()->for($applicant, 'candidate')->for($jobB)->create([
            'resume_path' => 'resumes/applicant.pdf',
        ]);

        // Recruiter A attempts to download applicant for Company B's job via web
        $this->actingAs($recruiterA)
            ->get("/en/recruiter/applications/{$applicationB->id}/resume")
            ->assertForbidden();

        // Recruiter A attempts to download via API
        Sanctum::actingAs($recruiterA);
        $this->getJson("/api/recruiter/applications/{$applicationB->id}/resume")
            ->assertForbidden();
    }

    public function test_api_recruiter_can_download_application_resume(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('resumes/app_resume.pdf', 'resume content');

        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now(), 'is_recruiter' => true]);
        $recruiter->assignRole('Recruiter');

        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();
        $applicant = User::factory()->create(['email_verified_at' => now()]);
        $applicant->assignRole('Candidate');

        $application = Application::factory()->for($applicant, 'candidate')->for($job)->create([
            'resume_path' => 'resumes/app_resume.pdf',
        ]);

        Sanctum::actingAs($recruiter);
        $response = $this->getJson("/api/recruiter/applications/{$application->id}/resume");

        $response->assertOk();
    }

    public function test_health_check_endpoint_returns_healthy_json(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJson([
                'status' => 'healthy',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                    'storage' => 'ok',
                ],
            ]);
    }
}
