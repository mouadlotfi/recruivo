@extends('layouts.app')

@section('content')
<div class="space-y-8">
    @php($isRecruiter = auth()->user()?->hasRole('Recruiter') ?? false)
    <header>
        <h1 class="text-3xl font-bold text-stone-900 dark:text-white">
            {{ $isRecruiter ? __('jobs.recruiter_explore_title') : __('jobs.find_opportunity') }}
        </h1>
        <p class="mt-2 text-stone-600 dark:text-stone-400">
            {{ $isRecruiter ? __('jobs.recruiter_explore_subtitle') : __('jobs.discover_jobs') }}
        </p>
    </header>

    <!-- Results -->
    <div>
        @if(!empty($hasPreferences))
            <h2 class="mb-4 text-xl font-semibold text-stone-900 dark:text-white">{{ __('jobs.recommended_for_you') }}</h2>
        @endif
        @if(count($jobs) > 0)
            <div data-infinite-scroll data-infinite-key="jobs" data-infinite-response="json" data-next-url="{{ $jobs->nextPageUrl() }}" data-show-more-label="{{ __('common.show_more') }}" data-loading-label="{{ __('common.loading_more') }}" data-retry-label="{{ __('common.load_more_failed') }}">
                <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3" data-infinite-items>
                    @include('jobs.partials.cards', ['jobs' => $jobs])
                </div>

            </div>
        @else
            <div class="rounded-xl border border-stone-200/60 bg-white/60 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ __('jobs.no_jobs_found') }}</h3>
                <p class="mt-2 text-stone-600 dark:text-stone-400">
                    {{ __('jobs.check_back_later') }}
                </p>
                <div class="mt-4">
                    <a
                        href="{{ localized_route('home') }}"
                        class="inline-flex items-center justify-center rounded-full border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-600 transition hover:border-amber-400 hover:text-amber-500 dark:border-amber-500/40 dark:text-amber-300 dark:hover:border-amber-400/60"
                    >
                        {{ __('jobs.back_to_home') }}
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection

