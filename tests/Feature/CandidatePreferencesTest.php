<?php

namespace Tests\Feature;

use App\Enums\ItCategory;
use App\Enums\JobStatus;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CandidatePreferencesTest extends TestCase
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

    public function test_candidate_profile_casts_preferred_categories(): void
    {
        $profile = CandidateProfile::factory()->create([
            'preferred_categories' => ['Software Development', 'DevOps'],
        ]);

        $this->assertIsArray($profile->preferred_categories);
        $this->assertSame(['Software Development', 'DevOps'], $profile->preferred_categories);
    }

    public function test_interest_list_contains_only_it_categories(): void
    {
        $categories = ItCategory::values();

        $this->assertNotEmpty($categories);
        $this->assertContains('Software Development', $categories);
        $this->assertNotContains('Engineering', $categories);
    }

    public function test_profile_edit_renders_interest_checkboxes_for_candidates(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create([
            'preferred_categories' => ['DevOps'],
        ]);

        $response = $this->actingAs($candidate)
            ->get('/en/profile')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Profile/Edit', false)
            ->where('candidateProfile.preferred_categories', ['DevOps'])
        );

        $profile = File::get(resource_path('js/Pages/Profile/Edit.vue'));
        $this->assertStringContainsString('preferred_categories', $profile);
        $this->assertStringContainsString('type="checkbox"', $profile);
    }

    public function test_candidate_can_save_preferences_via_profile_update(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create();

        $this->actingAs($candidate)
            ->put('/en/profile', [
                'name' => $candidate->name,
                'preferred_categories' => ['Software Development', 'Artificial Intelligence'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ['Software Development', 'Artificial Intelligence'],
            $candidate->candidateProfile->fresh()->preferred_categories
        );
    }

    public function test_verify_email_page_does_not_show_preference_modal(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create();

        session(['show_preferences_picker' => true]);

        $html = (string) $this->actingAs($candidate)
            ->get('/en/email/verify')
            ->getContent();

        $this->assertStringNotContainsString('data-preferences-modal', $html);
    }

    public function test_home_shows_preference_modal_for_verified_candidate(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create();

        session(['show_preferences_picker' => true]);

        $response = $this->actingAs($candidate)
            ->get('/en/')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home/Index', false)
            ->where('preferenceModal.show', true)
        );

        $home = File::get(resource_path('js/Pages/Home/Index.vue'));
        $this->assertStringContainsString('data-preferences-modal', $home);
    }

    public function test_home_hides_preference_modal_for_unverified_candidate(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => null]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create();

        session(['show_preferences_picker' => true]);

        $html = (string) $this->actingAs($candidate)
            ->get('/en/')
            ->getContent();

        $this->assertStringNotContainsString('data-preferences-modal', $html);
    }

    public function test_recruiter_never_sees_preference_modal(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');

        session(['show_preferences_picker' => true]);

        $html = (string) $this->actingAs($recruiter)
            ->get('/en/email/verify')
            ->getContent();

        $this->assertStringNotContainsString('data-preferences-modal', $html);
    }

    public function test_quick_preferences_save_persists_and_clears_flag(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create();

        session(['show_preferences_picker' => true]);

        $this->actingAs($candidate)
            ->post('/en/candidate/preferences', [
                'preferred_categories' => ['Cybersecurity', 'DevOps'],
            ])
            ->assertRedirect();

        $this->assertSame(
            ['Cybersecurity', 'DevOps'],
            $candidate->candidateProfile->fresh()->preferred_categories
        );
        $this->assertFalse(session()->has('show_preferences_picker'));
    }

    public function test_skip_clears_flag_without_saving(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['preferred_categories' => null]);

        session(['show_preferences_picker' => true]);

        $this->actingAs($candidate)
            ->post('/en/candidate/preferences', ['skip' => '1'])
            ->assertRedirect();

        $this->assertNull($candidate->candidateProfile->fresh()->preferred_categories);
        $this->assertFalse(session()->has('show_preferences_picker'));
    }

    public function test_unverified_candidate_quick_save_redirects_back_to_verification(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => null]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create();

        session(['show_preferences_picker' => true]);

        $this->actingAs($candidate)
            ->post('/en/candidate/preferences', [
                'preferred_categories' => ['DevOps'],
            ])
            ->assertRedirect(localized_route('verification.notice'));

        // Preferences are saved, but the flag stays so the modal shows again after verifying.
        $this->assertSame(['DevOps'], $candidate->candidateProfile->fresh()->preferred_categories);
        $this->assertTrue(session()->has('show_preferences_picker'));
    }

    public function test_unverified_candidate_skip_redirects_back_to_verification(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => null]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create();

        session(['show_preferences_picker' => true]);

        $this->actingAs($candidate)
            ->post('/en/candidate/preferences', ['skip' => '1'])
            ->assertRedirect(localized_route('verification.notice'));

        $this->assertTrue(session()->has('show_preferences_picker'));
    }

    public function test_candidate_can_remove_all_preferences_via_profile_update(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create([
            'preferred_categories' => ['Software Development', 'DevOps'],
        ]);

        // Simulate the profile form with every checkbox unchecked: the hidden
        // sentinel still submits the (empty) array.
        $this->actingAs($candidate)
            ->put('/en/profile', [
                'name' => $candidate->name,
                'preferred_categories' => [''],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([], $candidate->candidateProfile->fresh()->preferred_categories);
    }

    public function test_matching_jobs_appear_first_for_candidate_with_preferences(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create([
            'preferred_categories' => ['Software Development'],
        ]);

        $other = Job::factory()->create([
            'title' => 'Zulu Nonmatch Role',
            'category' => 'Networking',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);
        $match = Job::factory()->create([
            'title' => 'Alpha Match Role',
            'category' => 'Software Development',
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($candidate)->get('/en/jobs');

        $html = (string) $response->getContent();
        $this->assertTrue(
            strpos($html, 'Alpha Match Role') < strpos($html, 'Zulu Nonmatch Role'),
            'Matching job should appear before non-matching job'
        );
    }

    public function test_candidate_without_preferences_gets_default_order(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['preferred_categories' => null]);

        $a = Job::factory()->create([
            'title' => 'First Published', 'status' => JobStatus::Published->value, 'published_at' => now(),
        ]);
        $b = Job::factory()->create([
            'title' => 'Second Published', 'status' => JobStatus::Published->value, 'published_at' => now()->addMinute(),
        ]);

        $html = (string) $this->actingAs($candidate)->get('/en/jobs')->getContent();
        $this->assertTrue(
            strpos($html, 'Second Published') < strpos($html, 'First Published'),
            'Default ordering should be latest published first'
        );
    }

    public function test_home_shows_recommended_heading_for_candidate_with_preferences(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create([
            'preferred_categories' => ['DevOps'],
        ]);
        Job::factory()->create([
            'title' => 'DevOps Role', 'category' => 'DevOps',
            'status' => JobStatus::Published->value, 'published_at' => now(),
        ]);

        $html = (string) $this->actingAs($candidate)->get('/en/')->getContent();
        $this->assertStringContainsString(__('jobs.recommended_for_you'), $html);
    }

    public function test_recruiter_gets_default_order(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        Job::factory()->create([
            'title' => 'Recruiter View Role', 'status' => JobStatus::Published->value, 'published_at' => now(),
        ]);

        $html = (string) $this->actingAs($recruiter)->get('/en/jobs')->getContent();
        $this->assertStringContainsString('Recruiter View Role', $html);
    }
}
