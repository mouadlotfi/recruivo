<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use App\Services\ApplicationResumeService;
use App\Services\SmartSearchService;
use App\Services\UserAccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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

    public function test_web_email_change_requires_the_current_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');

        // The new address also becomes the recovery address, so possession of the
        // session alone must not be enough to repin it.
        $this->actingAs($user)
            ->put('/en/profile/email/request', ['email' => 'attacker@example.test'])
            ->assertSessionHasErrors('current_password');
        $this->assertNull($user->fresh()->pending_email);

        $this->actingAs($user)
            ->put('/en/profile/email/request', [
                'email' => 'attacker@example.test',
                'current_password' => 'not-the-password',
            ])
            ->assertSessionHasErrors('current_password');
        $this->assertNull($user->fresh()->pending_email);

        $this->actingAs($user)
            ->put('/en/profile/email/request', [
                'email' => 'new-address@example.test',
                'current_password' => 'password',
            ])
            ->assertSessionHasNoErrors();
        $this->assertSame('new-address@example.test', $user->fresh()->pending_email);
    }

    public function test_api_email_change_requires_the_current_password(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');

        // Negative control: no credential at all, and no prior session in this
        // method, so a 401 is the only way this can answer.
        $this->putJson('/api/profile', ['email' => 'attacker@example.test'])
            ->assertUnauthorized();

        Sanctum::actingAs($user);

        $this->putJson('/api/profile', ['email' => 'attacker@example.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
        $this->assertNull($user->fresh()->pending_email);

        $this->putJson('/api/profile', [
            'email' => 'attacker@example.test',
            'current_password' => 'not-the-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
        $this->assertNull($user->fresh()->pending_email);

        $this->putJson('/api/profile', [
            'email' => 'new-address@example.test',
            'current_password' => 'password',
        ])->assertOk();
        $this->assertSame('new-address@example.test', $user->fresh()->pending_email);
    }

    public function test_admin_deletion_refuses_an_admin_target_on_every_path(): void
    {
        Notification::fake();

        $adminA = User::factory()->create(['email_verified_at' => now()]);
        $adminA->assignRole('Admin');
        $adminB = User::factory()->create(['email_verified_at' => now()]);
        $adminB->assignRole('Admin');

        // The shared decision point refuses it, so every caller inherits the rule
        // rather than only the surface that remembered to check.
        try {
            app(UserAccountDeletionService::class)->deleteUserAccount($adminB, false);
            $this->fail('An admin-initiated deletion of an Admin target must be refused.');
        } catch (ValidationException) {
            // expected
        }
        $this->assertDatabaseHas('users', ['id' => $adminB->id]);

        $this->actingAs($adminA)
            ->delete("/en/admin/users/{$adminB->id}");
        $this->assertDatabaseHas('users', ['id' => $adminB->id]);

        // Self-service deletion is not an admin-initiated one and still works.
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        app(UserAccountDeletionService::class)->deleteUserAccount($candidate, true);
        $this->assertDatabaseMissing('users', ['id' => $candidate->id]);
    }

    public function test_api_admin_cannot_delete_another_admin(): void
    {
        Notification::fake();

        $adminA = User::factory()->create(['email_verified_at' => now()]);
        $adminA->assignRole('Admin');
        $adminB = User::factory()->create(['email_verified_at' => now()]);
        $adminB->assignRole('Admin');

        Sanctum::actingAs($adminA);

        $this->deleteJson("/api/admin/users/{$adminB->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('account');

        $this->assertDatabaseHas('users', ['id' => $adminB->id]);
    }

    public function test_auth_login_still_bounds_repeats_against_one_address(): void
    {
        // The per-address bucket is the half that stops a brute force against a
        // single account; adding a client-scoped ceiling must not replace it.
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/auth/login', [
                'email' => 'same-address@example.test',
                'password' => 'whatever',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'same-address@example.test',
            'password' => 'whatever',
        ])->assertStatus(429);
    }

    public function test_auth_login_has_a_per_client_budget(): void
    {
        // Without this, naming a different address per request multiplied the
        // budget without limit.
        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->postJson('/api/auth/login', [
                'email' => "spray-{$attempt}@example.test",
                'password' => 'whatever',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'spray-31@example.test',
            'password' => 'whatever',
        ])->assertStatus(429);
    }

    public function test_search_query_terms_are_bounded(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'title' => 'Astronomer',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $service = app(SmartSearchService::class);

        $this->assertSame(1, $service->search('astronomer')->jobCount());

        // The same term pushed past the term cap is never scored, which is what
        // bounds the per-term work an anonymous caller can buy.
        $junk = implode(' ', array_map(fn (int $i) => "zzq{$i}", range(1, 8)));
        $this->assertSame(0, $service->search("{$junk} astronomer")->jobCount());
    }

    public function test_web_account_deletion_does_not_resurrect_the_row(): void
    {
        Notification::fake();

        // The factory gives every user a non-empty remember_token, which is the
        // precondition for the old logout()-after-delete() re-insert.
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');
        $this->assertNotNull($user->remember_token);

        $this->actingAs($user)->delete('/en/profile');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_web_account_deletion_still_removes_the_users_own_files(): void
    {
        Notification::fake();
        Storage::fake('private');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');
        CandidateProfile::factory()->for($user, 'user')->create(['resume_path' => 'resumes/own.pdf']);
        Storage::disk('private')->put('resumes/own.pdf', 'resume');

        $this->actingAs($user)->delete('/en/profile');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        Storage::disk('private')->assertMissing('resumes/own.pdf');
    }

    public function test_password_change_revokes_every_existing_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');

        $token = $user->createToken('probe')->plainTextToken;
        $other = $user->createToken('other-device')->plainTextToken;
        $this->assertSame(2, $user->tokens()->count());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/profile/password', [
                'current_password' => 'password',
                'password' => 'RotatedPass123!',
                'password_confirmation' => 'RotatedPass123!',
            ])
            ->assertOk()
            ->assertJsonPath('tokens_revoked', true);

        $this->assertSame(0, $user->tokens()->count());

        // The resolved auth guards are cached for the life of the application
        // instance, so a second request in the same test method would otherwise
        // reuse the first request's authenticated user. Production runs a fresh
        // container per request, so drop the cached guards to match it.
        $this->app['auth']->forgetGuards();

        // Neither the rotated session's own token nor the sibling survives.
        $this->withHeader('Authorization', 'Bearer '.$other)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_password_reset_revokes_existing_tokens(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Candidate');
        $stranded = $user->createToken('stranded')->plainTextToken;
        $this->assertSame(1, $user->tokens()->count());

        $resetToken = Password::createToken($user);

        $this->post('/en/reset-password-submit', [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'RecoveredPass123!',
            'password_confirmation' => 'RecoveredPass123!',
        ]);

        $this->assertSame(0, $user->tokens()->count());
        $this->withHeader('Authorization', 'Bearer '.$stranded)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_lone_recruiter_deletion_unpublishes_the_company_listings_and_drops_the_logo(): void
    {
        Notification::fake();
        Storage::fake('public');

        $company = Company::factory()->create(['logo_path' => 'logos/acme.png']);
        Storage::disk('public')->put('logos/acme.png', 'logo');
        $recruiter = User::factory()->for($company)->create(['is_recruiter' => true]);
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(UserAccountDeletionService::class)->deleteUserAccount($recruiter, false);

        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'status' => 'draft', 'published_at' => null]);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'logo_path' => null]);
        Storage::disk('public')->assertMissing('logos/acme.png');

        // The leftover listing no longer accepts applications.
        $this->getJson("/api/jobs/{$job->id}")->assertNotFound();
    }

    public function test_company_with_another_recruiter_keeps_its_listings(): void
    {
        Notification::fake();
        Storage::fake('public');

        $company = Company::factory()->create(['logo_path' => 'logos/acme.png']);
        Storage::disk('public')->put('logos/acme.png', 'logo');
        $departing = User::factory()->for($company)->create(['is_recruiter' => true]);
        $departing->assignRole('Recruiter');
        $staying = User::factory()->for($company)->create(['is_recruiter' => true]);
        $staying->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($departing, 'recruiter')->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        app(UserAccountDeletionService::class)->deleteUserAccount($departing, false);

        $this->assertDatabaseHas('jobs', ['id' => $job->id, 'status' => 'published']);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'logo_path' => 'logos/acme.png']);
        Storage::disk('public')->assertExists('logos/acme.png');
    }

    public function test_application_owns_a_copy_of_the_profile_resume(): void
    {
        Notification::fake();
        Storage::fake('private');

        $job = $this->publishedJob();
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/profile.pdf']);
        Storage::disk('private')->put('resumes/profile.pdf', 'profile resume');

        $submissionToken = 'submission-token';

        $this->actingAs($candidate)
            ->withSession(["job_application_submission.{$job->id}" => ['token' => $submissionToken, 'completed' => false]])
            ->post("/en/jobs/{$job->id}/apply", [
                'submission_token' => $submissionToken,
                'cover_letter' => 'Cover letter',
                'resume_source' => 'profile',
            ])
            ->assertSessionHas('success');

        $application = Application::where('candidate_id', $candidate->id)->firstOrFail();

        $this->assertNotSame('resumes/profile.pdf', $application->resume_path);
        $this->assertStringStartsWith('application-resumes/', $application->resume_path);
        Storage::disk('private')->assertExists($application->resume_path);
        Storage::disk('private')->assertExists('resumes/profile.pdf');
    }

    public function test_api_application_also_owns_a_copy_of_the_profile_resume(): void
    {
        Notification::fake();
        Storage::fake('private');

        $job = $this->publishedJob();
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/profile.pdf']);
        Storage::disk('private')->put('resumes/profile.pdf', 'profile resume');

        $token = $candidate->createToken('probe')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/jobs/{$job->id}/apply", [
                'cover_letter' => 'Cover letter',
                'use_existing_resume' => 'true',
            ])
            ->assertCreated();

        $application = Application::where('candidate_id', $candidate->id)->firstOrFail();

        $this->assertNotSame('resumes/profile.pdf', $application->resume_path);
        $this->assertStringStartsWith('application-resumes/', $application->resume_path);
        Storage::disk('private')->assertExists($application->resume_path);
    }

    public function test_application_resume_survives_the_profile_resume_moving_on(): void
    {
        Notification::fake();
        Storage::fake('private');

        $job = $this->publishedJob();
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['resume_path' => 'resumes/profile.pdf']);
        Storage::disk('private')->put('resumes/profile.pdf', 'profile resume');

        $submissionToken = 'submission-token';

        $this->actingAs($candidate)
            ->withSession(["job_application_submission.{$job->id}" => ['token' => $submissionToken, 'completed' => false]])
            ->post("/en/jobs/{$job->id}/apply", [
                'submission_token' => $submissionToken,
                'cover_letter' => 'Cover letter',
                'resume_source' => 'profile',
            ])
            ->assertSessionHas('success');

        $application = Application::where('candidate_id', $candidate->id)->firstOrFail();

        // The profile flow replaces the resume by deleting the old file; the
        // application must not depend on that file.
        Storage::disk('private')->delete('resumes/profile.pdf');

        Storage::disk('private')->assertExists($application->resume_path);

        $this->actingAs($job->recruiter)
            ->get("/en/recruiter/applications/{$application->id}/resume")
            ->assertOk();
    }

    public function test_profile_resume_file_is_kept_while_an_application_references_it(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('resumes/shared.pdf', 'shared resume');

        $job = $this->publishedJob();
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        Application::factory()->for($candidate, 'candidate')->for($job)->create([
            'resume_path' => 'resumes/shared.pdf',
        ]);

        $service = app(ApplicationResumeService::class);

        // Rows written before copy-on-apply share the profile's path.
        $service->deleteIfUnreferenced('resumes/shared.pdf');
        Storage::disk('private')->assertExists('resumes/shared.pdf');

        // An unreferenced file is still removed, so the guard leaks no storage.
        Storage::disk('private')->put('resumes/orphan.pdf', 'orphan');
        $service->deleteIfUnreferenced('resumes/orphan.pdf');
        Storage::disk('private')->assertMissing('resumes/orphan.pdf');
    }

    public function test_demo_recruiter_cannot_mutate_jobs_through_the_web_surface(): void
    {
        Notification::fake();
        Storage::fake('private');

        $company = Company::factory()->create();
        $demo = User::factory()->for($company)->create([
            'email_verified_at' => now(),
            'is_recruiter' => true,
            'is_demo' => true,
        ]);
        $demo->assignRole('Recruiter');
        $existing = Job::factory()->for($company)->for($demo, 'recruiter')->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $payload = $this->jobPayload('Demo posted job');

        $this->actingAs($demo)->post('/en/recruiter/jobs', $payload)->assertSessionHasErrors('demo');
        $this->assertDatabaseMissing('jobs', ['title' => 'Demo posted job']);

        $this->actingAs($demo)
            ->delete("/en/recruiter/jobs/{$existing->id}")
            ->assertSessionHasErrors('demo');
        $this->assertDatabaseHas('jobs', ['id' => $existing->id]);

        // The refusal is scoped to demo accounts: a real recruiter in the same
        // company still creates jobs.
        $real = User::factory()->for($company)->create([
            'email_verified_at' => now(),
            'is_recruiter' => true,
        ]);
        $real->assignRole('Recruiter');
        $this->actingAs($real)->post('/en/recruiter/jobs', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('jobs', ['title' => 'Demo posted job']);
    }

    public function test_demo_recruiter_cannot_mutate_jobs_through_the_api(): void
    {
        Notification::fake();

        $company = Company::factory()->create();
        $demo = User::factory()->for($company)->create([
            'email_verified_at' => now(),
            'is_recruiter' => true,
            'is_demo' => true,
        ]);
        $demo->assignRole('Recruiter');
        $token = $demo->createToken('probe')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/recruiter/jobs', $this->jobPayload('Demo posted job'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('demo');

        $this->assertDatabaseMissing('jobs', ['title' => 'Demo posted job']);
    }

    public function test_only_the_tunnel_peer_may_set_the_client_ip(): void
    {
        // The app is also published on the LAN (APP_BIND_IP=0.0.0.0), so a LAN source
        // address must not be treated as a proxy: if it were, any LAN peer could send
        // an X-Forwarded-For of its choosing and pick the IP every limiter keys on.
        $lanPeer = Request::create('/en', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.50']);
        $lanPeer->headers->set('X-Forwarded-For', '203.0.113.9');
        (new TrustProxies)->handle($lanPeer, fn ($request) => $request);
        $this->assertSame('192.168.1.50', $lanPeer->ip());

        // The tunnel arrives from the docker bridge gateway - measured on the deploy
        // host - and is the only peer that may supply the real client IP.
        $tunnel = Request::create('/en', 'GET', [], [], [], ['REMOTE_ADDR' => '172.22.0.1']);
        $tunnel->headers->set('X-Forwarded-For', '203.0.113.9');
        (new TrustProxies)->handle($tunnel, fn ($request) => $request);
        $this->assertSame('203.0.113.9', $tunnel->ip());
    }

    public function test_anonymous_company_detail_does_not_disclose_application_volume(): void
    {
        $job = $this->publishedJob();
        $candidate = User::factory()->create(['email_verified_at' => now()]);
        $candidate->assignRole('Candidate');
        Application::factory()->for($candidate, 'candidate')->for($job)->create();

        $response = $this->getJson('/api/companies/'.$job->company->slug);

        $response->assertOk()->assertJsonMissingPath('data.jobs.0.applications_count');
        $this->assertNotEmpty($response->json('data.jobs'));
    }

    public function test_default_roles_migration_does_not_regrant_a_revoked_role(): void
    {
        foreach (['Admin', 'Recruiter', 'Candidate'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // An account with no roles is indistinguishable from one whose roles an
        // operator revoked, so the migration must not decide on its own.
        $user = User::factory()->create(['is_recruiter' => false]);
        $this->assertSame(0, $user->roles()->count());

        $migration = require database_path('migrations/2026_09_10_200000_ensure_default_roles_exist.php');
        $migration->up();

        $this->assertSame(0, $user->fresh()->roles()->count());
    }

    public function test_legacy_role_backfill_is_explicit(): void
    {
        foreach (['Admin', 'Recruiter', 'Candidate'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $recruiter = User::factory()->create(['is_recruiter' => true]);

        $this->artisan('roles:backfill-legacy --dry-run')->assertSuccessful();
        $this->assertSame(0, $recruiter->fresh()->roles()->count());

        $this->artisan('roles:backfill-legacy')->assertSuccessful();
        $this->assertTrue($recruiter->fresh()->hasRole('Recruiter'));
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPayload(string $title): array
    {
        return [
            'title' => $title,
            'description' => 'Body',
            'location' => 'Remote',
            'category' => 'Engineering',
            'remote_type' => 'remote',
            'salary_min' => 1000,
            'salary_max' => 2000,
            'status' => 'published',
        ];
    }

    private function publishedJob(): Job
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['is_recruiter' => true]);
        $recruiter->assignRole('Recruiter');

        return Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
