<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Candidate', 'guard_name' => 'web']);
    }

    public function test_users_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'signin@example.com',
        ]);

        $user->assignRole('Candidate');

        $response = $this->postJson('/api/auth/login', [
            'email' => 'signin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['user', 'token']);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'signin-fail@example.com',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'signin-fail@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'These credentials do not match our records.');
    }
}

