<?php

namespace Tests\Feature\Api\Recruiter;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * `status` is optional on a job update, and the API used to read its absence as
 * "not published": it cleared `published_at`, which silently dropped the job out
 * of every public listing while the recruiter saw a successful update.
 */
class JobUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    private function recruiter(): User
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['is_recruiter' => true]);
        $recruiter->assignRole('Recruiter');

        return $recruiter;
    }

    private function job(User $recruiter, JobStatus $status, ?string $publishedAt): Job
    {
        return Job::factory()->for($recruiter->company)->create([
            'recruiter_id' => $recruiter->id,
            'status' => $status->value,
            'published_at' => $publishedAt,
            'closes_at' => null,
        ]);
    }

    public function test_an_update_without_a_status_keeps_the_job_published_and_publicly_listed(): void
    {
        $recruiter = $this->recruiter();
        $job = $this->job($recruiter, JobStatus::Published, now()->subDay()->toDateTimeString());

        Sanctum::actingAs($recruiter);

        $this->putJson("/api/recruiter/jobs/{$job->id}", ['title' => 'Senior Backend Engineer (updated)'])
            ->assertOk();

        $job->refresh();

        $this->assertSame(JobStatus::Published, $job->status);
        $this->assertNotNull($job->published_at, 'Omitting status must not clear published_at.');

        // The observable consequence: the job is still on the public surface.
        $this->getJson('/api/jobs')
            ->assertOk()
            ->assertJsonFragment(['id' => $job->id]);
    }

    public function test_publishing_a_draft_stamps_the_publication_date(): void
    {
        $recruiter = $this->recruiter();
        $job = $this->job($recruiter, JobStatus::Draft, null);

        Sanctum::actingAs($recruiter);

        $this->putJson("/api/recruiter/jobs/{$job->id}", ['status' => JobStatus::Published->value])
            ->assertOk();

        $job->refresh();

        $this->assertSame(JobStatus::Published, $job->status);
        $this->assertNotNull($job->published_at);
    }

    public function test_drafting_a_published_job_keeps_its_publication_date(): void
    {
        // Mirrors the web surface: the timestamp records when the job was first
        // published and is not rewritten on the way back to draft.
        $recruiter = $this->recruiter();
        $publishedAt = now()->subDays(3)->startOfSecond();
        $job = $this->job($recruiter, JobStatus::Published, $publishedAt->toDateTimeString());

        Sanctum::actingAs($recruiter);

        $this->putJson("/api/recruiter/jobs/{$job->id}", ['status' => JobStatus::Draft->value])
            ->assertOk();

        $job->refresh();

        $this->assertSame(JobStatus::Draft, $job->status);
        $this->assertNotNull($job->published_at, 'Drafting must not erase the original publication date.');
        $this->assertTrue($publishedAt->equalTo($job->published_at));

        // A draft is not publicly listed, whatever the timestamp says.
        $this->getJson('/api/jobs')->assertOk()->assertJsonMissing(['id' => $job->id]);
    }
}
