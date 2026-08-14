<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('is_recruiter')->index();
        });

        DB::table('users')
            ->whereIn('email', [
                'admin@recruivo.work',
                'recruiter@recruivo.work',
                'candidate@recruivo.work',
            ])
            ->update(['is_demo' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
