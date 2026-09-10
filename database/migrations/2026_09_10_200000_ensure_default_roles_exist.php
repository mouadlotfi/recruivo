<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * The application assumes these rows exist: registration assigns one of them
     * and the navigation switches on them. A deployment only runs migrations, so
     * a fresh production database had none - and registration skipped the
     * assignment silently, leaving accounts with no role at all.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Admin', 'Recruiter', 'Candidate'] as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Accounts created while the roles were missing: give each the role that
        // registration would have assigned it.
        User::query()->doesntHave('roles')->each(function (User $user): void {
            $user->assignRole($user->is_recruiter ? 'Recruiter' : 'Candidate');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reference rows: dropping them would break every account that depends on
     * them, so this migration is intentionally irreversible.
     */
    public function down(): void {}
};
