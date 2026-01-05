@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-stone-900 dark:text-white">
            {{ __('jobs.find_opportunity') }}
        </h1>
        <p class="mt-2 text-stone-600 dark:text-stone-400">
            {{ __('jobs.discover_jobs') }}
        </p>
    </div>

    <!-- Results -->
    <div>
        @if(count($jobs) > 0)
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($jobs as $job)
                    <x-job-card :job="$job" />
                @endforeach
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

        <!-- Pagination -->
        @if($jobs->hasPages())
            <div class="mt-8">
                <x-pagination :paginator="$jobs" />
            </div>
        @endif
    </div>
</div>
@endsection

