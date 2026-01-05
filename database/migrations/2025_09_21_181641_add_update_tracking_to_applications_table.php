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
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('status_changed')->default(false);
            $table->boolean('notes_added')->default(false);
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('notes_added_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['status_changed', 'notes_added', 'status_changed_at', 'notes_added_at']);
        });
    }
};
