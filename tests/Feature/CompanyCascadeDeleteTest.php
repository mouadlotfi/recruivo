<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The application has no company-delete route, but the jobs.company_id
 * foreign key historically used cascadeOnDelete(), which would (via the
 * applications.job_id cascade) destroy a company's entire job + application
 * history if a company were ever deleted. This test proves the constraint now
 * uses nullOnDelete() so company deletion only orphans jobs, never destroys
 * the historical data.
 */
class CompanyCascadeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_company_orphans_its_jobs_but_preserves_them_and_their_applications(): void
    {
        $company = Company::factory()->create();
        $job = Job::factory()->for($company)->create([
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);
        $application = Application::factory()->for($job)->create();

        $jobsBefore = Job::count();
        $applicationsBefore = Application::count();

        // No route exists; exercise the database constraint directly, the way
        // any future delete path (console, manual op, new feature) would.
        $company->delete();

        // Company is gone.
        $this->assertNull(Company::find($company->id));

        // Jobs and applications are NOT destroyed.
        $this->assertSame($jobsBefore, Job::count(), 'Jobs must survive company deletion.');
        $this->assertSame($applicationsBefore, Application::count(), 'Applications must survive company deletion.');

        // The job is now orphaned (company_id nulled) rather than deleted.
        $this->assertNull($job->fresh()->company_id);
        $this->assertNotNull(Application::find($application->id), 'The application record must still exist.');
    }

    public function test_jobs_for_other_companies_are_unaffected_by_a_company_deletion(): void
    {
        $victim = Company::factory()->create();
        $survivor = Company::factory()->create();

        $victimJob = Job::factory()->for($victim)->create([
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);
        $survivorJob = Job::factory()->for($survivor)->create([
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $victim->delete();

        // Victim's job is orphaned, survivor's job keeps its company.
        $this->assertNull($victimJob->fresh()->company_id);
        $this->assertSame($survivor->id, $survivorJob->fresh()->company_id);
        $this->assertSame(2, Job::count());
    }
}
