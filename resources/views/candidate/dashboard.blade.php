@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ __('candidate.dashboard') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ __('candidate.dashboard_subtitle') }}</p>
        </div>
        <a 
            href="{{ localized_route('jobs.index') }}" 
            class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
        >
            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <span class="hidden sm:inline">{{ __('candidate.browse_jobs') }}</span>
            <span class="sm:hidden">{{ __('candidate.browse') }}</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('candidate.total_applications') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $totalApplications ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-500/10">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('candidate.pending') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $pendingApplications ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/10">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('candidate.accepted') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $acceptedApplications ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/10">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('candidate.rejected') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $rejectedApplications ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 sm:p-8">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-white sm:text-xl">{{ __('candidate.recent_applications') }}</h2>
            <a 
                href="{{ localized_route('candidate.applications') }}" 
                class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
            >
                {{ __('candidate.view_all_applications') }}
            </a>
        </div>

        @if(isset($recentApplications) && $recentApplications->isEmpty())
            <div class="text-center py-8">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                    <svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ __('candidate.no_applications_yet') }}</h3>
                <p class="text-stone-600 dark:text-stone-400">{{ __('candidate.start_applying') }}</p>
            </div>
        @elseif(isset($recentApplications))
            <div class="space-y-4">
                @foreach($recentApplications as $application)
                    <div class="flex flex-col gap-3 rounded-lg border border-stone-200 p-3 dark:border-stone-700 sm:flex-row sm:items-center sm:justify-between sm:p-4">
                        <div class="flex items-center gap-3 sm:gap-4">
                            @if($application->job->company)
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                    {{ substr($application->job->company->name, 0, 1) }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate font-medium text-stone-900 dark:text-white">{{ $application->job->title }}</h3>
                                @if($application->job->company)
                                    <p class="truncate text-sm text-stone-600 dark:text-stone-400">{{ $application->job->company->name }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            @if($application->status->value === 'pending')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                                    {{ __('candidate.pending') }}
                                </span>
                            @elseif($application->status->value === 'accepted')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                    {{ __('candidate.accepted') }}
                                </span>
                            @elseif($application->status->value === 'rejected')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                    {{ __('candidate.rejected') }}
                                </span>
                            @endif
                            <span class="text-xs text-stone-500 dark:text-stone-500 sm:text-sm">
                                {{ $application->created_at->diffForHumans() }}
                            </span>
                            <a 
                                href="{{ localized_route('jobs.show', $application->job) }}" 
                                class="inline-flex items-center justify-center rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                            >
                                {{ __('candidate.view') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-4">{{ __('candidate.quick_actions') }}</h3>
            <div class="space-y-3">
                <a 
                    href="{{ localized_route('jobs.index') }}" 
                    class="flex items-center gap-3 rounded-lg bg-amber-50 p-3 text-sm font-medium text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    {{ __('candidate.browse_available_jobs') }}
                </a>
                <a 
                    href="{{ localized_route('candidate.applications') }}" 
                    class="flex items-center gap-3 rounded-lg bg-stone-50 p-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                    {{ __('candidate.view_my_applications') }}
                </a>
                <a 
                    href="{{ localized_route('profile.edit') }}" 
                    class="flex items-center gap-3 rounded-lg bg-stone-50 p-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    {{ __('candidate.update_my_profile') }}
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-4">{{ __('candidate.tips_for_success') }}</h3>
            <div class="space-y-3 text-sm text-stone-600 dark:text-stone-400">
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    <p>{{ __('candidate.tip_1') }}</p>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    <p>{{ __('candidate.tip_2') }}</p>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    <p>{{ __('candidate.tip_3') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

