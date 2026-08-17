<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InertiaUiContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Recruiter', 'guard_name' => 'web']);
    }

    public function test_public_job_show_keeps_accessible_heading_outside_the_left_card_stack(): void
    {
        $page = File::get(resource_path('js/Pages/Jobs/Show.vue'));

        $this->assertStringContainsString('<h2 class="sr-only">{{ labels.job_details }}</h2>', $page);
        $this->assertStringContainsString(':class="remoteTypeClass"', $page);
        $this->assertStringContainsString('const remoteTypeClass = computed', $page);
        $this->assertMatchesRegularExpression(
            '/<div class="lg:col-span-2">\s*<h2 class="sr-only">\{\{ labels\.job_details \}\}<\/h2>\s*<div class="space-y-6">/s',
            $page,
        );
        $this->assertStringNotContainsString('<div class="space-y-6 lg:col-span-2">', $page);
    }

    public function test_profile_preview_header_uses_a_localized_focus_safe_responsive_back_link(): void
    {
        $page = File::get(resource_path('js/Pages/Profile/Preview.vue'));

        $this->assertStringContainsString('import { Head, Link, usePage } from \'@inertiajs/vue3\'', $page);
        $this->assertStringContainsString(':href="localeUrl(\'/profile\')"', $page);
        $this->assertStringContainsString('min-h-11', $page);
        $this->assertStringContainsString('focus-visible:ring-2', $page);
        $this->assertMatchesRegularExpression('/flex flex-col gap-4 md:flex-row md:items-start/', $page);
        $this->assertStringContainsString('md:ml-auto', $page);
        $this->assertStringContainsString('min-w-0 break-words', $page);
        $this->assertStringContainsString('class="break-words text-3xl', $page);
        $this->assertStringNotContainsString('window.location', $page);
    }

    public function test_recruiter_job_summary_exposes_index_metadata_fields(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'location' => 'Paris, France',
            'remote_type' => 'hybrid',
            'category' => 'Engineering',
        ]);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Jobs/Index', false)
                ->where('jobs.0.id', $job->id)
                ->where('jobs.0.location', 'Paris, France')
                ->where('jobs.0.remote_type', 'hybrid')
                ->where('jobs.0.category', 'Engineering')
                ->has('jobs.0.applications_label')
                ->has('jobs.0.posted_label')
                ->has('jobs.0.published_label')
                ->has('jobs.0.closes_label')
            );

        $controller = File::get(base_path('app/Http/Controllers/Recruiter/JobController.php'));
        $types = File::get(resource_path('js/types/index.ts'));
        $index = File::get(resource_path('js/Pages/Recruiter/Jobs/Index.vue'));

        foreach (["'location' => \$job->location", "'remote_type' => \$job->remote_type", "'category' => \$job->category"] as $field) {
            $this->assertStringContainsString($field, $controller);
        }
        foreach (['location: string | null', 'remote_type: string | null', 'category: string | null'] as $field) {
            $this->assertStringContainsString($field, $types);
        }
        foreach (['applications_label', 'posted_label', 'published_label', 'closes_label'] as $field) {
            $this->assertStringContainsString($field, $index);
        }
        $this->assertStringContainsString('data-job-metadata', $index);
        $this->assertStringContainsString('flex-wrap', $index);
        $this->assertStringContainsString('break-words', $index);
        $this->assertStringNotContainsString('truncate', $index);
    }

    public function test_recruiter_job_index_actions_and_metadata_remain_responsive_and_above_the_stretched_link(): void
    {
        $index = File::get(resource_path('js/Pages/Recruiter/Jobs/Index.vue'));

        $this->assertStringContainsString('before:absolute before:inset-0', $index);
        $this->assertStringContainsString('data-job-actions class="relative z-10', $index);
        $this->assertStringContainsString('min-h-11 min-w-11', $index);
        $this->assertStringContainsString('break-words', $index);
        $this->assertStringContainsString('flex-wrap', $index);
        $this->assertStringContainsString('sm:flex-row', $index);
        $this->assertStringContainsString('const pageItems = ref(new Map<number, RecruiterJobSummary[]>', $index);
        $this->assertStringContainsString('pageItems.value.set(currentPage, [...incoming])', $index);
        $this->assertStringContainsString('.sort(([leftPage], [rightPage]) => leftPage - rightPage)', $index);
        $this->assertStringContainsString('const deleteDialog = ref<HTMLElement | null>(null)', $index);
        $this->assertStringContainsString('const deleteCancelButton = ref<HTMLButtonElement | null>(null)', $index);
        $this->assertStringContainsString('onMounted(() => window.addEventListener(\'keydown\', handleWindowKeydown))', $index);
        $this->assertStringContainsString('@keydown="handleDialogKeydown"', $index);
        $this->assertStringContainsString('ref="deleteCancelButton"', $index);
        $this->assertStringContainsString('role="dialog" aria-modal="true"', $index);
    }

    public function test_recruiter_job_detail_uses_date_input_closing_date_without_changing_summary_serialization(): void
    {
        $company = Company::factory()->create();
        $recruiter = User::factory()->for($company)->create(['email_verified_at' => now()]);
        $recruiter->assignRole('Recruiter');
        $job = Job::factory()->for($company)->for($recruiter, 'recruiter')->create([
            'closes_at' => today()->addDays(5),
        ]);

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Jobs/Index', false)
                ->where('jobs.0.closes_at', $job->closes_at->toIso8601String())
            );

        $this->actingAs($recruiter)
            ->get('/en/recruiter/jobs/'.$job->id)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Recruiter/Jobs/Show', false)
                ->where('job.closes_at', $job->closes_at->toDateString())
            );

        $controller = File::get(base_path('app/Http/Controllers/Recruiter/JobController.php'));
        $this->assertStringContainsString("'closes_at' => \$job->closes_at?->toDateString()", $controller);
        $this->assertStringContainsString("'closes_at' => \$job->closes_at?->toIso8601String()", $controller);
    }

    public function test_recruiter_job_details_aligns_to_the_top_of_the_description_column(): void
    {
        $page = File::get(resource_path('js/Pages/Recruiter/Jobs/Show.vue'));

        $this->assertStringContainsString('lg:items-start', $page);
    }
}
