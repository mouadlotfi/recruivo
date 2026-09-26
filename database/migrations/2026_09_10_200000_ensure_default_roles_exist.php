<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
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

        // An account with no roles at all cannot be classified from here: one created
        // while these rows were missing and one whose roles an operator deliberately
        // revoked are indistinguishable, and granting silently would undo the
        // revocation. Record them instead, and leave the decision to
        // `php artisan roles:backfill-legacy`, which asks for one.
        $roleless = User::query()->doesntHave('roles')->get(['id', 'email', 'is_recruiter', 'created_at']);

        foreach ($roleless as $user) {
            Log::warning('Account left role-less by the default-roles migration; run roles:backfill-legacy to decide.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_recruiter' => (bool) $user->is_recruiter,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reference rows: dropping them would break every account that depends on
     * them, so this migration is intentionally irreversible.
     */
    public function down(): void {}
};
