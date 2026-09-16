<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Posts resolve by localized slug. The lookup used a raw MySQL-only
 * JSON_UNQUOTE(JSON_EXTRACT(...)), then Laravel's JSON path syntax - which must
 * be written `slug->en`, not `slug->>en`: the grammar splits on `->` and would
 * otherwise look up a key literally named `>en`, returning 404 on every driver.
 */
class PostShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_resolves_by_english_slug(): void
    {
        $post = Post::factory()->published()->create([
            'user_id' => User::factory(),
            'slug' => ['en' => 'trustworthy-job-descriptions', 'fr' => 'offres-fiables'],
        ]);

        $this->get('/en/posts/trustworthy-job-descriptions')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Posts/Show', false)
                ->where('post.id', $post->id));
    }

    public function test_post_resolves_by_french_slug(): void
    {
        $post = Post::factory()->published()->create([
            'user_id' => User::factory(),
            'slug' => ['en' => 'trustworthy-job-descriptions', 'fr' => 'offres-fiables'],
        ]);

        $this->get('/fr/posts/offres-fiables')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Posts/Show', false)
                ->where('post.id', $post->id));
    }

    public function test_unpublished_post_slug_is_not_found(): void
    {
        Post::factory()->draft()->create([
            'user_id' => User::factory(),
            'slug' => ['en' => 'unpublished-draft'],
        ]);

        $this->get('/en/posts/unpublished-draft')->assertNotFound();
    }
}
