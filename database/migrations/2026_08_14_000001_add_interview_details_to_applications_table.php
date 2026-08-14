<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dateTime('interview_at')->nullable()->after('status');
            $table->string('interview_location')->nullable()->after('interview_at');
            $table->string('interview_url', 2048)->nullable()->after('interview_location');
            $table->text('interview_instructions')->nullable()->after('interview_url');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['interview_instructions', 'interview_url', 'interview_location', 'interview_at']);
        });
    }
};
