<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The posts index is ordered by publication date.
 *
 * `Post::scopeLatest()` used to sit in the model looking like it did that, but a
 * real Eloquent builder method wins over a local scope, so the page was ordered
 * by `created_at` - insertion order - while the code read as if it were not.
 */
class PostOrderingTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(Carbon $publishedAt, Carbon $createdAt): Post
    {
        $post = Post::factory()->published()->create(['published_at' => $publishedAt]);
        $post->forceFill(['created_at' => $createdAt])->saveQuietly();

        return $post;
    }

    public function test_posts_index_orders_by_publication_date_not_insertion_order(): void
    {
        // The two orderings must disagree: this post is published most recently
        // but is the oldest row, so ordering by created_at would sort it last.
        $newest = $this->publishedPost(publishedAt: now()->subDay(), createdAt: now()->subDays(10));
        // Published ten days ago, inserted last.
        $oldest = $this->publishedPost(publishedAt: now()->subDays(10), createdAt: now());

        $this->get('/en/posts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Posts/Index', false)
                ->has('posts', 2)
                ->where('posts.0.id', $newest->id)
                ->where('posts.1.id', $oldest->id));
    }

    public function test_drafts_and_future_dated_posts_stay_out_of_the_index(): void
    {
        $visible = $this->publishedPost(publishedAt: now()->subDay(), createdAt: now()->subDay());
        Post::factory()->published()->create(['published_at' => now()->addWeek()]);
        Post::factory()->draft()->create();

        $this->get('/en/posts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('posts', 1)
                ->where('posts.0.id', $visible->id));
    }
}
