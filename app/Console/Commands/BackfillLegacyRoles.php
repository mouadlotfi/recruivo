<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Grants a role to accounts the default-roles migration deliberately left alone.
 *
 * That migration cannot tell an account created while the role rows were missing
 * from one whose roles an operator revoked, so it records them and stops. This is
 * the explicit decision it defers to: run it with --dry-run first.
 */
class BackfillLegacyRoles extends Command
{
    protected $signature = 'roles:backfill-legacy {--dry-run : List the accounts that would be granted a role}';

    protected $description = 'Grant the registration role to accounts the default-roles migration left role-less';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Recruiter', 'Candidate'] as $name) {
            if (! Role::where('name', $name)->where('guard_name', 'web')->exists()) {
                $this->error("Role [{$name}] is missing; run the migrations before backfilling.");

                return self::FAILURE;
            }
        }

        $roleless = User::query()->doesntHave('roles')->get();

        if ($roleless->isEmpty()) {
            $this->info('No role-less accounts.');

            return self::SUCCESS;
        }

        $this->table(
            ['id', 'email', 'role', 'created_at'],
            $roleless->map(fn (User $user): array => [
                $user->id,
                $user->email,
                $user->is_recruiter ? 'Recruiter' : 'Candidate',
                optional($user->created_at)->toDateTimeString(),
            ])->all(),
        );

        if ($this->option('dry-run')) {
            $this->info($roleless->count().' account(s) would be granted a role. Nothing was changed.');

            return self::SUCCESS;
        }

        foreach ($roleless as $user) {
            $user->assignRole($user->is_recruiter ? 'Recruiter' : 'Candidate');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info($roleless->count().' account(s) granted a role.');

        return self::SUCCESS;
    }
}
