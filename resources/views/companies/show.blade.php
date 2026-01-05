@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Back Button -->
    <a href="{{ localized_route('companies.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-stone-600 transition hover:text-amber-600 dark:text-stone-400 dark:hover:text-amber-400">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        {{ __('companies.back_to_companies') }}
    </a>

    <!-- Company Header -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
        <div class="flex items-start gap-6">
            @if($company->logo_url)
                <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="h-24 w-24 rounded-lg object-cover" />
            @else
                <div class="flex h-24 w-24 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white text-3xl font-semibold">
                    {{ substr($company->name, 0, 1) }}
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ $company->name }}</h1>
                @if($company->tagline)
                    <p class="mt-2 text-lg text-stone-600 dark:text-stone-400">{{ $company->tagline }}</p>
                @endif
                <div class="mt-4 flex flex-wrap gap-4">
                    @if($company->location)
                        <span class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            {{ $company->location }}
                        </span>
                    @endif
                    @if($company->size)
                        <span class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                            {{ $company->size }}
                        </span>
                    @endif
                    @if($company->founded_year)
                        <span class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            {{ __('companies.founded_year', ['year' => $company->founded_year]) }}
                        </span>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if($company->website_url)
                        <a
                            href="{{ $company->website_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-full border border-stone-200 px-4 py-2 text-sm font-medium text-stone-700 transition hover:border-amber-300 hover:text-amber-600 dark:border-stone-700 dark:text-stone-300 dark:hover:border-amber-400 dark:hover:text-amber-300"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                            </svg>
                            {{ __('companies.website') }}
                        </a>
                    @endif
                    @if($company->linkedin_url)
                        <a
                            href="{{ $company->linkedin_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-full border border-stone-200 px-4 py-2 text-sm font-medium text-stone-700 transition hover:border-amber-300 hover:text-amber-600 dark:border-stone-700 dark:text-stone-300 dark:hover:border-amber-400 dark:hover:text-amber-300"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                            {{ __('companies.linkedin') }}
                        </a>
                    @endif
                    @if($company->email)
                        <a
                            href="mailto:{{ $company->email }}"
                            class="inline-flex items-center gap-2 rounded-full border border-stone-200 px-4 py-2 text-sm font-medium text-stone-700 transition hover:border-amber-300 hover:text-amber-600 dark:border-stone-700 dark:text-stone-300 dark:hover:border-amber-400 dark:hover:text-amber-300"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                            {{ __('companies.contact') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if($company->mission || $company->culture)
            <div class="mt-8 space-y-6 border-t border-stone-200 pt-8 dark:border-stone-700">
                @if($company->mission)
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ __('companies.our_mission') }}</h2>
                        <p class="text-stone-600 dark:text-stone-400">{{ $company->mission }}</p>
                    </div>
                @endif
                @if($company->culture)
                    <div>
                        <h2 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ __('companies.company_culture') }}</h2>
                        <p class="text-stone-600 dark:text-stone-400">{{ $company->culture }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Open Positions -->
    @if(isset($company->jobs) && count($company->jobs) > 0)
        <div>
            <h2 class="text-2xl font-bold text-stone-900 dark:text-white mb-6">
                {{ __('companies.open_positions_count', ['count' => count($company->jobs)]) }}
            </h2>
            <div class="grid gap-6 md:grid-cols-2">
                @foreach($company->jobs as $job)
                    @php
                        $userHasApplied = auth()->check() && auth()->user()->hasRole('Candidate') && auth()->user()->applications()->where('job_id', $job->id)->exists();
                    @endphp
                    <a href="{{ localized_route('jobs.show', ['job' => $job->id]) }}" class="group relative block rounded-xl border border-stone-200/60 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60">
                        {{-- Applied Badge --}}
                        @if($userHasApplied)
                            <div class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                {{ __('common.applied') }}
                            </div>
                        @endif

                        <div class="{{ $userHasApplied ? 'pr-20' : '' }}">
                            <h3 class="font-semibold text-stone-900 group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400 transition">
                                {{ $job->title }}
                            </h3>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-stone-500 dark:text-stone-400">
                                @if($job->location)
                                    <span class="flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                        </svg>
                                        {{ $job->location }}
                                    </span>
                                @endif
                                @if($job->remote_type)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        {{ ucfirst($job->remote_type) }}
                                    </span>
                                @endif
                                @if($job->category)
                                    <span class="rounded-full bg-stone-100 px-2 py-0.5 font-medium text-stone-700 dark:bg-stone-700 dark:text-stone-300">
                                        {{ $job->category }}
                                    </span>
                                @endif
                            </div>
                            @if($job->salary_min || $job->salary_max)
                                <p class="mt-2 text-sm font-medium text-stone-700 dark:text-stone-300">
                                    ${{ number_format($job->salary_min ?? 0) }} - ${{ number_format($job->salary_max ?? 0) }}
                                </p>
                            @endif
                            @if(isset($job->applications_count))
                                <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">
                                    {{ $job->applications_count }} {{ $job->applications_count == 1 ? __('companies.application') : __('companies.applications') }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-xl border border-stone-200/60 bg-white/60 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/40">
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white">{{ __('companies.no_open_positions') }}</h3>
            <p class="mt-2 text-stone-600 dark:text-stone-400">
                {{ __('companies.check_back_later', ['company' => $company->name]) }}
            </p>
        </div>
    @endif
</div>
@endsection

