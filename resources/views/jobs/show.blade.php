@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Back Button -->
    <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 text-sm font-medium text-stone-600 transition hover:text-amber-600 dark:text-stone-400 dark:hover:text-amber-400">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        {{ __('jobs.back_to_jobs') }}
    </a>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <x-alert type="success">
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('success') }}
            </div>
        </x-alert>
    @endif

    @if(session('error'))
        <x-alert type="error">
            {{ session('error') }}
        </x-alert>
    @endif

    <div class="grid gap-8 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                <!-- Header -->
                <div class="flex items-start gap-4 pb-6 border-b border-stone-200 dark:border-stone-700">
                    @if($job->company && $job->company->logo_url)
                        <img src="{{ $job->company->logo_url }}" alt="{{ $job->company->name }}" class="h-16 w-16 rounded-lg object-cover" />
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white text-xl font-semibold">
                            {{ $job->company ? substr($job->company->name, 0, 1) : 'J' }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-stone-900 dark:text-white">{{ $job->title }}</h1>
                        @if($job->company)
                            <a href="{{ localized_route('companies.show', $job->company->slug) }}" class="text-lg text-amber-600 hover:text-amber-500 dark:text-amber-400">
                                {{ $job->company->name }}
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Job Details -->
                <div class="mt-6 space-y-4">
                    <div class="flex flex-wrap gap-3">
                        @if($job->location)
                            <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-3 py-1 text-sm text-stone-700 dark:bg-stone-700 dark:text-stone-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                {{ $job->location }}
                            </span>
                        @endif
                        @if($job->remote_type)
                            <a href="{{ localized_route('search', ['search' => '', 'remote_type' => strtolower($job->remote_type)]) }}" 
                               class="rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20">
                                {{ __('recruiter.' . str_replace('-', '', strtolower($job->remote_type))) }}
                            </a>
                        @endif
                        @if($job->category)
                            <a href="{{ localized_route('search', ['search' => $job->category]) }}" 
                               class="rounded-full bg-teal-100 px-3 py-1 text-sm font-medium text-teal-700 transition hover:bg-teal-200 dark:bg-teal-500/10 dark:text-teal-300 dark:hover:bg-teal-500/20">
                                {{ $job->category }}
                            </a>
                        @endif
                    </div>

                    @if($job->salary_min || $job->salary_max)
                        <div class="flex items-center gap-2 text-lg font-semibold text-stone-900 dark:text-white">
                            <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            ${{ number_format($job->salary_min ?? 0) }} - ${{ number_format($job->salary_max ?? 0) }}
                        </div>
                    @endif

                    <div class="prose prose-stone max-w-none dark:prose-invert mt-6">
                        {!! nl2br(e($job->description)) !!}
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Apply Card -->
            <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                @auth
                    @if($canApply)
                        @if($hasApplied)
                            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-600 dark:bg-green-900/20 dark:text-green-400">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('jobs.you_have_applied') }}
                                </div>
                            </div>
                        @else
                            <form method="POST" action="{{ localized_route('jobs.apply', $job->id) }}" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="cover_letter" class="block text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">
                                        {{ __('jobs.cover_letter_optional') }}
                                    </label>
                                    <textarea
                                        id="cover_letter"
                                        name="cover_letter"
                                        rows="4"
                                        placeholder="{{ __('jobs.cover_letter_placeholder') }}"
                                        class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:focus:border-amber-400 dark:focus:ring-amber-800/50"
                                    >{{ old('cover_letter') }}</textarea>
                                    @error('cover_letter')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button
                                    type="submit"
                                    class="w-full inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                >
                                    {{ __('jobs.apply_now_button') }}
                                </button>
                            </form>
                        @endif
                    @else
                        <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                            {{ __('jobs.only_candidates_can_apply') }}
                        </div>
                    @endif
                @else
                    <a
                        href="{{ localized_route('login') }}"
                        class="w-full inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                        {{ __('jobs.log_in_to_apply') }}
                    </a>
                @endauth

                <div class="mt-4 text-xs text-stone-500 dark:text-stone-400">
                    {{ $job->published_at ? __('jobs.posted_time_ago', ['time' => \Carbon\Carbon::parse($job->published_at)->diffForHumans()]) : __('jobs.posted_recently') }}
                </div>
            </div>

            <!-- Company Info -->
            @if($job->company)
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                    <h3 class="text-sm font-semibold text-stone-900 dark:text-white mb-4">{{ __('jobs.about_company', ['company' => $job->company->name]) }}</h3>
                    @if($job->company->tagline)
                        <p class="text-sm text-stone-600 dark:text-stone-400 mb-4">{{ $job->company->tagline }}</p>
                    @endif
                    <a
                        href="{{ localized_route('companies.show', $job->company->slug) }}"
                        class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400"
                    >
                        {{ __('jobs.view_company_profile') }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Similar Jobs -->
    @if(isset($similarJobs) && count($similarJobs) > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-stone-900 dark:text-white mb-6">{{ __('jobs.similar_jobs_title') }}</h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach($similarJobs as $similarJob)
                    <x-job-card :job="$similarJob" />
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

