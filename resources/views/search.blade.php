@extends('layouts.app', ['title' => __('common.search')])

@section('content')
@php
    $hasCriteria = $searchQuery || $remoteType || $location;
    $activeFilters = collect([
        $location ? ['key' => 'location', 'label' => __('common.filter_location'), 'value' => $location] : null,
        $remoteType ? ['key' => 'remote_type', 'label' => __('common.filter_work_type'), 'value' => __('common.'.$remoteType)] : null,
    ])->filter()->values();
    $totalCount = $jobsCount + $companiesCount;
@endphp
<div class="space-y-6">

    {{-- Sticky compact search bar --}}
    <div class="search-container relative" data-search-surface="page" data-search-all-label="{{ __('common.search_all_results') }}" data-search-no-results="{{ __('common.no_search_suggestions') }}" data-search-error="{{ __('common.search_error') }}" data-search-recent="{{ __('common.recent_searches') }}" data-search-remove-recent="{{ __('common.remove_recent_search') }}">
        <form method="GET" action="{{ localized_route('search') }}" class="relative">
            <label for="page-search" class="sr-only">{{ __('common.search_placeholder') }}</label>
            <input id="page-search" name="search" value="{{ $searchQuery }}" type="search" autocomplete="off" role="combobox" aria-autocomplete="list" aria-controls="page-search-suggestions" aria-expanded="false" placeholder="{{ __('common.search_placeholder') }}" class="search-input w-full rounded-2xl border border-stone-200 bg-white py-3.5 pl-12 pr-36 text-base text-stone-900 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-200/60 dark:border-stone-700 dark:bg-stone-900 dark:text-white dark:focus:border-amber-500 dark:focus:ring-amber-500/15 sm:py-4 sm:text-lg">
            <svg class="pointer-events-none absolute inset-y-0 left-4 my-auto h-5 w-5 text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
            <div data-search-actions class="absolute inset-y-0 right-2 my-auto flex items-center gap-1">
                <button type="button" data-search-clear class="hidden h-10 w-10 shrink-0 items-center justify-center text-stone-400 transition-colors hover:text-stone-700 focus:outline-none focus-visible:rounded-full focus-visible:ring-2 focus-visible:ring-amber-400 dark:text-stone-500 dark:hover:text-stone-200" aria-label="{{ __('common.clear_search') }}"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl bg-amber-600 px-4 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-300 sm:h-11">{{ __('common.search') }}</button>
            </div>
            <input type="hidden" name="filter" value="{{ $filter }}">
            @if($remoteType)<input type="hidden" name="remote_type" value="{{ $remoteType }}">@endif
            @if($location)<input type="hidden" name="location" value="{{ $location }}">@endif
        </form>
    </div>

    @if($hasCriteria)
        {{-- Result-type tabs with counts --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <nav aria-label="{{ __('common.filter') }}" class="inline-flex w-full rounded-xl border border-stone-200 bg-white p-1 dark:border-stone-700 dark:bg-stone-800 sm:w-auto">
                <a data-search-tab="all" href="{{ localized_route('search', array_merge(request()->except('filter'), ['filter' => 'all'])) }}" @if($filter === 'all') aria-current="page" @endif class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-sm font-medium transition sm:px-4 {{ $filter === 'all' ? 'bg-amber-600 text-white shadow-sm' : 'text-stone-600 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white' }}">
                    {{ __('common.all') }} <span class="tabular-nums opacity-80">({{ $totalCount }})</span>
                </a>
                <a data-search-tab="jobs" href="{{ localized_route('search', array_merge(request()->except('filter'), ['filter' => 'jobs'])) }}" @if($filter === 'jobs') aria-current="page" @endif class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-sm font-medium transition sm:px-4 {{ $filter === 'jobs' ? 'bg-amber-600 text-white shadow-sm' : 'text-stone-600 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white' }}">
                    {{ __('common.jobs') }} <span class="tabular-nums opacity-80">({{ $jobsCount }})</span>
                </a>
                <a data-search-tab="companies" href="{{ localized_route('search', array_merge(request()->except('filter'), ['filter' => 'companies'])) }}" @if($filter === 'companies') aria-current="page" @endif class="flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-sm font-medium transition sm:px-4 {{ $filter === 'companies' ? 'bg-amber-600 text-white shadow-sm' : 'text-stone-600 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white' }}">
                    {{ __('common.companies') }} <span class="tabular-nums opacity-80">({{ $companiesCount }})</span>
                </a>
            </nav>

            <p class="text-sm text-stone-600 dark:text-stone-400" aria-live="polite">
                @if($searchQuery)
                    {{ __('common.results_for_query', ['query' => $searchQuery]) }}
                @else
                    {{ __('common.showing_results', ['count' => $totalCount]) }}
                @endif
            </p>
        </div>

        @if($suggestedCorrection)
            <a href="{{ localized_route('search', array_merge(request()->except('search'), ['search' => $suggestedCorrection])) }}" class="inline-flex rounded-full bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20">
                {{ __('common.did_you_mean', ['query' => $suggestedCorrection]) }}
            </a>
        @endif

        {{-- Active filter chips + collapsible filter panel --}}
        <div x-data="{ showFilters: false }" class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                @foreach($activeFilters as $active)
                    <a data-active-filter-chip href="{{ localized_route('search', array_merge(request()->except($active['key']), [$active['key'] => null])) }}" aria-label="{{ __('common.remove_filter', ['filter' => $active['label']]) }}" class="group inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 py-1.5 pl-3 pr-2 text-xs font-medium text-amber-800 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20">
                        <span><span class="font-semibold">{{ $active['label'] }}:</span> {{ $active['value'] }}</span>
                        <svg class="h-3.5 w-3.5 text-amber-500 transition group-hover:text-amber-700 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </a>
                @endforeach

                <button @click="showFilters = !showFilters" :aria-expanded="showFilters.toString()" aria-controls="search-filter-panel" class="inline-flex items-center gap-2 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
                    {{ __('common.refine_filters') }}
                    <svg class="h-3.5 w-3.5 transition-transform" :class="showFilters && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25L12 15.75 4.5 8.25" /></svg>
                </button>
            </div>

            <div id="search-filter-panel" x-show="showFilters" x-cloak x-transition class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-6">
                <form method="GET" action="{{ localized_route('search') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <input type="hidden" name="search" value="{{ $searchQuery }}">
                    <input type="hidden" name="filter" value="{{ $filter }}">

                    <div class="space-y-2">
                        <label for="location" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                {{ __('common.location') }}
                            </span>
                        </label>
                        <select data-search-location-filter name="location" id="location" class="w-full rounded-lg border border-stone-200/80 bg-white/80 px-4 py-2.5 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500">
                            <option value="">{{ __('jobs.all_locations') }}</option>
                            @foreach($locations as $availableLocation)
                                <option value="{{ $availableLocation }}" {{ $location === $availableLocation ? 'selected' : '' }}>{{ $availableLocation }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="remote_type" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                            <span class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819" /></svg>
                                {{ __('common.work_type') }}
                            </span>
                        </label>
                        <select name="remote_type" id="remote_type" class="w-full rounded-lg border border-stone-200/80 bg-white/80 px-4 py-2.5 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500">
                            <option value="">{{ __('common.all_types') }}</option>
                            <option value="remote" {{ $remoteType === 'remote' ? 'selected' : '' }}>{{ __('common.remote') }}</option>
                            <option value="hybrid" {{ $remoteType === 'hybrid' ? 'selected' : '' }}>{{ __('common.hybrid') }}</option>
                            <option value="onsite" {{ $remoteType === 'onsite' ? 'selected' : '' }}>{{ __('common.onsite') }}</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200">
                            {{ __('common.apply_filters') }}
                        </button>
                        <a href="{{ localized_route('search', ['search' => $searchQuery, 'filter' => $filter]) }}" class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                            {{ __('common.clear_filters') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results --}}
        <div class="space-y-10">
            @if(in_array($filter, ['all', 'jobs']) && $jobs->count() > 0)
                <section aria-labelledby="search-jobs-heading" data-infinite-scroll data-infinite-key="search-jobs" data-next-url="{{ $jobs->nextPageUrl() }}" data-show-more-label="{{ __('common.show_more') }}" data-loading-label="{{ __('common.loading_more') }}" data-retry-label="{{ __('common.load_more_failed') }}">
                    <div class="mb-4 flex items-baseline justify-between gap-3">
                        <h2 id="search-jobs-heading" class="text-xl font-bold text-stone-900 dark:text-white sm:text-2xl">{{ __('jobs.title') }}</h2>
                        <span class="text-sm text-stone-500 dark:text-stone-400">{{ __('common.jobs_count', ['count' => $jobsCount]) }}</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3" data-infinite-items>
                        @foreach($jobs as $job)
                            @include('components.job-card', ['job' => $job])
                        @endforeach
                    </div>
                </section>
            @endif

            @if(in_array($filter, ['all', 'companies']) && $companies->count() > 0)
                <section aria-labelledby="search-companies-heading" data-infinite-scroll data-infinite-key="search-companies" data-next-url="{{ $companies->nextPageUrl() }}" data-show-more-label="{{ __('common.show_more') }}" data-loading-label="{{ __('common.loading_more') }}" data-retry-label="{{ __('common.load_more_failed') }}">
                    <div class="mb-4 flex items-baseline justify-between gap-3">
                        <h2 id="search-companies-heading" class="text-xl font-bold text-stone-900 dark:text-white sm:text-2xl">{{ __('companies.title') }}</h2>
                        <span class="text-sm text-stone-500 dark:text-stone-400">{{ __('common.companies_count', ['count' => $companiesCount]) }}</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3" data-infinite-items>
                        @foreach($companies as $company)
                            @include('components.company-card', ['company' => $company])
                        @endforeach
                    </div>
                </section>
            @endif

            @if(($filter === 'all' && $jobs->count() === 0 && $companies->count() === 0) ||
                ($filter === 'jobs' && $jobs->count() === 0) ||
                ($filter === 'companies' && $companies->count() === 0))
                <div class="rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-12 text-center dark:border-stone-700 dark:bg-stone-900/40">
                    <svg class="mx-auto h-12 w-12 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="mt-4 text-lg font-medium text-stone-900 dark:text-white">
                        @if($filter === 'jobs')
                            {{ __('common.no_jobs_match') }}
                        @elseif($filter === 'companies')
                            {{ __('common.no_companies_match') }}
                        @else
                            {{ __('common.no_results') }}
                        @endif
                    </h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">{{ __('common.broaden_search') }}</p>
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                        <a href="#page-search" class="inline-flex rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-300">{{ __('common.edit_search') }}</a>
                        @if($activeFilters->isNotEmpty())
                            <a href="{{ localized_route('search', ['search' => $searchQuery, 'filter' => $filter]) }}" class="inline-flex rounded-xl border border-stone-200 bg-white px-5 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">{{ __('common.clear_filters') }}</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>

    @else
        {{-- Empty state with popular searches --}}
        <div class="rounded-2xl border border-dashed border-stone-300 bg-white/60 px-6 py-12 text-center dark:border-stone-700 dark:bg-stone-900/40">
            <svg class="mx-auto h-12 w-12 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <h3 class="mt-4 text-lg font-medium text-stone-900 dark:text-white">{{ __('common.start_search') }}</h3>
            <p class="mt-2 text-stone-600 dark:text-stone-400">{{ __('common.search_jobs_and_companies') }}</p>

            @if($popularSearches->isNotEmpty())
                <div class="mt-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ __('common.try_popular_search') }}</p>
                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        @foreach($popularSearches as $popular)
                            <a data-popular-search href="{{ localized_route('search', ['search' => $popular]) }}" class="inline-flex rounded-full border border-stone-200 bg-white px-4 py-2 text-sm font-medium text-stone-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:border-amber-500/40 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">
                                {{ $popular }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
