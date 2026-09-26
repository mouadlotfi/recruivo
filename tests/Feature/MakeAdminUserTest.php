<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MakeAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_admin_user_via_cli_options(): void
    {
        $this->artisan('make:admin', [
            '--name' => 'Super Admin',
            '--email' => 'admin.test@example.com',
            '--password' => 'supersecret123',
        ])
            ->expectsOutputToContain('Administrator account created successfully')
            ->assertSuccessful();

        $user = User::where('email', 'admin.test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Super Admin', $user->name);
        $this->assertTrue(Hash::check('supersecret123', $user->password));
        $this->assertTrue($user->hasRole('Admin'));
        $this->assertFalse($user->is_demo);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_can_create_admin_user_interactively(): void
    {
        $this->artisan('make:admin')
            ->expectsQuestion('Administrator name', 'Jane Admin')
            ->expectsQuestion('Administrator email address', 'jane.admin@example.com')
            ->expectsQuestion('Administrator password (minimum 8 characters)', 'password1234')
            ->expectsOutputToContain('Administrator account created successfully')
            ->assertSuccessful();

        $user = User::where('email', 'jane.admin@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Jane Admin', $user->name);
        $this->assertTrue($user->hasRole('Admin'));
    }

    public function test_fails_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->artisan('make:admin', [
            '--name' => 'Another Admin',
            '--email' => 'existing@example.com',
            '--password' => 'password123',
        ])
            ->expectsOutputToContain('The email has already been taken.')
            ->assertFailed();
    }

    public function test_fails_when_password_is_too_short(): void
    {
        $this->artisan('make:admin', [
            '--name' => 'Short Pass',
            '--email' => 'short@example.com',
            '--password' => 'short',
        ])
            ->expectsOutputToContain('The password field must be at least 8 characters.')
            ->assertFailed();
    }
}
