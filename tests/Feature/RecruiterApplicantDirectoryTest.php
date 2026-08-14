<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruiterApplicantDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_recruiter_sees_applicants_and_the_jobs_they_applied_to(): void
    {
        [$recruiter, $candidate, $firstJob] = $this->makeApplication();
        $secondJob = Job::factory()->for($recruiter->company)->for($recruiter, 'recruiter')->create(['title' => 'Platform Engineer']);
        Application::factory()->for($candidate, 'candidate')->for($secondJob)->create();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/applicants')
            ->assertOk()
            ->assertSee($candidate->name)
            ->assertSee($firstJob->title)
            ->assertSee('Platform Engineer')
            ->assertSee('/en/recruiter/applicants/'.$candidate->id, false);
    }

    public function test_recruiter_can_view_an_applicant_profile_and_application_history(): void
    {
        [$recruiter, $candidate, $job] = $this->makeApplication();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/applicants/'.$candidate->id)
            ->assertOk()
            ->assertSee($candidate->name)
            ->assertSee($candidate->email)
            ->assertSee($job->title);
    }

    public function test_recruiter_cannot_view_candidates_who_did_not_apply_to_their_jobs(): void
    {
        [$recruiter] = $this->makeApplication();
        $unrelatedCandidate = User::factory()->create();
        $unrelatedCandidate->assignRole('Candidate');

        $this->actingAs($recruiter)
            ->get('/en/recruiter/applicants/'.$unrelatedCandidate->id)
            ->assertNotFound();
    }

    public function test_job_applications_can_be_filtered_by_status_tabs(): void
    {
        [$recruiter, $pendingCandidate, $job] = $this->makeApplication();
        $acceptedCandidate = User::factory()->create(['name' => 'Accepted Candidate']);
        $acceptedCandidate->assignRole('Candidate');
        Application::factory()->for($acceptedCandidate, 'candidate')->for($job)->create([
            'status' => ApplicationStatus::Accepted->value,
        ]);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications?status=accepted')
            ->assertOk()
            ->assertSee('data-application-status-tabs', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Accepted Candidate')
            ->assertDontSee($pendingCandidate->name);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id.'/applications?status=invalid')
            ->assertRedirect('/en/recruiter/jobs/'.$job->id.'/applications');
    }

    public function test_application_review_controls_use_responsive_non_native_select_layout(): void
    {
        $view = file_get_contents(resource_path('views/recruiter/applications/index.blade.php'));

        $this->assertStringContainsString('data-application-review-panel', $view);
        $this->assertStringContainsString('appearance-none', $view);
        $this->assertStringContainsString('pointer-events-none', $view);
        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1fr)_18rem]', $view);
    }

    public function test_recruiter_views_application_resume_inline_instead_of_downloading_it(): void
    {
        Storage::fake('private');
        [$recruiter, $candidate, $job] = $this->makeApplication();
        $application = $candidate->applications()->where('job_id', $job->id)->firstOrFail();
        $application->update(['resume_path' => 'application-resumes/candidate.pdf']);
        Storage::disk('private')->put($application->resume_path, 'pdf bytes');

        $response = $this->actingAs($recruiter)
            ->get('/en/recruiter/applications/'.$application->id.'/resume');

        $response->assertOk();
        $this->assertStringStartsWith('inline;', (string) $response->headers->get('content-disposition'));
    }

    public function test_candidate_can_update_structured_profile_and_recruiter_can_view_it(): void
    {
        [$recruiter, $candidate] = $this->makeApplication();

        $this->actingAs($candidate)->withSession(['_token' => 'profile-token'])->put('/en/profile', [
            '_token' => 'profile-token',
            'name' => $candidate->name,
            'phone' => '+1 555 0100',
            'profile_summary' => 'I build reliable platforms and enjoy helping teams simplify complex systems.',
            'headline' => 'Senior Platform Engineer',
            'skills' => 'Laravel, PostgreSQL, Docker',
            'languages_json' => json_encode([
                ['language' => 'English', 'proficiency' => 'native_bilingual'],
                ['language' => 'French', 'proficiency' => 'professional_working'],
            ]),
            'links_json' => json_encode([
                ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/in/example'],
                ['name' => 'GitHub', 'url' => 'https://github.com/example'],
            ]),
            'experiences_json' => json_encode([
                [
                    'job_title' => 'Platform Engineer',
                    'company_name' => 'Acme',
                    'location' => 'Dublin, Ireland',
                    'start_date' => '2022-03',
                    'end_date' => null,
                    'is_current' => true,
                    'description' => 'Built reliable services.',
                ],
            ]),
            'educations_json' => json_encode([
                [
                    'school' => 'Example University',
                    'degree' => 'BSc',
                    'field_of_study' => 'Computer Science',
                    'start_date' => '2018-09',
                    'end_date' => '2022-06',
                    'is_current' => false,
                    'description' => 'Focused on distributed systems.',
                ],
            ]),
        ])->assertRedirect();

        $profile = $candidate->fresh()->candidateProfile;
        $this->assertSame('I build reliable platforms and enjoy helping teams simplify complex systems.', $candidate->fresh()->profile_summary);
        $this->assertSame('English', $profile->languages_data[0]['language']);
        $this->assertSame('native_bilingual', $profile->languages_data[0]['proficiency']);
        $this->assertSame('GitHub', $profile->profile_links[1]['name']);
        $this->assertSame('https://github.com/example', $profile->profile_links[1]['url']);
        $this->assertTrue($profile->experiences[0]['is_current']);
        $this->assertNull($profile->experiences[0]['end_date']);
        $this->assertSame('Example University', $profile->educations[0]['school']);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/applicants/'.$candidate->id)
            ->assertOk()
            ->assertSee('Senior Platform Engineer')
            ->assertSee('I build reliable platforms and enjoy helping teams simplify complex systems.')
            ->assertSee('Platform Engineer')
            ->assertSee('Acme')
            ->assertSee('Mar 2022')
            ->assertSee(__('profile.present'))
            ->assertSee('Example University')
            ->assertSee('English')
            ->assertSee(__('profile.proficiency_native_bilingual'))
            ->assertSee('GitHub')
            ->assertSee('https://github.com/example', false);
    }

    public function test_structured_candidate_profile_validation_rejects_invalid_collections(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $this->actingAs($candidate)->withSession(['_token' => 'profile-token'])->put('/en/profile', [
            '_token' => 'profile-token',
            'name' => $candidate->name,
            'languages_json' => json_encode([
                ['language' => '', 'proficiency' => 'expert'],
            ]),
            'links_json' => json_encode(array_fill(0, 6, [
                'name' => 'Unsafe',
                'url' => 'javascript:alert(1)',
            ])),
            'experiences_json' => json_encode([
                [
                    'job_title' => '',
                    'company_name' => '',
                    'location' => '',
                    'start_date' => 'March 2022',
                    'end_date' => '2020-01',
                    'is_current' => false,
                    'description' => '',
                ],
            ]),
            'educations_json' => json_encode([
                [
                    'school' => '',
                    'degree' => '',
                    'field_of_study' => '',
                    'start_date' => 'invalid',
                    'end_date' => null,
                    'is_current' => false,
                    'description' => '',
                ],
            ]),
        ])->assertSessionHasErrors([
            'languages.0.language',
            'languages.0.proficiency',
            'links',
            'links.0.url',
            'experiences.0.job_title',
            'experiences.0.company_name',
            'experiences.0.start_date',
            'educations.0.school',
            'educations.0.degree',
            'educations.0.field_of_study',
            'educations.0.start_date',
        ]);

        $this->assertDatabaseMissing('candidate_profiles', ['user_id' => $candidate->id]);
    }

    public function test_candidate_profile_form_uses_structured_collection_editors(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $this->actingAs($candidate)
            ->get('/en/profile')
            ->assertOk()
            ->assertSee('data-profile-collection="languages"', false)
            ->assertSee('data-profile-collection="links"', false)
            ->assertSee('data-profile-collection="experiences"', false)
            ->assertSee('data-profile-collection="educations"', false)
            ->assertSee(__('profile.add_language'))
            ->assertSee(__('profile.add_link'))
            ->assertSee(__('profile.add_experience'))
            ->assertSee(__('profile.add_education'))
            ->assertSee('data-language-select', false)
            ->assertSee('data-link-name-select', false)
            ->assertSee('data-date-month="start_date"', false)
            ->assertSee('data-date-year="start_date"', false)
            ->assertSee('data-date-month="end_date"', false)
            ->assertSee('data-date-year="end_date"', false)
            ->assertSee('LinkedIn')
            ->assertSee('X')
            ->assertSee('GitHub')
            ->assertSee(__('profile.personal_website'))
            ->assertSee('Instagram')
            ->assertSee(__('profile.links'))
            ->assertSee(__('profile.skills_placeholder'))
            ->assertSee(__('profile.phone_placeholder'), false)
            ->assertSee('name="profile_summary"', false)
            ->assertSee(__('profile.about'))
            ->assertSee('data-year-policy="experience-start-current-max"', false)
            ->assertSee('data-year-policy="experience-end-current-max"', false)
            ->assertSee('data-year-policy="education-start-current-max"', false)
            ->assertSee('data-year-policy="education-end-future-allowed"', false)
            ->assertSee('dateOrderFields', false)
            ->assertSee(__('profile.end_date_after_start'))
            ->assertSee('/en/candidate/profile-preview', false)
            ->assertSee(__('profile.preview_as_recruiter'))
            ->assertDontSee('data-candidate-profile-email', false)
            ->assertDontSee('type="month"', false)
            ->assertDontSee('profile-link-names', false)
            ->assertDontSee('name="languages"', false)
            ->assertDontSee('name="experience"', false)
            ->assertDontSee('name="education"', false)
            ->assertDontSee('name="github_url"', false)
            ->assertDontSee('name="portfolio_url"', false);
    }

    public function test_candidate_can_preview_their_profile_as_recruiters_see_it(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create([
            'headline' => 'Platform Engineer',
            'languages_data' => [['language' => 'English', 'proficiency' => 'fluent']],
            'profile_links' => [['name' => 'GitHub', 'url' => 'https://github.com/example']],
        ]);

        $this->actingAs($candidate)
            ->get('/en/candidate/profile-preview')
            ->assertOk()
            ->assertSee(__('profile.recruiter_preview'))
            ->assertSee('Platform Engineer')
            ->assertSee('English')
            ->assertSee('GitHub')
            ->assertSee('https://github.com/example', false);
    }

    public function test_candidate_cannot_save_unknown_or_duplicate_link_types(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $this->actingAs($candidate)->withSession(['_token' => 'profile-token'])->put('/en/profile', [
            '_token' => 'profile-token',
            'name' => $candidate->name,
            'links_json' => json_encode([
                ['name' => 'GitHub', 'url' => 'https://github.com/first'],
                ['name' => 'GitHub', 'url' => 'https://github.com/second'],
                ['name' => 'Behance', 'url' => 'https://behance.net/example'],
            ]),
        ])->assertSessionHasErrors([
            'links.1.name',
            'links.2.name',
        ]);

        $this->assertDatabaseMissing('candidate_profiles', ['user_id' => $candidate->id]);
    }

    public function test_candidate_profile_dates_require_month_and_year_for_experience_and_education(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $this->actingAs($candidate)->withSession(['_token' => 'profile-token'])->put('/en/profile', [
            '_token' => 'profile-token',
            'name' => $candidate->name,
            'experiences_json' => json_encode([[
                'job_title' => 'Engineer',
                'company_name' => 'Acme',
                'location' => '',
                'start_date' => '2022',
                'end_date' => '2024',
                'is_current' => false,
                'description' => '',
            ]]),
            'educations_json' => json_encode([[
                'school' => 'Example University',
                'degree' => 'BSc',
                'field_of_study' => 'Computer Science',
                'start_date' => '2018',
                'end_date' => '2022',
                'is_current' => false,
                'description' => '',
            ]]),
        ])->assertSessionHasErrors([
            'experiences.0.start_date',
            'experiences.0.end_date',
            'educations.0.start_date',
            'educations.0.end_date',
        ]);
    }

    public function test_future_year_rules_are_enforced_for_experience_and_education_start_dates(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $futureYear = (int) date('Y') + 1;

        $this->actingAs($candidate)->withSession(['_token' => 'profile-token'])->put('/en/profile', [
            '_token' => 'profile-token',
            'name' => $candidate->name,
            'experiences_json' => json_encode([[
                'job_title' => 'Engineer',
                'company_name' => 'Acme',
                'location' => '',
                'start_date' => "{$futureYear}-01",
                'end_date' => "{$futureYear}-02",
                'is_current' => false,
                'description' => '',
            ]]),
            'educations_json' => json_encode([[
                'school' => 'Example University',
                'degree' => 'MSc',
                'field_of_study' => 'Computer Science',
                'start_date' => "{$futureYear}-01",
                'end_date' => "{$futureYear}-06",
                'is_current' => false,
                'description' => '',
            ]]),
        ])->assertSessionHasErrors([
            'experiences.0.start_date',
            'experiences.0.end_date',
            'educations.0.start_date',
        ])->assertSessionDoesntHaveErrors('educations.0.end_date');
    }

    public function test_end_date_before_start_date_is_rejected_for_experience_and_education(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $this->actingAs($candidate)->withSession(['_token' => 'profile-token'])->put('/en/profile', [
            '_token' => 'profile-token',
            'name' => $candidate->name,
            'experiences_json' => json_encode([[
                'job_title' => 'Engineer',
                'company_name' => 'Acme',
                'location' => '',
                'start_date' => '2024-09',
                'end_date' => '2024-08',
                'is_current' => false,
                'description' => '',
            ]]),
            'educations_json' => json_encode([[
                'school' => 'Example University',
                'degree' => 'BSc',
                'field_of_study' => 'Computer Science',
                'start_date' => '2022-06',
                'end_date' => '2021-12',
                'is_current' => false,
                'description' => '',
            ]]),
        ])->assertSessionHasErrors([
            'experiences.0.end_date',
            'educations.0.end_date',
        ]);
    }

    private function makeApplication(): array
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['title' => 'Cloud Engineer']);
        Application::factory()->for($candidate, 'candidate')->for($job)->create([
            'status' => ApplicationStatus::Pending->value,
        ]);

        return [$recruiter, $candidate, $job];
    }
}
