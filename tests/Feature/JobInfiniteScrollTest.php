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

    public function test_jobs_page_uses_a_show_more_container_instead_of_pagination_links(): void
    {
        $this->createPublishedJobs(13);

        $response = $this->get('/en/jobs');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Jobs/Index', false)
                ->has('jobs', 12)
                ->where('jobs.0.title', 'Role 01')
                ->where('pagination.total', 13)
                ->where('pagination.last_page', 2)
            );

        $jobsPage = File::get(resource_path('js/Pages/Jobs/Index.vue'));
        $this->assertStringContainsString('data-infinite-items', $jobsPage);
        $this->assertStringContainsString('labels.show_more', $jobsPage);
        $this->assertStringNotContainsString('Pagination Navigation', $jobsPage);
    }

    public function test_infinite_scroll_request_returns_only_the_requested_job_page(): void
    {
        $this->createPublishedJobs(13);

        $response = $this
            ->withHeader('X-Infinite-Scroll', '1')
            ->get('/en/jobs?page=2');

        $response->assertOk();
        $response->assertJsonPath('next_url', null);

        $html = $response->json('html');

        $this->assertStringContainsString('Role 13', $html);
        $this->assertStringNotContainsString('Role 01', $html);
        $this->assertStringNotContainsString('<html', $html);
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
