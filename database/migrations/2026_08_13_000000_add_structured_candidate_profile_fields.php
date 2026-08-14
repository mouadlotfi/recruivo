<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->json('languages_data')->nullable()->after('languages');
            $table->json('profile_links')->nullable()->after('website_url');
            $table->json('experiences')->nullable()->after('profile_links');
            $table->json('educations')->nullable()->after('experiences');
        });

        DB::table('candidate_profiles')->orderBy('id')->each(function ($profile) {
            $languages = collect(preg_split('/[,\n]+/', (string) $profile->languages))
                ->map(fn ($language) => trim($language))
                ->filter()
                ->map(fn ($language) => ['language' => $language, 'proficiency' => 'intermediate'])
                ->values()
                ->all();

            $links = collect([
                ['name' => 'LinkedIn', 'url' => $profile->linkedin_url],
                ['name' => 'Portfolio', 'url' => $profile->portfolio_url],
                ['name' => 'GitHub', 'url' => $profile->github_url],
                ['name' => 'Website', 'url' => $profile->website_url],
            ])->filter(fn ($link) => filled($link['url']))->values()->all();

            $experiences = filled($profile->experience)
                ? [[
                    'job_title' => 'Professional Experience',
                    'company_name' => 'Previous experience',
                    'location' => '',
                    'start_date' => null,
                    'end_date' => null,
                    'is_current' => false,
                    'description' => $profile->experience,
                ]]
                : [];

            $educations = filled($profile->education)
                ? [[
                    'school' => 'Education',
                    'degree' => 'Qualification',
                    'field_of_study' => 'General studies',
                    'start_date' => null,
                    'end_date' => null,
                    'is_current' => false,
                    'description' => $profile->education,
                ]]
                : [];

            DB::table('candidate_profiles')->where('id', $profile->id)->update([
                'languages_data' => json_encode($languages),
                'profile_links' => json_encode($links),
                'experiences' => json_encode($experiences),
                'educations' => json_encode($educations),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table) {
            $table->dropColumn(['languages_data', 'profile_links', 'experiences', 'educations']);
        });
    }
};
