<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add a field to track if this is a recruiter account
            $table->boolean('is_recruiter')->default(false)->after('company_id');
            
            // Make personal fields nullable since recruiters won't have them
            $table->string('name')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->string('location')->nullable()->change();
            $table->text('profile_summary')->nullable()->change();
            $table->string('profile_picture_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_recruiter');
            
            // Revert personal fields to required
            $table->string('name')->nullable(false)->change();
            $table->string('phone')->nullable(false)->change();
            $table->string('location')->nullable(false)->change();
            $table->text('profile_summary')->nullable(false)->change();
            $table->string('profile_picture_path')->nullable(false)->change();
        });
    }
};