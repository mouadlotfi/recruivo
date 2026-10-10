<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two migrations each added a unique index on applications(candidate_id, job_id)
 * under a different name and neither dropped the other, so live databases carry
 * both:
 *
 *   - 2025_09_20_211829_add_unique_constraint_to_applications_table.php
 *       unique_candidate_job_application
 *   - 2026_08_12_000000_prevent_duplicate_applications.php
 *       applications_candidate_job_unique
 *
 * Both are history and must not be edited, so this drops the redundant second one
 * and recreates it on the way down.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique('applications_candidate_job_unique');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unique(['candidate_id', 'job_id'], 'applications_candidate_job_unique');
        });
    }
};
