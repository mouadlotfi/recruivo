<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class JobInfiniteScrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_page_uses_the_native_infinite_scroll_component(): void
    {
        $this->createPublishedJobs(13);

        $response = $this->get('/en/jobs');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Jobs/Index', false)
                ->has('jobs.data', 12)
                ->where('jobs.data.0.title', 'Role 01')
                ->where('jobs.meta.total', 13)
                ->where('jobs.meta.last_page', 2)
                ->where('jobs.meta.next_page_url', config('app.url') . '/en/jobs?page=2')
                ->where('pagination.total', 13)
                ->where('pagination.last_page', 2)
            );

        $jobsPage = File::get(resource_path('js/Pages/Jobs/Index.vue'));
        $this->assertStringContainsString('InfiniteScroll', $jobsPage);
        $this->assertStringContainsString('data="jobs"', $jobsPage);
        $this->assertStringContainsString('manual', $jobsPage);
        $this->assertStringContainsString('only-next', $jobsPage);
        $this->assertStringContainsString('data-infinite-items', $jobsPage);
        $this->assertStringContainsString('labels.show_more', $jobsPage);
        $this->assertStringNotContainsString('Pagination Navigation', $jobsPage);
    }

    public function test_infinite_scroll_header_no_longer_returns_an_html_fragment(): void
    {
        $this->createPublishedJobs(13);

        $response = $this->withHeader('X-Infinite-Scroll', '1')->get('/en/jobs?page=2');

        $response->assertOk();
        $content = $response->getContent();

        // The custom header path is gone — this is the full Inertia shell, not
        // a JSON fragment carrying `html`/`next_url`.
        $this->assertStringContainsString('data-page=', $content);
        $this->assertStringNotContainsString('"html"', $content);
        $this->assertStringNotContainsString('"next_url"', $content);

        $controller = File::get(app_path('Http/Controllers/JobController.php'));
        $this->assertStringNotContainsString('X-Infinite-Scroll', $controller);
        $this->assertStringNotContainsString('jobs.partials.cards', $controller);
    }

    public function test_next_page_scroll_visit_returns_only_the_requested_page_items(): void
    {
        $this->createPublishedJobs(13);

        $version = $this->get('/en/jobs')->assertOk()->viewData('page')['version'];

        $response = $this
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'Jobs/Index',
                'X-Inertia-Partial-Data' => 'jobs',
                'X-Inertia-Infinite-Scroll-Merge-Intent' => 'append',
            ])
            ->get('/en/jobs?page=2');

        $response->assertOk();

        $this->assertSame('Jobs/Index', $response->json('component'));
        $this->assertCount(1, $response->json('props.jobs.data'));
        $this->assertSame('Role 13', $response->json('props.jobs.data.0.title'));
        $this->assertSame(2, $response->json('props.jobs.meta.current_page'));
        $this->assertSame(2, $response->json('scrollProps.jobs.currentPage'));
        $this->assertNull($response->json('scrollProps.jobs.nextPage'));
        $this->assertFalse($response->json('scrollProps.jobs.reset'));

        // The mergeable data path is advertised so the client appends pages.
        $this->assertContains('jobs.data', $response->json('mergeProps'));
    }

    public function test_filter_visit_marks_the_scroll_prop_for_reset_instead_of_merge(): void
    {
        $this->createPublishedJobs(13);

        $version = $this->get('/en/jobs')->assertOk()->viewData('page')['version'];

        $response = $this
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'Jobs/Index',
                'X-Inertia-Partial-Data' => 'hasPreferences,jobs',
                'X-Inertia-Reset' => 'jobs',
            ])
            ->get('/en/jobs');

        $response->assertOk();

        $this->assertSame(1, $response->json('scrollProps.jobs.currentPage'));
        $this->assertTrue($response->json('scrollProps.jobs.reset'));
        $this->assertSame(1, $response->json('props.jobs.meta.current_page'));
        // A reset prop is never advertised for merging — the client replaces it.
        $this->assertNotContains('jobs.data', $response->json('mergeProps') ?? []);
    }

    private function createPublishedJobs(int $count): void
    {
        foreach (range(1, $count) as $index) {
            Job::factory()->create([
                'title' => sprintf('Role %02d', $index),
                'status' => JobStatus::Published->value,
                'published_at' => now()->subMinutes($index),
            ]);
        }
    }
}