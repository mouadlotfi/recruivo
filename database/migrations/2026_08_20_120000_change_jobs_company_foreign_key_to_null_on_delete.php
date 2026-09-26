<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Previously `jobs.company_id` used `cascadeOnDelete()`. Because
     * `applications.job_id` also cascades, deleting a Company would destroy
     * every job and every application/status event for that company.
     *
     * This switches the company relationship to `nullOnDelete()` so that a
     * company deletion (now unreachable in the app, but a latent risk via
     * future code, console, or manual DB work) orphans its jobs instead of
     * wiping the job + application history.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }
};
