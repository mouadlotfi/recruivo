@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div>
        <a href="{{ localized_route('recruiter.applicants.index') }}" class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">← {{ __('recruiter.back_to_applicants') }}</a>
        <div class="mt-5 flex items-center gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-amber-100 text-2xl font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                {{ mb_strtoupper(mb_substr($applicant->name, 0, 1)) }}
            </div>
            <div>
                <p class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('recruiter.applicant_profile') }}</p>
                <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ $applicant->name }}</h1>
                @if($applicant->candidateProfile?->headline)
                    <p class="mt-1 text-stone-600 dark:text-stone-400">{{ $applicant->candidateProfile->headline }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <aside class="space-y-6">
            <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
                <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('recruiter.contact_information') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-stone-500">{{ __('profile.email_address') }}</dt><dd class="mt-1 break-all text-stone-800 dark:text-stone-200">{{ $applicant->email }}</dd></div>
                    <div><dt class="text-stone-500">{{ __('profile.phone_number') }}</dt><dd class="mt-1 text-stone-800 dark:text-stone-200">{{ $applicant->phone ?: __('recruiter.not_provided') }}</dd></div>
                </dl>
                @if($applications->first()?->resume_path || $applicant->candidateProfile?->resume_path)
                    <a href="{{ localized_route('recruiter.applications.resume', $applications->first()) }}" target="_blank" rel="noopener" class="relative z-10 mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-500">{{ __('recruiter.view_resume') }}</a>
                @endif
            </section>

            @include('recruiter.applicants.partials.structured-profile')
        </aside>

        <section class="lg:col-span-2">
            <h2 class="text-xl font-semibold text-stone-900 dark:text-white">{{ __('recruiter.application_history') }}</h2>
            <div class="mt-4 space-y-4">
                @foreach($applications as $application)
                    <article class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-semibold text-stone-900 dark:text-white">{{ $application->job->title }}</h3>
                                <p class="mt-1 text-sm text-stone-500">{{ __('recruiter.applied_time', ['time' => $application->created_at->diffForHumans()]) }}</p>
                            </div>
                            <span class="w-fit rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700 dark:bg-stone-800 dark:text-stone-300">{{ __('recruiter.'.$application->status->value) }}</span>
                        </div>
                        @if($application->cover_letter)
                            <details data-cover-letter-collapsible class="group mt-4">
                                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between rounded-lg bg-stone-50 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 [&::-webkit-details-marker]:hidden">
                                    {{ __('recruiter.cover_letter') }}
                                    <svg class="h-4 w-4 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                </summary>
                                <div class="mt-2 whitespace-pre-line rounded-lg bg-stone-50 p-4 text-sm text-stone-700 dark:bg-stone-800 dark:text-stone-300">{{ $application->cover_letter }}</div>
                            </details>
                        @endif
                        <a href="{{ localized_route('recruiter.jobs.applications', $application->job) }}" class="mt-4 inline-flex text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">{{ __('recruiter.review_application') }} →</a>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
