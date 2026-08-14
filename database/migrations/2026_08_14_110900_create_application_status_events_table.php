<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->timestamp('created_at');

            $table->index(['application_id', 'created_at']);
        });

        // Backfill one baseline event per existing application (DB facade only — no model events).
        DB::table('applications')->chunkById(500, function ($applications) {
            $rows = $applications->map(fn ($application) => [
                'application_id' => $application->id,
                'changed_by_user_id' => null,
                'from_status' => null,
                'to_status' => $application->status,
                'created_at' => $application->created_at ?? now(),
            ])->all();

            DB::table('application_status_events')->insert($rows);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_events');
    }
};
