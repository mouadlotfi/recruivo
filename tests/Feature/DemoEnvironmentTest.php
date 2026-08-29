<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia;
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

    public function test_demo_accounts_are_flagged_with_is_demo(): void
    {
        $this->artisan('db:seed', ['--force' => true]);

        $admin = User::where('email', 'admin@recruivo.work')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_demo);
        $this->assertTrue($admin->hasRole('Admin'));

        $recruiter = User::where('email', 'recruiter@recruivo.work')->first();
        $this->assertNotNull($recruiter);
        $this->assertTrue($recruiter->is_demo);
        $this->assertTrue($recruiter->hasRole('Recruiter'));

        $candidate = User::where('email', 'candidate@recruivo.work')->first();
        $this->assertNotNull($candidate);
        $this->assertTrue($candidate->is_demo);
        $this->assertTrue($candidate->hasRole('Candidate'));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
