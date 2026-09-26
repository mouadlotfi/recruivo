<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL compared these columns case-insensitively through utf8mb4_unicode_ci;
     * PostgreSQL does not. citext restores that: the unique index on
     * users.email and the login/reset lookups keep MySQL's semantics with no
     * query changes anywhere.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('create extension if not exists citext');
        DB::statement('alter table users alter column email type citext using email::citext');
        DB::statement('alter table password_reset_tokens alter column email type citext using email::citext');
    }

    /**
     * Reverting reintroduces case-sensitive email comparison, which is why the
     * extension is deliberately left installed.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('alter table users alter column email type varchar(255) using email::text');
        DB::statement('alter table password_reset_tokens alter column email type varchar(255) using email::text');
    }
};
