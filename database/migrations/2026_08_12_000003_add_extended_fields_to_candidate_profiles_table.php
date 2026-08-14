<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->text('education')->nullable()->after('experience');
            $table->text('languages')->nullable()->after('education');
            $table->string('github_url')->nullable()->after('portfolio_url');
            $table->string('website_url')->nullable()->after('github_url');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropColumn(['education', 'languages', 'github_url', 'website_url']);
        });
    }
};
