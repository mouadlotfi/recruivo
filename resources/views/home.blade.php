@extends('layouts.app')

@section('content')
<div class="space-y-12">
    <!-- Hero Section -->
    <section class="space-y-6 text-center">
        @guest
            {{-- Guest Hero --}}
            <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                {{ __('home.hero_title_guest') }}
            </h1>
            <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                {{ __('home.hero_description_guest') }}
            </p>
            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <a href="{{ localized_route('register') }}" 
                   class="inline-flex items-center justify-center rounded-full bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500">
                    {{ __('home.get_started') }}
                </a>
                <a href="{{ localized_route('jobs.index') }}" 
                   class="inline-flex items-center justify-center rounded-full border-2 border-amber-600 px-6 py-3 text-base font-semibold text-amber-600 transition hover:bg-amber-50 dark:border-amber-400 dark:text-amber-400 dark:hover:bg-amber-500/10">
                    {{ __('home.browse_jobs') }}
                </a>
            </div>
        @else
            @if(auth()->user()->hasRole('Candidate'))
                {{-- Candidate Hero --}}
                <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                    @if(session('first_login'))
                        {{ __('home.hero_title_candidate_first', ['name' => auth()->user()->name]) }}
                    @else
                        {{ __('home.hero_title_candidate', ['name' => auth()->user()->name]) }}
                    @endif
                </h1>
                <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                    {{ __('home.hero_description_candidate') }}
                </p>
            @elseif(auth()->user()->hasRole('Recruiter'))
                {{-- Recruiter Hero --}}
                <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                    {{ __('home.hero_title_recruiter', ['name' => auth()->user()->company->name ?? auth()->user()->name]) }}
                </h1>
                <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                    {{ __('home.hero_description_recruiter') }}
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <a href="{{ localized_route('recruiter.jobs.create') }}" 
                       class="inline-flex items-center justify-center rounded-full bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500">
                        <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('home.post_a_job') }}
                    </a>
                    <a href="{{ localized_route('recruiter.dashboard') }}" 
                       class="inline-flex items-center justify-center rounded-full border-2 border-amber-600 px-6 py-3 text-base font-semibold text-amber-600 transition hover:bg-amber-50 dark:border-amber-400 dark:text-amber-400 dark:hover:bg-amber-500/10">
                        {{ __('home.view_dashboard') }}
                    </a>
                </div>
            @else
                {{-- Default Hero (fallback) --}}
                <h1 class="font-display text-4xl font-bold tracking-tight text-stone-900 sm:text-5xl dark:text-white">
                    {{ __('home.hero_title') }}
                </h1>
                <p class="mx-auto max-w-2xl text-lg text-stone-600 dark:text-stone-400">
                    {{ __('home.hero_description') }}
                </p>
            @endif
        @endguest
        
        <!-- Metrics -->
        <div class="mx-auto grid max-w-4xl grid-cols-2 gap-4 pt-8 sm:grid-cols-4">
            <div class="rounded-xl border border-stone-200/60 bg-white/60 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                    {{ $metrics['total_roles'] ?? 0 }}
                </div>
                <div class="text-sm text-stone-600 dark:text-stone-400">{{ __('home.active_roles') }}</div>
            </div>
            <div class="rounded-xl border border-stone-200/60 bg-white/60 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                    {{ $metrics['remote_roles'] ?? 0 }}
                </div>
                <div class="text-sm text-stone-600 dark:text-stone-400">{{ __('home.remote_jobs') }}</div>
            </div>
            <div class="rounded-xl border border-stone-200/60 bg-white/60 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                    {{ $metrics['new_this_week'] ?? 0 }}
                </div>
                <div class="text-sm text-stone-600 dark:text-stone-400">{{ __('home.new_this_week') }}</div>
            </div>
            <div class="rounded-xl border border-stone-200/60 bg-white/60 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">
                    {{ $metrics['active_companies'] ?? 0 }}
                </div>
                <div class="text-sm text-stone-600 dark:text-stone-400">{{ __('home.companies_hiring') }}</div>
            </div>
        </div>
    </section>

    <!-- Jobs Section -->
    <section class="space-y-6">
        @if(count($jobs) > 0)
            <div class="grid gap-6 md:grid-cols-2">
                @foreach($jobs as $job)
                    <x-job-card :job="$job" />
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-stone-200/60 bg-white/60 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
                <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ __('home.no_roles_title') }}</h3>
                <p class="mt-2 text-stone-600 dark:text-stone-400">
                    {{ __('home.no_roles_description') }}
                </p>
                <div class="mt-4">
                    <a
                        href="{{ localized_route('home') }}"
                        class="inline-flex items-center justify-center rounded-full border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-600 transition hover:border-amber-400 hover:text-amber-500 dark:border-amber-500/40 dark:text-amber-300 dark:hover:border-amber-400/60"
                    >
                        {{ __('home.show_all_opportunities') }}
                    </a>
                </div>
            </div>
        @endif

        <!-- Pagination -->
        @if($jobs->hasPages())
            <x-pagination :paginator="$jobs" />
        @endif
    </section>
</div>
@endsection

