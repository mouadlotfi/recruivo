<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\ApplicationStatusEvent;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * `jobs.recruiter_id` used cascadeOnDelete(), and `applications.job_id` cascades
 * in turn, so deleting a recruiter destroyed every job they posted along with the
 * applications and status events on them. This proves jobs are orphaned now.
 */
class RecruiterDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter', 'Admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }

    private function recruiter(Company $company): User
    {
        $recruiter = User::factory()->for($company)->create(['is_recruiter' => true]);
        $recruiter->assignRole('Recruiter');

        return $recruiter;
    }

    public function test_deleting_a_recruiter_orphans_their_jobs_and_preserves_candidate_history(): void
    {
        $company = Company::factory()->create();
        $recruiter = $this->recruiter($company);

        $job = Job::factory()->for($company)->create([
            'recruiter_id' => $recruiter->id,
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $application = Application::factory()->for($job)->create();
        $event = ApplicationStatusEvent::create([
            'application_id' => $application->id,
            'changed_by_user_id' => $recruiter->id,
            'from_status' => ApplicationStatus::Pending->value,
            'to_status' => ApplicationStatus::Shortlisted->value,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->delete("/en/admin/users/{$recruiter->id}")
            ->assertRedirect();

        // The account is gone.
        $this->assertNull(User::find($recruiter->id));

        // The job survives, orphaned rather than deleted, and stays attributed to
        // the company so it can be reassigned.
        $this->assertSame(1, Job::count(), 'Jobs must survive recruiter deletion.');
        $this->assertNull($job->fresh()->recruiter_id);
        $this->assertSame($company->id, $job->fresh()->company_id);

        // Candidate history is intact: the application and its timeline survive,
        // with the deleted recruiter detached from the status event.
        $this->assertNotNull(Application::find($application->id), 'The application record must still exist.');
        $this->assertNotNull($event->fresh());
        $this->assertNull($event->fresh()->changed_by_user_id);
    }

    public function test_other_recruiters_jobs_are_untouched_by_a_recruiter_deletion(): void
    {
        $company = Company::factory()->create();
        $leaving = $this->recruiter($company);
        $staying = $this->recruiter($company);

        $orphanedJob = Job::factory()->for($company)->create(['recruiter_id' => $leaving->id]);
        $keptJob = Job::factory()->for($company)->create(['recruiter_id' => $staying->id]);

        $this->actingAs($this->admin())
            ->delete("/en/admin/users/{$leaving->id}")
            ->assertRedirect();

        $this->assertSame(2, Job::count());
        $this->assertNull($orphanedJob->fresh()->recruiter_id);
        $this->assertSame($staying->id, $keptJob->fresh()->recruiter_id);
    }
}
