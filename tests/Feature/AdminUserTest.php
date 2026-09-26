<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserTest extends TestCase
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
        $admin = User::factory()->create(['name' => 'System Admin']);
        $admin->assignRole('Admin');

        return $admin;
    }

    public function test_only_admin_can_view_users_list_and_sees_candidate_and_company_links(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create(['name' => 'Tech Corp', 'slug' => 'tech-corp']);
        $recruiter = User::factory()->for($company)->create(['name' => 'Rick Recruiter', 'is_recruiter' => true]);
        $recruiter->assignRole('Recruiter');

        $candidate = User::factory()->create(['name' => 'Cody Candidate']);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create(['headline' => 'Full-Stack Developer']);

        $this->get('/en/admin/users')->assertRedirect('/en/login');

        $this->actingAs($candidate)->get('/en/admin/users')->assertForbidden();
        $this->actingAs($recruiter)->get('/en/admin/users')->assertForbidden();

        $this->actingAs($admin)
            ->get('/en/admin/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Users', false)
                ->has('users', 3)
                ->where('users.0.candidate_url', fn ($url) => str_contains((string) $url, '/admin/users/'))
                ->where('users.1.company.slug', 'tech-corp')
            );
    }

    public function test_admin_can_view_candidate_profile_preview(): void
    {
        $admin = $this->admin();
        $candidate = User::factory()->create([
            'name' => 'Alice Engineer',
            'email' => 'alice@example.com',
            'profile_summary' => 'Passionate about cloud architecture.',
        ]);
        $candidate->assignRole('Candidate');
        CandidateProfile::factory()->for($candidate)->create([
            'headline' => 'Senior Cloud Architect',
            'skills' => 'AWS, Terraform, Go',
        ]);

        $this->actingAs($admin)
            ->get("/en/admin/users/{$candidate->id}/candidate")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Profile/Preview', false)
                ->where('applicant.name', 'Alice Engineer')
                ->where('applicant.candidateProfile.headline', 'Senior Cloud Architect')
                ->where('applicant.candidateProfile.skills', 'AWS, Terraform, Go')
                ->where('backUrl', fn ($url) => str_contains((string) $url, '/admin/users'))
            );
    }

    public function test_admin_viewing_non_candidate_aborts_404(): void
    {
        $admin = $this->admin();
        $recruiter = User::factory()->create();
        $recruiter->assignRole('Recruiter');

        $this->actingAs($admin)
            ->get("/en/admin/users/{$recruiter->id}/candidate")
            ->assertNotFound();
    }

    public function test_admin_visiting_jobs_or_companies_is_redirected_to_admin_dashboard(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/en/jobs')
            ->assertRedirect('/en/admin/dashboard');

        $this->actingAs($admin)
            ->get('/en/companies')
            ->assertRedirect('/en/admin/dashboard');
    }
}
