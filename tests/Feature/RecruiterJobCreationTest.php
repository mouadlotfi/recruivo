<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecruiterJobCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_every_new_job_field_is_required_in_form_and_server_validation(): void
    {
        $recruiter = $this->makeRecruiter();

        $response = $this->actingAs($recruiter)->get('/en/recruiter/jobs/create')
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Recruiter/Jobs/Create', false)
        );

        $jobForm = File::get(resource_path('js/Components/Recruiter/JobForm.vue'));
        $this->assertStringContainsString('id="salary_min"', $jobForm);
        $this->assertStringContainsString('id="salary_max"', $jobForm);
        $this->assertStringContainsString('id="category"', $jobForm);

        $this->actingAs($recruiter)->post('/en/recruiter/jobs', [])
            ->assertSessionHasErrors(['title', 'description', 'location', 'salary_min', 'salary_max', 'category', 'remote_type', 'status']);
    }

    public function test_maximum_salary_cannot_be_lower_than_minimum_salary(): void
    {
        $recruiter = $this->makeRecruiter();

        $this->actingAs($recruiter)->post('/en/recruiter/jobs', [
            'title' => 'Platform Engineer',
            'description' => 'Build dependable systems.',
            'location' => 'Remote',
            'salary_min' => 90000,
            'salary_max' => 80000,
            'category' => 'Engineering',
            'remote_type' => 'remote',
            'status' => 'published',
        ])->assertSessionHasErrors('salary_max');

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_inertia_job_creation_redirects_to_the_recruiter_jobs_page(): void
    {
        $recruiter = $this->makeRecruiter();

        $this->actingAs($recruiter)
            ->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post('/en/recruiter/jobs', [
                'title' => 'Platform Engineer',
                'description' => 'Build dependable systems.',
                'location' => 'Remote',
                'salary_min' => 80000,
                'salary_max' => 120000,
                'category' => 'Software Development',
                'remote_type' => 'remote',
                'status' => 'published',
            ])
            ->assertStatus(302)
            ->assertRedirect('/en/recruiter/jobs');

        $this->flushHeaders();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Jobs/Index', false)
            );
    }

    public function test_inertia_job_edit_redirects_to_the_recruiter_jobs_page(): void
    {
        $recruiter = $this->makeRecruiter();
        $job = Job::factory()->for($recruiter->company)->for($recruiter, 'recruiter')->create();

        $this->actingAs($recruiter)
            ->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
            ->patch("/en/recruiter/jobs/{$job->id}", [
                'title' => 'Updated Platform Engineer',
                'description' => 'Build dependable systems.',
                'location' => 'Remote',
                'salary_min' => 85000,
                'salary_max' => 125000,
                'category' => 'Software Development',
                'remote_type' => 'remote',
                'status' => 'published',
            ])
            ->assertStatus(303)
            ->assertRedirect('/en/recruiter/jobs');

        $this->flushHeaders();

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Jobs/Index', false)
            );
    }

    private function makeRecruiter(): User
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        return $recruiter;
    }
}
