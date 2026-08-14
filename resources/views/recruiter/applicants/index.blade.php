@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ __('recruiter.applicants_title') }}</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-400">{{ __('recruiter.applicants_description') }}</p>
    </div>

    @if($applicants->isEmpty())
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center dark:border-stone-700/60 dark:bg-stone-900/60">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-white">{{ __('recruiter.no_applicants') }}</h2>
            <p class="mt-2 text-stone-600 dark:text-stone-400">{{ __('recruiter.no_applicants_description') }}</p>
        </div>
    @else
        <div data-infinite-scroll data-infinite-key="recruiter-applicants" data-next-url="{{ $applicants->nextPageUrl() }}" data-show-more-label="{{ __('common.show_more') }}" data-loading-label="{{ __('common.loading_more') }}" data-retry-label="{{ __('common.load_more_failed') }}">
            <div class="grid gap-5 md:grid-cols-2" data-infinite-items>
                @foreach($applicants as $applicant)
                    <article class="group relative rounded-xl border border-stone-200/60 bg-white/80 p-6 transition hover:border-amber-300 hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60 dark:hover:border-amber-700">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                {{ mb_strtoupper(mb_substr($applicant->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="text-lg font-semibold text-stone-900 group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                                    <a href="{{ localized_route('recruiter.applicants.show', $applicant) }}" class="before:absolute before:inset-0 focus:outline-none focus-visible:before:rounded-xl focus-visible:before:ring-2 focus-visible:before:ring-amber-500">
                                        {{ $applicant->name }}
                                    </a>
                                </h2>
                                @if($applicant->candidateProfile?->headline)
                                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">{{ $applicant->candidateProfile->headline }}</p>
                                @endif
                                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-stone-500">{{ __('recruiter.applied_jobs') }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($applicant->applications as $application)
                                        <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs text-stone-700 dark:bg-stone-800 dark:text-stone-300">{{ $application->job->title }}</span>
                                    @endforeach
                                </div>
                                <p class="mt-4 text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('recruiter.view_profile') }} →</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
