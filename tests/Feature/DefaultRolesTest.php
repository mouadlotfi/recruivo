<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DefaultRolesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Registration assigns one of these and the navigation switches on them. A
     * deployment runs migrations without seeders, so the rows have to come from
     * the migration: a fresh production database without them left every new
     * account with no role at all.
     */
    public function test_default_roles_exist_after_migrating(): void
    {
        $this->assertSame(
            ['Admin', 'Candidate', 'Recruiter'],
            Role::orderBy('name')->pluck('name')->all()
        );
    }

    /**
     * A missing role must fail loudly: skipping the assignment quietly is what
     * produced accounts that the navigation and role middleware treat as nobody.
     */
    public function test_registration_fails_loudly_when_the_role_is_missing(): void
    {
        Role::where('name', 'Candidate')->delete();

        $this->postJson('/api/auth/register', [
            'account_type' => 'candidate',
            'name' => 'Roleless Candidate',
            'email' => 'roleless@example.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'ValidPass123!',
        ])->assertStatus(500);
    }
}
