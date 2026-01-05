<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleEn = fake()->sentence();
        $titleFr = fake('fr_FR')->sentence();
        $titleAr = 'مقالة عن ' . fake()->word();

        return [
            'user_id' => User::factory(),
            'title' => [
                'en' => $titleEn,
                'fr' => $titleFr,
                'ar' => $titleAr,
            ],
            'slug' => [
                'en' => Str::slug($titleEn),
                'fr' => Str::slug($titleFr),
                'ar' => Str::slug($titleAr),
            ],
            'content' => [
                'en' => fake()->paragraphs(5, true),
                'fr' => fake('fr_FR')->paragraphs(5, true),
                'ar' => 'محتوى المقالة باللغة العربية. ' . fake()->paragraphs(3, true),
            ],
            'featured_image' => fake()->imageUrl(800, 600, 'blog', true),
            'is_published' => fake()->boolean(80),
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the post is published.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function published(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function draft(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}

