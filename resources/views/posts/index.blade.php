@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ __('Blog') }}</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-400">{{ __('Latest articles and updates') }}</p>
    </div>
    
    @if($posts->count() > 0)
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($posts as $post)
                <article class="overflow-hidden rounded-xl border border-stone-200 bg-white transition hover:shadow-lg dark:border-stone-700 dark:bg-stone-800">
                    @if($post->featured_image)
                        <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="h-48 w-full object-cover">
                    @endif
                    
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
                                {{ __('Read more') }} →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        
        <div class="mt-8">
            {{ $posts->links() }}
        </div>
    @else
        <div class="rounded-lg border border-stone-200 bg-white p-12 text-center dark:border-stone-700 dark:bg-stone-800">
            <p class="text-stone-600 dark:text-stone-400">{{ __('No posts found') }}</p>
        </div>
    @endif
</div>
@endsection

