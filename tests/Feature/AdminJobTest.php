<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Candidate', 'Recruiter', 'Admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_only_the_admin_role_can_open_the_jobs_page(): void
    {
        $this->get('/en/admin/jobs')->assertRedirect('/en/login');

        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');
        $this->actingAs($candidate)->get('/en/admin/jobs')->assertForbidden();

        $recruiter = User::factory()->create();
        $recruiter->assignRole('Recruiter');
        $this->actingAs($recruiter)->get('/en/admin/jobs')->assertForbidden();

        $this->actingAs($this->admin())
            ->get('/en/admin/jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Jobs', false)
                ->has('jobs')
                ->has('pagination')
                ->has('filters')
                ->has('statusOptions')
                ->where('labels.sidebar_jobs', 'Jobs')
            );
    }

    public function test_admin_jobs_search_status_no_application_and_job_filters_serialize_counts(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create(['name' => 'Acme Search']);
        $recruiter = User::factory()->for($company)->create(['name' => 'Rita Recruiter']);
        $recruiter->assignRole('Recruiter');
        $candidate = User::factory()->create();
        $candidate->assignRole('Candidate');

        $matchingJob = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'title' => 'Needle Platform Engineer',
            'location' => 'Paris, France',
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);
        Application::factory()->for($candidate, 'candidate')->for($matchingJob)->create();

        $draftJob = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'title' => 'Draft Role',
            'status' => JobStatus::Draft->value,
            'published_at' => null,
        ]);
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'title' => 'Other Published Role',
            'status' => JobStatus::Published->value,
            'published_at' => now()->subDays(2),
        ]);

        $this->actingAs($admin)
            ->get('/en/admin/jobs?search=Needle')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 1)
                ->where('filters.search', 'Needle')
                ->where('jobs.0.id', $matchingJob->id)
                ->where('jobs.0.company.name', $company->name)
                ->where('jobs.0.recruiter.email', $recruiter->email)
                ->where('jobs.0.status', JobStatus::Published->value)
                ->where('jobs.0.status_label', 'Published')
                ->where('jobs.0.applications_count', 1)
            );

        $this->actingAs($admin)
            ->get('/en/admin/jobs?status=draft')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 1)
                ->where('filters.status', JobStatus::Draft->value)
                ->where('jobs.0.id', $draftJob->id)
                ->where('jobs.0.applications_count', 0)
            );

        $this->actingAs($admin)
            ->get('/en/admin/jobs?no_applications=1')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 2)
                ->where('filters.filter', 'no_applications')
                ->where('filters.no_applications', true)
                ->where('jobs', fn ($jobs): bool => $jobs->every(
                    fn (array $job): bool => $job['applications_count'] === 0,
                ))
            );

        $this->actingAs($admin)
            ->get('/en/admin/jobs?filter=no_applications')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 2)
                ->where('filters.no_applications', true)
            );

        $this->actingAs($admin)
            ->get('/en/admin/jobs?job='.$matchingJob->id)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 1)
                ->where('filters.job', $matchingJob->id)
                ->where('jobs.0.id', $matchingJob->id)
            );
    }

    public function test_admin_jobs_are_paginated_and_have_no_edit_or_delete_endpoint(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        $jobs = Job::factory()->count(21)->for($company)->for($recruiter, 'recruiter')->create([
            'status' => JobStatus::Published->value,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/en/admin/jobs')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pagination.total', 21)
                ->where('pagination.per_page', 20)
                ->where('pagination.current_page', 1)
                ->where('pagination.last_page', 2)
                ->has('jobs', 20)
            );

        $this->actingAs($admin)->get('/en/admin/jobs/'.$jobs->first()->id)->assertNotFound();
        $this->actingAs($admin)->delete('/en/admin/jobs/'.$jobs->first()->id)->assertStatus(405);
    }

    public function test_admin_jobs_page_resolves_french_labels_and_status_labels(): void
    {
        $this->actingAs($this->admin())
            ->get('/fr/admin/jobs?status=published')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('labels.job_management_title', 'Gestion des emplois')
                ->where('labels.sidebar_jobs', 'Emplois')
                ->where('filters.status', JobStatus::Published->value)
                ->where('statusOptions.0.label', 'Brouillon')
                ->where('statusOptions.1.label', 'Publié')
            );
    }

    public function test_company_pages_opened_from_admin_jobs_point_back_at_admin_jobs(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create(['name' => 'Hint Co', 'slug' => 'hint-co']);
        $recruiter = User::factory()->for($company)->create();
        $recruiter->assignRole('Recruiter');
        Job::factory()->for($company)->for($recruiter, 'recruiter')->create(['status' => JobStatus::Published->value]);

        $this->actingAs($admin)
            ->get('/en/companies/hint-co?from=admin_jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Companies/Show', false)
                ->where('adminBack.url', '/en/admin/jobs')
                ->where('adminBack.label', 'Back to Admin Jobs')
            );

        $this->actingAs($admin)
            ->get('/fr/companies/hint-co?from=admin_jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('adminBack.url', '/fr/admin/jobs')
                ->where('adminBack.label', 'Retour aux offres Admin')
            );

        // Any other entry point - the users list, a job detail page, a bookmark -
        // keeps the historical admin users link the view falls back to.
        $this->actingAs($admin)
            ->get('/en/companies/hint-co')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Companies/Show', false)
                ->where('adminBack', null)
            );

        $jobsPage = File::get(resource_path('js/Pages/Admin/Jobs.vue'));
        $this->assertStringContainsString("'?from=admin_jobs'", $jobsPage);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        return $admin;
    }
}
