<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobInfiniteScrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_page_uses_a_show_more_container_instead_of_pagination_links(): void
    {
        $this->createPublishedJobs(13);

        $response = $this->get('/en/jobs');

        $response->assertOk();
        $response->assertSee('data-infinite-scroll', false);
        $response->assertSee('data-show-more-label', false);
        $response->assertSee('data-next-url', false);
        $response->assertSee('Role 01');
        $response->assertDontSee('Role 13');
        $response->assertDontSee('Pagination Navigation');
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
