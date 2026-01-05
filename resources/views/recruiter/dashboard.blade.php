@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ __('recruiter.dashboard') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ __('recruiter.dashboard_subtitle') }}</p>
        </div>
        <a 
            href="{{ localized_route('recruiter.jobs.create') }}" 
            class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
        >
            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="hidden sm:inline">{{ __('recruiter.post_new_job') }}</span>
            <span class="sm:hidden">{{ __('Post Job') }}</span>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('recruiter.total_jobs') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $totalJobs }}</p>
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
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('recruiter.active_jobs') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $activeJobs }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('recruiter.total_applications') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $totalApplications }}</p>
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
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('recruiter.pending_applications') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $pendingApplications }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-stone-900 dark:text-white">{{ __('recruiter.recent_applications') }}</h2>
            <a 
                href="{{ localized_route('recruiter.jobs.index') }}" 
                class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400 dark:hover:text-amber-300"
            >
                {{ __('recruiter.view_all_jobs') }}
            </a>
        </div>

        @if($recentApplications->isEmpty())
            <div class="text-center py-8">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                    <svg class="h-8 w-8 text-stone-600 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ __('recruiter.no_applications_yet') }}</h3>
                <p class="text-stone-600 dark:text-stone-400">{{ __('recruiter.applications_will_appear') }}</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($recentApplications as $application)
                    <div class="flex items-center justify-between rounded-lg border border-stone-200 p-4 dark:border-stone-700">
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                {{ substr($application->candidate->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-medium text-stone-900 dark:text-white">{{ $application->candidate->name }}</h3>
                                <p class="text-sm text-stone-600 dark:text-stone-400">{{ $application->job->title }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($application->status->value === 'pending')
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400">
                                    {{ __('recruiter.pending') }}
                                </span>
                            @elseif($application->status->value === 'accepted')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                                    {{ __('recruiter.accepted') }}
                                </span>
                            @elseif($application->status->value === 'rejected')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                    {{ __('recruiter.rejected') }}
                                </span>
                            @endif
                            <span class="text-sm text-stone-500 dark:text-stone-500">
                                {{ $application->created_at->diffForHumans() }}
                            </span>
                            <a 
                                href="{{ localized_route('recruiter.jobs.applications', $application->job) }}" 
                                class="inline-flex items-center justify-center rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                            >
                                {{ __('recruiter.view') }}
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
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-4">{{ __('recruiter.quick_actions') }}</h3>
            <div class="space-y-3">
                <a 
                    href="{{ localized_route('recruiter.jobs.create') }}" 
                    class="flex items-center gap-3 rounded-lg bg-amber-50 p-3 text-sm font-medium text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('recruiter.post_new_job') }}
                </a>
                <a 
                    href="{{ localized_route('recruiter.jobs.index') }}" 
                    class="flex items-center gap-3 rounded-lg bg-stone-50 p-3 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                    {{ __('recruiter.manage_jobs') }}
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-4">{{ __('recruiter.tips_for_success') }}</h3>
            <div class="space-y-3 text-sm text-stone-600 dark:text-stone-400">
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    <p>{{ __('recruiter.tip_1') }}</p>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    <p>{{ __('recruiter.tip_2') }}</p>
                </div>
                <div class="flex items-start gap-2">
                    <svg class="h-4 w-4 mt-0.5 text-green-500" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3" />
                    </svg>
                    <p>{{ __('recruiter.tip_3') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
