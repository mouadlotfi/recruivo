@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ __('admin.dashboard') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ __('admin.dashboard_subtitle') }}</p>
        </div>
        <div class="flex gap-3">
            <a 
                href="{{ localized_route('admin.users') }}" 
                class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
            >
                <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <span class="hidden sm:inline">{{ __('admin.manage_users') }}</span>
                <span class="sm:hidden">{{ __('Users') }}</span>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('admin.total_users') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $totalUsers }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/10">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('admin.total_jobs') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $totalJobs }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-500/10">
                    <svg class="h-6 w-6 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('admin.total_applications') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $totalApplications }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="flex items-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-500/10">
                    <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M3.75 4.5h16.5m-1.5 0l-1.5 1.5m0 0l-1.5-1.5m1.5 1.5V3.75M6.75 3.75h10.5m-10.5 0v1.5m10.5-1.5v1.5" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-400">{{ __('admin.total_companies') }}</p>
                    <p class="text-2xl font-semibold text-stone-900 dark:text-white">{{ $totalCompanies }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-4">{{ __('admin.user_management') }}</h3>
            <div class="space-y-3">
                <a 
                    href="{{ localized_route('admin.users') }}" 
                    class="flex items-center gap-3 rounded-lg bg-amber-50 p-3 text-sm font-medium text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    {{ __('admin.view_all_users') }}
                </a>
            </div>
        </div>

        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-4">{{ __('admin.system_overview') }}</h3>
            <div class="space-y-3 text-sm text-stone-600 dark:text-stone-400">
                <div class="flex items-center justify-between">
                    <span>{{ __('admin.active_users') }}</span>
                    <span class="font-medium text-stone-900 dark:text-white">{{ $totalUsers }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>{{ __('admin.published_jobs') }}</span>
                    <span class="font-medium text-stone-900 dark:text-white">{{ $totalJobs }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>{{ __('admin.total_applications') }}</span>
                    <span class="font-medium text-stone-900 dark:text-white">{{ $totalApplications }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>{{ __('admin.registered_companies') }}</span>
                    <span class="font-medium text-stone-900 dark:text-white">{{ $totalCompanies }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
        <h2 class="text-xl font-semibold text-stone-900 dark:text-white mb-6">{{ __('admin.system_status') }}</h2>
        <div class="grid gap-6 md:grid-cols-3">
            <div class="text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-500/10">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-stone-900 dark:text-white">{{ __('admin.system_online') }}</h3>
                <p class="text-sm text-stone-600 dark:text-stone-400">{{ __('admin.all_services_running') }}</p>
            </div>
            <div class="text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-stone-900 dark:text-white">{{ __('admin.performance') }}</h3>
                <p class="text-sm text-stone-600 dark:text-stone-400">{{ __('admin.optimal_response_times') }}</p>
            </div>
            <div class="text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-500/10">
                    <svg class="h-6 w-6 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h3 class="font-semibold text-stone-900 dark:text-white">{{ __('admin.security') }}</h3>
                <p class="text-sm text-stone-600 dark:text-stone-400">{{ __('admin.all_systems_secure') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
