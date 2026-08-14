@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ __('posts.title') }}</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-400">{{ __('posts.subtitle') }}</p>
    </div>
    
    @if($posts->count() > 0)
        <div data-infinite-scroll data-infinite-key="posts" data-next-url="{{ $posts->nextPageUrl() }}" data-show-more-label="{{ __('common.show_more') }}" data-loading-label="{{ __('common.loading_more') }}" data-retry-label="{{ __('common.load_more_failed') }}">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3" data-infinite-items>
            @foreach($posts as $post)
                <article class="overflow-hidden rounded-xl border border-stone-200/60 bg-white/80 shadow-sm backdrop-blur transition hover:shadow-lg dark:border-stone-700/60 dark:bg-stone-900/60">
                    <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ asset('images/post-placeholder.svg') }}'" class="h-48 w-full object-cover">
                    
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-stone-900 dark:text-white">
                            <a href="{{ localized_route('posts.show', $post->getLocalizedSlugAttribute()) }}" class="hover:text-amber-600 dark:hover:text-amber-400">
                                {{ $post->title }}
                            </a>
                        </h2>
                        
                        <p class="mt-3 text-stone-600 dark:text-stone-400">
                            {{ Str::limit($post->content, 150) }}
                        </p>
                        
                        <div class="mt-4 flex items-center justify-between text-sm text-stone-500 dark:text-stone-400">
                            <time>{{ $post->published_at->format('M d, Y') }}</time>
                            <a href="{{ localized_route('posts.show', $post->getLocalizedSlugAttribute()) }}" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
                                {{ __('posts.read_more') }} →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        </div>
    @else
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <p class="text-stone-600 dark:text-stone-400">{{ __('posts.empty') }}</p>
        </div>
    @endif
</div>
@endsection

