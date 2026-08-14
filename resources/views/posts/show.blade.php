@extends('layouts.app')

@section('content')
<article class="mx-auto max-w-4xl">
    <div class="mb-8">
        <a href="{{ localized_route('posts.index') }}" class="inline-flex items-center text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            {{ __('posts.back') }}
        </a>
    </div>
    
    <header class="mb-8">
        <h1 class="text-4xl font-bold text-stone-900 dark:text-white">{{ $post->title }}</h1>
        
        <div class="mt-4 flex items-center gap-4 text-sm text-stone-600 dark:text-stone-400">
            <time>{{ $post->published_at->format('M d, Y') }}</time>
            <span>•</span>
            <span>{{ __('posts.by') }} {{ $post->user->name }}</span>
        </div>
    </header>
    
    <div class="mb-8">
        <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" decoding="async" onerror="this.onerror=null;this.src='{{ asset('images/post-placeholder.svg') }}'" class="w-full rounded-xl">
    </div>
    
    <div class="prose prose-stone max-w-none dark:prose-invert">
        {!! nl2br(e($post->content)) !!}
    </div>
    
    <footer class="mt-12 border-t border-stone-200 pt-8 dark:border-stone-700">
        <div class="flex items-center justify-between">
            <a href="{{ localized_route('posts.index') }}" class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800">
                ← {{ __('posts.all') }}
            </a>
            
            <div class="flex items-center gap-2">
                <span class="text-sm text-stone-600 dark:text-stone-400">{{ __('posts.languages') }}:</span>
                <div class="flex gap-2">
                    @foreach(['en', 'fr'] as $locale)
                        <a href="{{ localized_route('posts.show', $post->getTranslation('slug', $locale), $locale) }}" 
                           class="rounded-lg px-3 py-1 text-sm font-medium transition {{ app()->getLocale() === $locale ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400' : 'text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800' }}">
                            {{ strtoupper($locale) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>
</article>
@endsection

