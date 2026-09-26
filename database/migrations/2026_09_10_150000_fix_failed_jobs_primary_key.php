<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `failed_jobs.id` was declared as a `char(36)` primary key with no default,
     * but the configured failure provider (QUEUE_FAILED_DRIVER=database-uuids,
     * DatabaseUuidFailedJobProvider) inserts only `uuid` - so every insert was
     * rejected with "Field 'id' doesn't have a default value" and failed jobs were
     * never recorded anywhere. The admin dashboard's failed-job count was always
     * zero and nothing surfaced delivery failures.
     *
     * The table cannot be holding rows (the schema rejected every attempt), so
     * recreating it with the shape the provider expects is lossless.
     */
    public function up(): void
    {
        Schema::dropIfExists('failed_jobs');

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }
};
