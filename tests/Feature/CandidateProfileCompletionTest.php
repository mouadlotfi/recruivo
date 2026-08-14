<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CandidateProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    // ── Model-level profileCompletion() ──

    public function test_empty_candidate_returns_zero_percent_and_all_missing(): void
    {
        $user = User::factory()->create(['profile_summary' => null]);
        $user->assignRole('Candidate');

        $result = $user->profileCompletion();

        $this->assertEquals(0, $result['percentage']);
        $this->assertEquals(0, $result['completed']);
        $this->assertEquals(5, $result['total']);
        $this->assertEqualsCanonicalizing(['headline', 'profile_summary', 'skills', 'resume', 'experience'], $result['missing']);
    }

    public function test_complete_candidate_returns_hundred_percent_and_no_missing(): void
    {
        $user = User::factory()->create(['profile_summary' => 'A summary']);
        $user->assignRole('Candidate');
        CandidateProfile::factory()->for($user)->create([
            'headline' => 'Engineer',
            'skills' => 'PHP',
            'resume_path' => 'resumes/x.pdf',
            'experiences' => [['job_title' => 'Dev', 'company_name' => 'ACME']],
        ]);

        $result = $user->profileCompletion();

        $this->assertEquals(100, $result['percentage']);
        $this->assertEquals(5, $result['completed']);
        $this->assertEquals([], $result['missing']);
    }

    public function test_partial_candidate_returns_correct_partial_score(): void
    {
        $user = User::factory()->create(['profile_summary' => 'A summary']);
        $user->assignRole('Candidate');
        CandidateProfile::factory()->for($user)->create([
            'headline' => 'Engineer',
            'skills' => null,
            'resume_path' => null,
            'experiences' => null,
        ]);

        $result = $user->profileCompletion();

        $this->assertEquals(40, $result['percentage']);
        $this->assertEquals(2, $result['completed']);
        $this->assertEqualsCanonicalizing(['skills', 'resume', 'experience'], $result['missing']);
    }

    // ── Dashboard completion card ──

    public function test_dashboard_shows_completion_card_and_only_missing_items(): void
    {
        $user = User::factory()->create(['profile_summary' => 'A summary']);
        $user->assignRole('Candidate');
        CandidateProfile::factory()->for($user)->create([
            'headline' => 'Engineer',
            'skills' => null,
            'resume_path' => null,
        ]);

        $response = $this->actingAs($user)->get('/en/candidate/dashboard');

        $response->assertOk();
        $response->assertSee('data-profile-completion', false);
        $response->assertSee('40%');
        $response->assertSee(__('candidate.completion_skills'));
        $response->assertSee(__('candidate.completion_resume'));
        $response->assertSee(__('candidate.completion_experience'));
        $response->assertDontSee(__('candidate.completion_headline'));
        $response->assertSee('role="progressbar"', false);
    }

    public function test_dashboard_hides_completion_card_when_profile_complete(): void
    {
        $user = User::factory()->create(['profile_summary' => 'A summary']);
        $user->assignRole('Candidate');
        CandidateProfile::factory()->for($user)->create([
            'headline' => 'Engineer',
            'skills' => 'PHP',
            'resume_path' => 'resumes/x.pdf',
            'experiences' => [['job_title' => 'Dev', 'company_name' => 'ACME']],
        ]);

        $response = $this->actingAs($user)->get('/en/candidate/dashboard');

        $response->assertOk();
        $response->assertDontSee('data-profile-completion', false);
        $response->assertDontSee(__('candidate.profile_complete'));
    }

    public function test_dashboard_no_longer_shows_tips_for_success(): void
    {
        $user = User::factory()->create(['profile_summary' => null]);
        $user->assignRole('Candidate');

        $response = $this->actingAs($user)->get('/en/candidate/dashboard');

        $response->assertOk();
        $response->assertDontSee(__('candidate.tips_for_success'));
        $response->assertDontSee(__('candidate.tip_1'));
        $response->assertDontSee(__('candidate.tip_2'));
        $response->assertDontSee(__('candidate.tip_3'));
    }

    // ── Profile edit completion summary ──

    public function test_profile_edit_shows_completion_for_candidates(): void
    {
        $user = User::factory()->create(['profile_summary' => 'A summary']);
        $user->assignRole('Candidate');
        CandidateProfile::factory()->for($user)->create([
            'headline' => 'Engineer',
            'skills' => null,
            'resume_path' => null,
        ]);

        $response = $this->actingAs($user)->get('/en/profile');

        $response->assertOk();
        $response->assertSee('40%');
        $response->assertSee('role="progressbar"', false);
        $response->assertSee('id="experience"', false);
    }

    public function test_profile_edit_hides_completion_for_recruiters(): void
    {
        $company = \App\Models\Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'is_recruiter' => true,
        ]);
        $user->assignRole('Recruiter');

        $response = $this->actingAs($user)->get('/en/profile');

        $response->assertOk();
        $response->assertDontSee('role="progressbar"', false);
        $response->assertDontSee('20%');
    }

    public function test_profile_edit_hides_completion_when_profile_complete(): void
    {
        $user = User::factory()->create(['profile_summary' => 'A summary']);
        $user->assignRole('Candidate');
        CandidateProfile::factory()->for($user)->create([
            'headline' => 'Engineer',
            'skills' => 'PHP',
            'resume_path' => 'resumes/x.pdf',
            'experiences' => [['job_title' => 'Dev', 'company_name' => 'ACME']],
        ]);

        $response = $this->actingAs($user)->get('/en/profile');

        $response->assertOk();
        $response->assertDontSee('role="progressbar"', false);
        $response->assertDontSee(__('profile.profile_complete'));
    }
}
