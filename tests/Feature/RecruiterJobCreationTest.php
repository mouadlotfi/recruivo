<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($recruiter)->get('/en/recruiter/jobs/create')
            ->assertOk()
            ->assertSee('name="salary_min"', false)
            ->assertSee('name="salary_max"', false)
            ->assertSee('name="category"', false);

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

    private function makeRecruiter(): User
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        return $recruiter;
    }
}