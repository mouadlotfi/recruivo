<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `jobs.recruiter_id` used `cascadeOnDelete()`, and `applications.job_id`
     * cascades in turn, so deleting a recruiter account destroyed every job they
     * posted together with the applications and status events of every candidate
     * who applied to those jobs.
     *
     * Jobs are attributed to the company (`jobs.company_id`), so the recruiter
     * reference becomes nullable and orphans the job instead of erasing candidate
     * history. The read paths already tolerate a missing recruiter
     * (Admin\JobController, Admin/Jobs.vue) and application notifications fall
     * back to the company's other recruiters.
     */
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['recruiter_id']);
            $table->unsignedBigInteger('recruiter_id')->nullable()->change();
            $table->foreign('recruiter_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['recruiter_id']);
            $table->unsignedBigInteger('recruiter_id')->nullable(false)->change();
            $table->foreign('recruiter_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
