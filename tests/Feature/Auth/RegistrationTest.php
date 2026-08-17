<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
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
            'email' => 'recruiter@example.com', // Personal email for login
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
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
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('message', 'Registration successful! Please check your email to verify your account.');

        $user = User::where('email', 'recruiter@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Recruiter'));
        $this->assertSame('Visionary Labs', $user->name);
        $this->assertSame('Head of Talent', $user->job_title);

        $company = $user->company;
        $this->assertInstanceOf(Company::class, $company);
        $this->assertSame('Visionary Labs', $company->name);
        $this->assertSame('Remote', $company->location);
        $this->assertNull($company->email);
        $this->assertNotEmpty($company->slug);
    }

    public function test_company_registration_form_does_not_render_a_personal_name_field(): void
    {
        $response = $this->get('/en/register')->assertOk();
        $page = $response->getContent();

        $this->assertStringContainsString('"component":"Auth\\/Register"', $page);
        $this->assertStringContainsString('"company_name":"Company name"', $page);

        $registerPage = File::get(resource_path('js/Pages/Auth/Register.vue'));

        $this->assertStringContainsString('id="company_name"', $registerPage);
        $this->assertStringContainsString('name="company[location]"', $registerPage);
        $this->assertStringContainsString('v-show="form.account_type === \'candidate\'"', $registerPage);
        $this->assertStringNotContainsString('id="recruiter_name"', $registerPage);
        $this->assertStringNotContainsString('id="company_email"', $registerPage);
    }

    public function test_company_registration_requires_a_location(): void
    {
        $this->postJson('/api/auth/register', [
            'account_type' => 'company',
            'email' => 'recruiter@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
            'company' => ['name' => 'Visionary Labs'],
        ])->assertUnprocessable()->assertJsonValidationErrors('company.location');
    }

    public function test_company_email_is_not_displayed_on_company_or_profile_pages(): void
    {
        $company = Company::factory()->create(['email' => 'private@company.test']);
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');

        $this->get('/en/companies/'.$company->slug)
            ->assertOk()
            ->assertDontSee('private@company.test');

        $this->actingAs($recruiter)->get('/en/profile')
            ->assertOk()
            ->assertDontSee('id="company_email"', false)
            ->assertDontSee('private@company.test');
    }
}
