<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DemoEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_shares_is_demo_environment_prop_when_in_demo_mode(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('app.is_demo', true);

        $response = $this->get('/en');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('isDemoEnvironment', true)
                ->has('translations.en.demo_environment_badge')
                ->has('translations.en.demo_environment_notice')
            );
    }

    public function test_inertia_shares_is_demo_environment_false_in_production(): void
    {
        App::detectEnvironment(fn () => 'production');
        Config::set('app.is_demo', false);

        $response = $this->get('/en');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('isDemoEnvironment', false)
            );
    }

    public function test_demo_shares_the_production_url(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('app.is_demo', true);
        Config::set('app.production_url', 'https://recruivo.tech');

        $response = $this->get('/en');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('isDemoEnvironment', true)
                ->where('productionUrl', 'https://recruivo.tech')
                ->has('translations.en.demo_production_link')
                ->has('translations.fr.demo_production_link')
            );
    }

    public function test_production_does_not_share_the_production_url(): void
    {
        App::detectEnvironment(fn () => 'production');
        Config::set('app.is_demo', false);
        Config::set('app.production_url', 'https://recruivo.tech');

        $response = $this->get('/en');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('productionUrl', null)
            );
    }

    public function test_registration_is_disabled_on_the_demo_web_route(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        App::detectEnvironment(fn () => 'demo');
        Config::set('app.is_demo', true);

        $response = $this->from('/en/register')->post('/en/register', [
            'account_type' => 'candidate',
            'name' => 'Cami Candidate',
            'email' => 'candidate@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
        ]);

        $response->assertRedirect('/en/register')
            ->assertSessionHasErrors('email');

        $this->assertSame(
            __('auth.registration_disabled_on_demo'),
            session('errors')->first('email')
        );

        $this->assertDatabaseMissing('users', ['email' => 'candidate@example.com']);
    }

    public function test_register_page_exposes_the_disabled_notice_on_demo(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('app.is_demo', true);

        $response = $this->get('/en/register');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('isDemoEnvironment', true)
                ->where('labels.registration_disabled_notice', __('auth.registration_disabled_on_demo'))
            );
    }

    public function test_registration_is_disabled_on_the_demo_api_route(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('app.is_demo', true);

        $response = $this->postJson('/api/auth/register', [
            'account_type' => 'candidate',
            'name' => 'Cami Candidate',
            'email' => 'candidate@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', __('auth.registration_disabled_on_demo'));

        $this->assertDatabaseMissing('users', ['email' => 'candidate@example.com']);
    }

    public function test_registration_still_works_outside_the_demo_environment(): void
    {
        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);

        App::detectEnvironment(fn () => 'production');
        Config::set('app.is_demo', false);

        $response = $this->postJson('/api/auth/register', [
            'account_type' => 'candidate',
            'name' => 'Cami Candidate',
            'email' => 'candidate@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'candidate@example.com']);
    }

    public function test_login_still_works_on_the_demo_environment(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);

        App::detectEnvironment(fn () => 'demo');
        Config::set('app.is_demo', true);

        $user = User::factory()->create([
            'email' => 'demo-visitor@example.com',
            'password' => bcrypt('ValidPass123!'),
        ]);
        $user->assignRole('Candidate');

        $response = $this->post('/en/login', [
            'email' => 'demo-visitor@example.com',
            'password' => 'ValidPass123!',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_page_offers_the_demo_accounts_on_the_demo(): void
    {
        App::detectEnvironment(fn () => 'demo');
        Config::set('app.is_demo', true);

        $response = $this->get('/en/login');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/Login', false)
                ->has('demoAccounts', 3)
                ->where('demoAccounts.0.email', config('app.demo_accounts.candidate.email'))
                ->where('demoAccounts.0.password', config('app.demo_accounts.candidate.password'))
                ->where('demoAccounts.0.label', __('auth.demo_account_candidate'))
                ->where('demoAccounts.1.email', config('app.demo_accounts.recruiter.email'))
                ->where('demoAccounts.2.email', config('app.demo_accounts.admin.email'))
            );
    }

    public function test_login_page_hides_the_demo_accounts_outside_the_demo(): void
    {
        App::detectEnvironment(fn () => 'production');
        Config::set('app.is_demo', false);

        $response = $this->get('/en/login');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/Login', false)
                ->has('demoAccounts', 0)
            );
    }

    public function test_demo_accounts_are_flagged_with_is_demo(): void
    {
        $this->artisan('db:seed', ['--force' => true]);

        $admin = User::where('email', config('app.demo_accounts.admin.email'))->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_demo);
        $this->assertTrue($admin->hasRole('Admin'));

        $recruiter = User::where('email', config('app.demo_accounts.recruiter.email'))->first();
        $this->assertNotNull($recruiter);
        $this->assertTrue($recruiter->is_demo);
        $this->assertTrue($recruiter->hasRole('Recruiter'));

        $candidate = User::where('email', config('app.demo_accounts.candidate.email'))->first();
        $this->assertNotNull($candidate);
        $this->assertTrue($candidate->is_demo);
        $this->assertTrue($candidate->hasRole('Candidate'));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
