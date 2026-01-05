@extends('layouts.app', ['title' => __('common.search')])

@section('content')
<div class="space-y-8">
    {{-- Search Header --}}
    <div class="text-center">
        <h1 class="text-3xl font-bold tracking-tight text-stone-900 dark:text-white sm:text-4xl">
            {{ __('common.search') }}
        </h1>
        @if($searchQuery || $remoteType || $location)
            <p class="mt-2 text-lg text-stone-600 dark:text-stone-300">
                {{ __('common.showing_results', ['count' => ($jobs->total() ?? 0) + ($companies->total() ?? 0)]) }}
                @if($searchQuery)
                    {{ __('common.for') }} "<span class="font-semibold">{{ $searchQuery }}</span>"
                @endif
            </p>
        @else
            <p class="mt-2 text-lg text-stone-600 dark:text-stone-300">
                {{ __('common.search_hint') }}
            </p>
        @endif
    </div>

    {{-- Filters Section --}}
    @if($searchQuery || $remoteType || $location)
        <div class="space-y-4" x-data="{ showFilters: false }">
            {{-- Filter Tabs --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="inline-flex rounded-lg border border-stone-200 bg-white p-1 dark:border-stone-700 dark:bg-stone-800">
                    <a
                        href="{{ localized_route('search', array_merge(request()->except('filter'), ['filter' => 'all'])) }}"
                        class="rounded-md px-4 py-2 text-sm font-medium transition {{ $filter === 'all' ? 'bg-amber-600 text-white' : 'text-stone-600 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white' }}"
                    >
                        {{ __('common.all') }}
                    </a>
                    <a
                        href="{{ localized_route('search', array_merge(request()->except('filter'), ['filter' => 'jobs'])) }}"
                        class="rounded-md px-4 py-2 text-sm font-medium transition {{ $filter === 'jobs' ? 'bg-amber-600 text-white' : 'text-stone-600 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white' }}"
                    >
                        {{ __('common.jobs') }}
                    </a>
                    <a
                        href="{{ localized_route('search', array_merge(request()->except('filter'), ['filter' => 'companies'])) }}"
                        class="rounded-md px-4 py-2 text-sm font-medium transition {{ $filter === 'companies' ? 'bg-amber-600 text-white' : 'text-stone-600 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white' }}"
                    >
                        {{ __('common.companies') }}
                    </a>
                </div>

                {{-- Toggle Filters Button (Mobile) --}}
                <button 
                    @click="showFilters = !showFilters"
                    class="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 sm:hidden"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                    {{ __('common.filters') }}
                </button>
            </div>

            {{-- Filter Panel --}}
            <div x-show="showFilters" x-cloak x-transition class="sm:!block rounded-xl border border-stone-200/60 bg-white/80 p-4 sm:p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <form method="GET" action="{{ localized_route('search') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Keep search query --}}
                    <input type="hidden" name="search" value="{{ $searchQuery }}">
                    <input type="hidden" name="filter" value="{{ $filter }}">

                    {{-- Location Filter --}}
                    <div class="space-y-2">
                        <label for="location" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                {{ __('common.location') }}
                            </div>
                        </label>
                        <input
                            type="text"
                            name="location"
                            id="location"
                            value="{{ $location }}"
                            placeholder="{{ __('common.enter_location') }}"
                            class="w-full rounded-lg border border-stone-200/80 bg-white/80 px-4 py-2 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                    </div>

                    {{-- Remote Type Filter --}}
                    <div class="space-y-2">
                        <label for="remote_type" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205l3 1m1.5.5l-1.5-.5M6.75 7.364V3h-3v18m3-13.636l10.5-3.819" />
                                </svg>
                                {{ __('common.work_type') }}
                            </div>
                        </label>
                        <select
                            name="remote_type"
                            id="remote_type"
                            class="w-full rounded-lg border border-stone-200/80 bg-white/80 px-4 py-2 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        >
                            <option value="">{{ __('common.all_types') }}</option>
                            <option value="remote" {{ $remoteType === 'remote' ? 'selected' : '' }}>{{ __('common.remote') }}</option>
                            <option value="hybrid" {{ $remoteType === 'hybrid' ? 'selected' : '' }}>{{ __('common.hybrid') }}</option>
                            <option value="onsite" {{ $remoteType === 'onsite' ? 'selected' : '' }}>{{ __('common.onsite') }}</option>
                        </select>
                    </div>

                    {{-- Apply/Clear Buttons --}}
                    <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                        <button
                            type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            {{ __('common.apply_filters') }}
                        </button>
                        <a
                            href="{{ localized_route('search', ['search' => $searchQuery, 'filter' => $filter]) }}"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg border border-stone-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-200 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ __('common.clear_filters') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Results --}}
        <div class="space-y-8">
            {{-- Jobs Results --}}
            @if(in_array($filter, ['all', 'jobs']) && $jobs->count() > 0)
                <div>
                    <h2 class="mb-4 text-2xl font-bold text-stone-900 dark:text-white">
                        {{ __('jobs.title') }}
                    </h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($jobs as $job)
                            @include('components.job-card', ['job' => $job])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Companies Results --}}
            @if(in_array($filter, ['all', 'companies']) && $companies->count() > 0)
                <div>
                    <h2 class="mb-4 text-2xl font-bold text-stone-900 dark:text-white">
                        {{ __('companies.title') }}
                    </h2>
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($companies as $company)
                            @include('components.company-card', ['company' => $company])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- No Results --}}
            @if(($filter === 'all' && $jobs->count() === 0 && $companies->count() === 0) ||
                ($filter === 'jobs' && $jobs->count() === 0) ||
                ($filter === 'companies' && $companies->count() === 0))
                <div class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-stone-900 dark:text-white">
                        {{ __('common.no_results') }}
                    </h3>
                    <p class="mt-2 text-stone-600 dark:text-stone-400">
                        {{ __('common.try_adjusting_search') }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Unified Pagination --}}
        @if($searchQuery || $remoteType || $location)
            @if($filter === 'jobs' && $jobs->hasPages())
                <div class="mt-8">
                    <x-pagination :paginator="$jobs" />
                </div>
            @elseif($filter === 'companies' && $companies->hasPages())
                <div class="mt-8">
                    <x-pagination :paginator="$companies" />
                </div>
            @elseif($filter === 'all')
                {{-- For 'all', show pagination for whichever has more pages or the first one with pages --}}
                @if($jobs->hasPages() || $companies->hasPages())
                    <div class="mt-8">
                        @if($jobs->hasPages())
                            <x-pagination :paginator="$jobs" />
                        @else
                            <x-pagination :paginator="$companies" />
                        @endif
                    </div>
                @endif
            @endif
        @endif
    @else
        {{-- Empty State --}}
        <div class="py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-stone-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-stone-900 dark:text-white">
                {{ __('common.start_search') }}
            </h3>
            <p class="mt-2 text-stone-600 dark:text-stone-400">
                {{ __('common.search_jobs_and_companies') }}
            </p>
        </div>
    @endif
</div>
@endsection

