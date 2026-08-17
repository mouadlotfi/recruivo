<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostController extends Controller
{
    /**
     * Page-string keys for the public posts index.
     *
     * @var array<int, string>
     */
    private const INDEX_PAGE_LABEL_KEYS = [
        'title', 'subtitle', 'read_more', 'empty',
    ];

    /**
     * Page-string keys for the public post detail page.
     *
     * @var array<int, string>
     */
    private const SHOW_PAGE_LABEL_KEYS = [
        'back', 'by', 'all', 'languages',
    ];

    /**
     * Display a listing of published posts.
     */
    public function index()
    {
        $posts = Post::published()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Posts/Index', [
            'posts' => $posts->getCollection()
                ->map(fn (Post $post) => $this->serializePost($post))
                ->values()
                ->all(),
            'pagination' => [
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'next_page_url' => $posts->nextPageUrl(),
                'prev_page_url' => $posts->previousPageUrl(),
            ],
            'placeholder_image_url' => asset('images/post-placeholder.svg'),
            'labels' => [
                ...collect(self::INDEX_PAGE_LABEL_KEYS)->mapWithKeys(
                    fn (string $key) => [$key => __("posts.$key")]
                )->all(),
                'show_more' => __('common.show_more'),
                'loading_more' => __('common.loading_more'),
                'load_more_failed' => __('common.load_more_failed'),
            ],
        ]);
    }

    /**
     * Display the specified post by its localized slug.
     */
    public function show(string $locale, string $slug)
    {
        // Find post by localized slug using JSON query for better performance.
        $post = Post::published()
            ->with('user')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(slug, '$.{$locale}')) = ?", [$slug])
            ->first();

        if (!$post) {
            abort(404);
        }

        return Inertia::render('Posts/Show', [
            'post' => $this->serializePostDetail($post),
            'placeholder_image_url' => asset('images/post-placeholder.svg'),
            'index_url' => localized_route('posts.index'),
            'language_links' => collect(['en', 'fr'])
                ->map(fn (string $language) => [
                    'locale' => $language,
                    'href' => localized_route(
                        'posts.show',
                        $post->getTranslation('slug', $language),
                        $language
                    ),
                ])
                ->all(),
            'labels' => collect(self::SHOW_PAGE_LABEL_KEYS)->mapWithKeys(
                fn (string $key) => [$key => __("posts.$key")]
            )->all(),
        ]);
    }

    /**
     * Flat serialization for post cards; no Eloquent models leak into props.
     *
     * @return array<string, mixed>
     */
    private function serializePost(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => Str::limit((string) $post->content, 150),
            'featured_image_url' => $post->featured_image_url,
            'published_at_label' => $post->published_at?->format('M d, Y') ?? '',
            'url' => $post->url,
        ];
    }

    /**
     * Flat serialization for the post detail page.
     *
     * @return array<string, mixed>
     */
    private function serializePostDetail(Post $post): array
    {
        return [
            ...$this->serializePost($post),
            'content_html' => nl2br(e((string) $post->content)),
            'author_name' => $post->user->name,
        ];
    }
}

