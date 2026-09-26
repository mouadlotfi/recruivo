<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jobs')->where('remote_type', 'on-site')->update(['remote_type' => 'onsite']);

        Schema::table('jobs', function (Blueprint $table) {
            $table->string('remote_type')->default('onsite')->change();
        });
    }

    public function down(): void
    {
        DB::table('jobs')->where('remote_type', 'onsite')->update(['remote_type' => 'on-site']);

        Schema::table('jobs', function (Blueprint $table) {
            $table->string('remote_type')->default('on-site')->change();
        });
    }
};
