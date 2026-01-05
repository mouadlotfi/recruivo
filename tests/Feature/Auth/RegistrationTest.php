<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_candidate_can_register_and_receive_candidate_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'account_type' => 'candidate',
            'name' => 'Cami Candidate',
            'email' => 'candidate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Registration successful! Please check your email to verify your account.');

        $user = User::where('email', 'candidate@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->company_id);
        $this->assertTrue($user->hasRole('Candidate'));
    }

    public function test_company_registration_creates_company_and_assigns_recruiter_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'account_type' => 'company',
            'name' => 'Riley Recruiter',
            'email' => 'recruiter@example.com', // Personal email for login
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company' => [
                'name' => 'Visionary Labs',
                'tagline' => 'Building hiring tools that delight',
                'location' => 'Remote',
                'website_url' => 'https://visionarylabs.test',
                'linkedin_url' => '',
                'size' => '11-50',
                'mission' => 'Unlock fulfilling careers for teams everywhere.',
                'culture' => 'Trust, flexibility, continuous learning.',
                'job_title' => 'Head of Talent',
                'email' => 'contact@visionarylabs.com', // Company email for business
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Registration successful! Please check your email to verify your account.');

        $user = User::where('email', 'recruiter@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Recruiter'));
        $this->assertSame('Head of Talent', $user->job_title);

        $company = $user->company;
        $this->assertInstanceOf(Company::class, $company);
        $this->assertSame('Visionary Labs', $company->name);
        $this->assertSame('Remote', $company->location);
        $this->assertSame('contact@visionarylabs.com', $company->email);
        $this->assertNotEmpty($company->slug);
    }
}

