@extends('layouts.app')

@section('content')
<style>
    .job-description h3 { margin: 1.25rem 0 0.5rem; font-size: 0.9375rem; font-weight: 600; color: #1c1917; }
    .job-description h3:first-child { margin-top: 0; }
    .job-description p { margin: 0.5rem 0; }
    .job-description ul { margin: 0.25rem 0 0.5rem; padding-left: 1.25rem; list-style: disc; }
    .job-description li { margin: 0.25rem 0; }
    .dark .job-description h3 { color: #f5f5f4; }
</style>
<div data-recruiter-job-detail class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <a href="{{ localized_route('recruiter.jobs.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-stone-600 transition hover:text-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:text-amber-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                {{ __('recruiter.back_to_jobs') }}
            </a>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <h1 class="break-words text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ $job->title }}</h1>
                @if($job->isExpired())
                    <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-400">
                        {{ __('recruiter.expired') }}
                    </span>
                @else
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $job->status->value === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' : 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300' }}">
                    {{ __('recruiter.'.$job->status->value) }}
                </span>
                @endif
            </div>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">{{ __('recruiter.posted_time', ['time' => $job->created_at->diffForHumans()]) }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <a href="{{ localized_route('recruiter.jobs.applications', $job) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-amber-300 hover:text-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-amber-500/50 dark:hover:text-amber-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.01-.059a2.25 2.25 0 01-.217-.19 2.25 2.25 0 01-.233-.293 11.962 11.962 0 0110.563-7.335M15 19.128A12.318 12.318 0 008.624 21c-.364 0-.724-.019-1.08-.055m3.456-8.9c.338 0 .67.03.994.086M12 6.75a3.75 3.75 0 110-7.5 3.75 3.75 0 010 7.5zM15 4.5a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0z" /></svg>
                {{ trans_choice('recruiter.applications_count', $job->applications_count, ['count' => $job->applications_count]) }}
            </a>
            <a href="{{ localized_route('recruiter.jobs.edit', $job) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                {{ __('recruiter.edit_job') }}
            </a>
        </div>
    </div>

    @if($job->isExpired())
        <x-alert type="error">
            {{ __('recruiter.expired_job_notice') }}
        </x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
        <section class="min-w-0 rounded-2xl border border-stone-200/70 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-stone-700/70 dark:bg-stone-900/70 sm:p-8">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-white">{{ rtrim(__('recruiter.job_description'), ' *') }}</h2>
            <div class="job-description mt-4 break-words text-sm leading-7 text-stone-700 dark:text-stone-300">
                {!! App\Support\JobDescriptionFormatter::format($job->description) !!}
            </div>
        </section>

        <aside class="rounded-2xl border border-stone-200/70 bg-white/80 p-5 shadow-sm backdrop-blur dark:border-stone-700/70 dark:bg-stone-900/70 sm:p-6">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-white">{{ __('jobs.job_details') }}</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="font-medium text-stone-500 dark:text-stone-400">{{ __('recruiter.location') }}</dt>
                    <dd class="mt-1 break-words text-stone-900 dark:text-white">{{ $job->location }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-stone-500 dark:text-stone-400">{{ __('recruiter.category') }}</dt>
                    <dd class="mt-1 break-words text-stone-900 dark:text-white">{{ $job->category }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-stone-500 dark:text-stone-400">{{ __('recruiter.remote_type') }}</dt>
                    <dd class="mt-1 text-stone-900 dark:text-white">{{ __('recruiter.'.str_replace('-', '', strtolower($job->remote_type))) }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-stone-500 dark:text-stone-400">{{ __('jobs.salary_range') }}</dt>
                    <dd class="mt-1 text-stone-900 dark:text-white">${{ number_format($job->salary_min) }} - ${{ number_format($job->salary_max) }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</div>
@endsection
