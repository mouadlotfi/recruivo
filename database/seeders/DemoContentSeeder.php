<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Services\DemoContentService;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(DemoContentService $content): void
    {
        // Ensure all posts have multi-language translations set
        Post::query()->orderBy('id')->each(function (Post $post, int $index) use ($content) {
            $article = $content->article($index);
            $post->setTranslations('title', $article['title']);
            $post->setTranslations('slug', collect($article['title'])->map(fn (string $title) => str($title)->slug()->toString())->all());
            $post->setTranslations('content', $article['content']);
            $post->save();
        });

        $this->command?->info('Curated demo content and locations synchronized.');
    }
}
