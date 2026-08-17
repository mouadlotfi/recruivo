<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\Post;
use App\Models\User;
use App\Services\UserAccountDeletionService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        foreach (['Candidate', 'Recruiter', 'Admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_candidate_resume_settings_hide_private_storage_names_and_use_a_responsive_upload_panel(): void
    {
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate, 'user')->create([
            'resume_path' => 'resumes/1_1723540000.pdf',
        ]);

        $response = $this->actingAs($candidate)->get('/en/profile');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Profile/Edit', false)
                ->where('candidateProfile.resume_path', 'resumes/1_1723540000.pdf')
            );

        $profile = File::get(resource_path('js/Pages/Profile/Edit.vue'));
        $this->assertStringContainsString('resume_uploaded', $profile);
        $this->assertStringContainsString('replace_resume', $profile);
        $this->assertStringContainsString('profile_resume', $profile);
        $this->assertStringNotContainsString('1_1723540000.pdf', $profile);
    }

    public function test_candidate_cannot_apply_to_an_unpublished_job(): void
    {
        Notification::fake();

        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate, 'user')->create([
            'resume_path' => 'resumes/candidate.pdf',
        ]);

        $job = Job::factory()->create(['status' => JobStatus::Draft->value]);

        $response = $this->actingAs($candidate)->post("/en/jobs/{$job->id}/apply");

        $response->assertNotFound();
        $this->assertDatabaseMissing('applications', [
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
        ]);
    }

    public function test_api_user_can_delete_their_own_account(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');
        Sanctum::actingAs($user);

        $this->deleteJson('/api/profile')->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_demo_account_cannot_delete_itself_through_the_api(): void
    {
        Notification::fake();

        $demo = User::factory()->create([
            'email_verified_at' => now(),
            'is_demo' => true,
        ]);
        $demo->assignRole('Candidate');
        Sanctum::actingAs($demo);

        $this->deleteJson('/api/profile')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account');

        $this->assertDatabaseHas('users', ['id' => $demo->id]);
    }

    public function test_demo_account_profile_and_credentials_are_read_only(): void
    {
        $company = Company::factory()->create(['name' => 'Protected Demo Company']);
        $demo = User::factory()->for($company)->create([
            'name' => 'Protected Demo User',
            'email_verified_at' => now(),
            'is_demo' => true,
        ]);
        $demo->assignRole('Recruiter');

        $this->actingAs($demo)
            ->put('/en/profile', ['company' => ['name' => 'Changed Company']])
            ->assertSessionHasErrors('profile');

        $this->actingAs($demo)
            ->put('/en/profile/password', [
                'current_password' => 'password',
                'password' => 'ChangedPassword123!',
                'password_confirmation' => 'ChangedPassword123!',
            ])
            ->assertSessionHasErrors('profile');

        $this->actingAs($demo)
            ->put('/en/profile/email/request', ['email' => 'changed-demo@example.com'])
            ->assertSessionHasErrors('profile');

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Protected Demo Company']);
        $this->assertDatabaseHas('users', [
            'id' => $demo->id,
            'name' => 'Protected Demo User',
            'pending_email' => null,
        ]);
    }

    public function test_demo_account_can_see_profile_settings_but_all_forms_are_disabled(): void
    {
        $company = Company::factory()->create(['name' => 'Visible Demo Company']);
        $demo = User::factory()->for($company)->create([
            'email_verified_at' => now(),
            'is_demo' => true,
        ]);
        $demo->assignRole('Recruiter');

        $response = $this->actingAs($demo)
            ->get('/en/profile')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Profile/Edit', false)
            ->where('user.is_demo', true)
            ->where('company.name', 'Visible Demo Company')
        );

        $profile = File::get(resource_path('js/Pages/Profile/Edit.vue'));
        $this->assertStringContainsString(':disabled="user.is_demo"', $profile);
        $this->assertStringContainsString('demo_read_only', $profile);
    }

    public function test_demo_account_profile_is_read_only_through_the_api(): void
    {
        $company = Company::factory()->create(['name' => 'Protected API Company']);
        $demo = User::factory()->for($company)->create(['is_demo' => true]);
        $demo->assignRole('Recruiter');
        Sanctum::actingAs($demo);

        $this->putJson('/api/profile', ['name' => 'Changed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile');

        $this->putJson('/api/profile/password', [
            'current_password' => 'password',
            'password' => 'ChangedPassword123!',
            'password_confirmation' => 'ChangedPassword123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('profile');

        $this->putJson('/api/recruiter/company-profile', ['name' => 'Changed Company'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile');

        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Protected API Company']);
    }

    public function test_api_admin_can_delete_another_user(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');
        $target = User::factory()->create();
        $target->assignRole('Candidate');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/admin/users/{$target->id}")->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_a_demo_account_through_the_api(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');
        $demo = User::factory()->create(['is_demo' => true]);
        $demo->assignRole('Candidate');
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/admin/users/{$demo->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account');

        $this->assertDatabaseHas('users', ['id' => $demo->id]);
    }

    public function test_demo_account_cannot_be_deleted_directly_through_the_model(): void
    {
        $demo = User::factory()->create(['is_demo' => true]);

        try {
            $demo->delete();
            $this->fail('Deleting a demo account should throw a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('account', $exception->errors());
        }

        $this->assertDatabaseHas('users', ['id' => $demo->id]);
    }

    public function test_deleting_a_recruiter_does_not_delete_candidate_owned_resume_files(): void
    {
        Notification::fake();
        Storage::fake('private');
        Storage::disk('private')->put('resumes/candidate.pdf', 'resume');

        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create();
        Application::factory()->for($candidate, 'candidate')->for($job)->create([
            'resume_path' => 'resumes/candidate.pdf',
        ]);

        app(UserAccountDeletionService::class)->deleteUserAccount($recruiter, false);

        Storage::disk('private')->assertExists('resumes/candidate.pdf');
        Storage::disk('public')->assertMissing('resumes/candidate.pdf');
    }

    public function test_resume_migration_command_removes_publicly_accessible_copies(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        Storage::disk('public')->put('resumes/legacy.pdf', 'legacy resume');

        $candidate = User::factory()->create();
        CandidateProfile::factory()->for($candidate, 'user')->create([
            'resume_path' => 'resumes/legacy.pdf',
        ]);

        $this->artisan('resumes:migrate-private')->assertSuccessful();

        Storage::disk('private')->assertExists('resumes/legacy.pdf');
        Storage::disk('public')->assertMissing('resumes/legacy.pdf');
    }

    public function test_job_factory_uses_the_valid_onsite_remote_type_value(): void
    {
        $factory = file_get_contents(database_path('factories/JobFactory.php'));

        $this->assertStringContainsString("'onsite'", $factory);
        $this->assertStringNotContainsString("'on-site'", $factory);
    }

    public function test_unreliable_external_post_placeholders_are_replaced_locally(): void
    {
        $post = new Post(['featured_image' => 'https://via.placeholder.com/800x600.png']);

        $this->assertSame(asset('images/post-placeholder.svg'), $post->featured_image_url);
    }

    public function test_recruiter_can_clear_an_optional_company_profile_field(): void
    {
        $company = Company::factory()->create(['tagline' => 'Old tagline']);
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $this->actingAs($recruiter)->put('/en/profile', [
            'company' => [
                'name' => $company->name,
                'tagline' => '',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'tagline' => null,
        ]);
    }

    public function test_signed_email_change_link_can_be_used_without_an_active_session(): void
    {
        Notification::fake();

        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'pending_email' => 'new-owner@example.test',
        ]);
        $owner->assignRole('Candidate');

        $response = $this->get(URL::temporarySignedRoute('profile.email.verify', now()->addHour(), [
            'locale' => 'en',
            'id' => $owner->id,
            'hash' => sha1($owner->pending_email),
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'email' => 'new-owner@example.test',
            'pending_email' => null,
        ]);
    }

    public function test_unsigned_email_change_verification_link_is_rejected(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'pending_email' => 'new-email@example.test',
        ]);
        $user->assignRole('Candidate');

        $response = $this->actingAs($user)->get(route('profile.email.verify', [
            'locale' => 'en',
            'id' => $user->id,
            'hash' => sha1($user->pending_email),
        ]));

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
            'pending_email' => 'new-email@example.test',
        ]);
    }

    public function test_wrong_password_does_not_reveal_that_an_unverified_account_exists(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.test',
            'email_verified_at' => null,
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->from('/en/login')->post('/en/login', [
            'email' => $user->email,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertRedirect('/en/login');
        $response->assertSessionHasErrors([
            'email' => __('auth.invalid_credentials'),
        ]);
    }

    public function test_verification_resend_does_not_reveal_whether_an_email_exists(): void
    {
        Notification::fake();

        $this->postJson('/api/email/verification-notification', [
            'email' => 'unknown@example.test',
        ])->assertOk()->assertJson([
            'message' => 'If the account exists and needs verification, a link has been sent.',
        ]);
    }
}
