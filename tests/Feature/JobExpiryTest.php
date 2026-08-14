<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JobExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_job_casts_closing_date_and_reports_expiry(): void
    {
        $active = Job::factory()->create(['closes_at' => today()]);
        $expired = Job::factory()->create(['closes_at' => today()->subDay()]);
        $openEnded = Job::factory()->create(['closes_at' => null]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $active->closes_at);
        $this->assertFalse($active->isExpired());
        $this->assertTrue($expired->isExpired());
        $this->assertFalse($openEnded->isExpired());
    }

    public function test_published_scope_excludes_expired_jobs(): void
    {
        $expired = Job::factory()->create(['closes_at' => today()->subDay(), 'status' => JobStatus::Published->value]);
        $today = Job::factory()->create(['closes_at' => today(), 'status' => JobStatus::Published->value]);
        $tomorrow = Job::factory()->create(['closes_at' => today()->addDay(), 'status' => JobStatus::Published->value]);
        $openEnded = Job::factory()->create(['closes_at' => null, 'status' => JobStatus::Published->value]);
        $draft = Job::factory()->create(['closes_at' => null, 'status' => JobStatus::Draft->value]);

        $ids = Job::published()->pluck('id')->all();

        $this->assertNotContains($expired->id, $ids);
        $this->assertContains($today->id, $ids);
        $this->assertContains($tomorrow->id, $ids);
        $this->assertContains($openEnded->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_expired_job_detail_returns_404_for_public_web(): void
    {
        $expired = Job::factory()->create(['closes_at' => today()->subDay(), 'status' => JobStatus::Published->value]);

        $this->get('/en/jobs/'.$expired->id)->assertNotFound();
    }

    public function test_expired_job_detail_returns_404_for_public_api(): void
    {
        $expired = Job::factory()->create(['closes_at' => today()->subDay(), 'status' => JobStatus::Published->value]);

        $this->getJson('/api/jobs/'.$expired->id)->assertNotFound();
    }

    public function test_web_application_to_expired_job_is_rejected(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $expired = Job::factory()->create(['closes_at' => today()->subDay(), 'status' => JobStatus::Published->value]);

        $this->actingAs($candidate)
            ->post('/en/jobs/'.$expired->id.'/apply', [
                'resume_source' => 'profile',
                'cover_letter' => 'Cover letter text.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_api_application_to_expired_job_is_rejected(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        Sanctum::actingAs($candidate);
        $expired = Job::factory()->create(['closes_at' => today()->subDay(), 'status' => JobStatus::Published->value]);

        $this->postJson('/api/jobs/'.$expired->id.'/apply', ['cover_letter' => 'Hello'])
            ->assertNotFound();

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_recruiter_can_still_manage_an_expired_job(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'closes_at' => today()->subDay(),
            'status' => JobStatus::Published->value,
        ]);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id)
            ->assertOk();
    }

    public function test_candidate_policy_cannot_view_expired_job(): void
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $job = Job::factory()->create(['closes_at' => today()->subDay(), 'status' => JobStatus::Published->value]);

        $this->assertFalse((new \App\Policies\JobPolicy())->view($candidate, $job));
    }

    public function test_job_create_accepts_future_or_today_closing_date(): void
    {
        $recruiter = $this->makeRecruiter();
        $data = $this->validJobData(['closes_at' => today()->toDateString()]);

        $this->actingAs($recruiter)
            ->post('/en/recruiter/jobs', $data)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // SQLite stores the date cast as "Y-m-d 00:00:00" (model default date format)
        $this->assertDatabaseHas('jobs', ['closes_at' => today()->toDateTimeString()]);
    }

    public function test_job_create_rejects_past_closing_date(): void
    {
        $recruiter = $this->makeRecruiter();

        $this->actingAs($recruiter)
            ->post('/en/recruiter/jobs', $this->validJobData(['closes_at' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors('closes_at');

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_job_create_allows_missing_closing_date(): void
    {
        $recruiter = $this->makeRecruiter();

        $this->actingAs($recruiter)
            ->post('/en/recruiter/jobs', $this->validJobData([]))
            ->assertSessionHasNoErrors();
    }

    public function test_job_update_cannot_keep_past_closing_date(): void
    {
        $recruiter = $this->makeRecruiter();
        $job = $this->makePublishedJob($recruiter, ['closes_at' => today()->subDay()->toDateString()]);

        $this->actingAs($recruiter)
            ->patch('/en/recruiter/jobs/'.$job->id, ['closes_at' => today()->subDay()->toDateString()])
            ->assertSessionHasErrors('closes_at');
    }

    public function test_toggle_expired_job_to_published_is_rejected(): void
    {
        $recruiter = $this->makeRecruiter();
        $job = $this->makePublishedJob($recruiter, ['closes_at' => today()->subDay()->toDateString()]);

        $this->actingAs($recruiter)
            ->post('/en/recruiter/jobs/'.$job->id.'/toggle')
            ->assertSessionHas('error');
    }

    public function test_api_toggle_expired_job_is_rejected_with_422(): void
    {
        $recruiter = $this->makeRecruiter();
        $job = $this->makePublishedJob($recruiter, ['closes_at' => today()->subDay()->toDateString()]);

        Sanctum::actingAs($recruiter);

        $this->postJson('/api/recruiter/jobs/'.$job->id.'/toggle')
            ->assertStatus(422);

        $this->assertDatabaseHas('jobs', [
            'id' => $job->id,
            'status' => JobStatus::Published->value,
        ]);
    }

    public function test_api_job_create_and_resource_expose_closes_at(): void
    {
        $recruiter = $this->makeRecruiter();
        $data = $this->validJobData(['closes_at' => today()->addDays(5)->toDateString()]);

        $response = $this->actingAs($recruiter)
            ->postJson('/api/recruiter/jobs', $data)
            ->assertCreated()
            ->assertJsonPath('data.closes_at', today()->addDays(5)->toDateString())
            ->assertJsonPath('data.is_expired', false);
    }

    public function test_job_forms_include_native_closing_date_field(): void
    {
        $recruiter = $this->makeRecruiter();
        $job = $this->makePublishedJob($recruiter);

        $this->actingAs($recruiter)->get('/en/recruiter/jobs/create')
            ->assertOk()
            ->assertSee('name="closes_at"', false)
            ->assertSee('type="date"', false)
            ->assertSee('min="'.today()->toDateString().'"', false);

        $this->actingAs($recruiter)->get('/en/recruiter/jobs/'.$job->id.'/edit')
            ->assertOk()
            ->assertSee('name="closes_at"', false)
            ->assertSee('type="date"', false);
    }

    public function test_recruiter_job_index_labels_expired_job(): void
    {
        $recruiter = $this->makeRecruiter();
        $expired = $this->makePublishedJob($recruiter, ['closes_at' => today()->subDay()->toDateString(), 'title' => 'Expired Role']);

        $this->actingAs($recruiter)->get('/en/recruiter/jobs')
            ->assertOk()
            ->assertSee(__('recruiter.expired'))
            ->assertSee('Expired Role');
    }

    public function test_public_job_card_shows_closing_soon_copy(): void
    {
        $job = Job::factory()->create(['closes_at' => today()->addDays(3)->toDateString(), 'status' => JobStatus::Published->value]);

        $this->get('/en/jobs')
            ->assertOk()
            ->assertSee(__('jobs.closing_soon'));
    }

    public function test_open_ended_job_does_not_render_closing_copy(): void
    {
        $job = Job::factory()->create(['closes_at' => null, 'status' => JobStatus::Published->value]);

        $this->get('/en/jobs')
            ->assertOk()
            ->assertDontSee(__('jobs.closing_soon'));
    }

    private function makeRecruiter(): User
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        return $recruiter;
    }

    private function validJobData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Platform Engineer',
            'description' => 'Build dependable systems.',
            'location' => 'Remote',
            'salary_min' => 90000,
            'salary_max' => 120000,
            'category' => 'Engineering',
            'remote_type' => 'remote',
            'status' => 'published',
        ], $overrides);
    }

    private function makePublishedJob(User $recruiter, array $overrides = []): Job
    {
        return Job::factory()
            ->for($recruiter->company)
            ->for($recruiter, 'recruiter')
            ->create(array_merge([
                'status' => JobStatus::Published->value,
                'published_at' => now(),
            ], $overrides));
    }
}
