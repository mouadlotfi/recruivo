<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('created_at', 'users_created_at_index');
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->index('published_at', 'jobs_published_at_index');
            $table->index(['status', 'published_at'], 'jobs_status_published_at_index');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->index('created_at', 'applications_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_created_at_index');
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropIndex('jobs_published_at_index');
            $table->dropIndex('jobs_status_published_at_index');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex('applications_created_at_index');
        });
    }
};
