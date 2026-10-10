<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\User;

/**
 * Factories for the role-assigned users the feature tests need. Test classes used
 * to carry near-identical private copies of these. The semantic differences that
 * existed stay explicit parameters (attached company, `is_recruiter`, an admin's
 * display name) so no test silently changes what it creates.
 */
trait CreatesRoleUsers
{
    /**
     * A recruiter assigned the Recruiter role, attached to the given company (a
     * fresh one is created when none is given).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function makeRecruiter(array $attributes = [], ?Company $company = null): User
    {
        $recruiter = User::factory()
            ->for($company ?? Company::factory()->create())
            ->create($attributes);

        $recruiter->assignRole('Recruiter');

        return $recruiter;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeAdmin(array $attributes = []): User
    {
        $admin = User::factory()->create($attributes);
        $admin->assignRole('Admin');

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makeCandidate(array $attributes = []): User
    {
        $candidate = User::factory()->create(array_merge(['email_verified_at' => now()], $attributes));
        $candidate->assignRole('Candidate');

        return $candidate;
    }
}
