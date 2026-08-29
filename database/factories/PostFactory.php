<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
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
        $edition = fake()->unique()->numberBetween(1, 100000);
        $titleEn = "A Practical Guide to Better Hiring — Edition {$edition}";
        $titleFr = "Guide pratique pour un meilleur recrutement — Édition {$edition}";
        $titleAr = "دليل عملي لتوظيف أفضل — الإصدار {$edition}";

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
                'en' => 'A good hiring process starts with clear expectations. Describe the outcome of the role, explain how the team works, and tell candidates what each interview stage will cover. Direct communication helps everyone make better decisions and avoids wasting time.',
                'fr' => 'Un bon processus de recrutement commence par des attentes claires. Décrivez le résultat attendu du poste, expliquez le fonctionnement de l’équipe et précisez le contenu de chaque étape. Une communication directe aide chacun à prendre une meilleure décision.',
                'ar' => 'تبدأ عملية التوظيف الجيدة بتوقعات واضحة. اشرح نتائج الدور وطريقة عمل الفريق ومحتوى كل مرحلة من مراحل المقابلة. يساعد التواصل المباشر الجميع على اتخاذ قرارات أفضل ويحترم وقت المرشحين.',
            ],
            'featured_image' => '/images/post-placeholder.svg',
            'is_published' => fake()->boolean(80),
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the post is published.
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
     */
    public function draft(): Factory
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
