@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-stone-900 dark:text-white sm:text-3xl">{{ __('applications.my_applications') }}</h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400 sm:text-base">{{ __('applications.subtitle') }}</p>
        </div>
        <a
            href="{{ localized_route('jobs.index') }}"
            class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:px-6 sm:py-3"
        >
            <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <span>{{ __('applications.browse_jobs') }}</span>
        </a>
    </div>

    @if(session('success'))
        <x-alert type="success">
            {{ session('success') }}
        </x-alert>
    @endif

    @if(session('error'))
        <x-alert type="error">
            {{ session('error') }}
        </x-alert>
    @endif

    <nav data-candidate-application-status-tabs aria-label="{{ __('applications.my_applications') }}" class="flex w-full gap-1 overflow-x-auto rounded-xl border border-stone-200 bg-white p-1 dark:border-stone-700 dark:bg-stone-800 sm:w-fit">
        @php
            $cTabs = ['all' => ['label' => __('applications.all'), 'count' => $statusCounts['all'] ?? 0]];
            foreach (\App\Enums\ApplicationStatus::cases() as $cCase) {
                $cTabs[$cCase->value] = ['label' => __('applications.'.$cCase->value), 'count' => $statusCounts[$cCase->value] ?? 0];
            }
        @endphp
        @foreach($cTabs as $tabStatus => $tab)
            <a
                href="{{ localized_route('candidate.applications', $tabStatus === 'all' ? [] : ['status' => $tabStatus]) }}"
                @if($status === $tabStatus) aria-current="page" @endif
                class="min-h-11 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-semibold transition {{ $status === $tabStatus ? 'bg-amber-600 text-white shadow-sm' : 'text-stone-600 hover:bg-stone-50 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white' }}"
            >
                {{ $tab['label'] }} ({{ $tab['count'] }})
            </a>
        @endforeach
    </nav>

    @if($applications->isEmpty())
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-stone-900 dark:text-white mb-2">{{ $status === 'all' ? __('applications.no_applications_yet') : __('applications.no_applications_for_status') }}</h3>
            <p class="text-stone-600 dark:text-stone-400 mb-6">{{ __('applications.start_applying') }}</p>
            <a
                href="{{ localized_route('jobs.index') }}"
                class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
            >
                {{ __('applications.browse_available_jobs') }}
            </a>
        </div>
    @else
        <div data-infinite-scroll data-infinite-key="candidate-applications" data-next-url="{{ $applications->nextPageUrl() }}" data-show-more-label="{{ __('common.show_more') }}" data-loading-label="{{ __('common.loading_more') }}" data-retry-label="{{ __('common.load_more_failed') }}">
        <div class="space-y-4" data-infinite-items>
            @foreach($applications as $application)
                <div class="rounded-xl border border-stone-200/60 bg-white/80 p-4 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 transition hover:border-amber-200 dark:hover:border-amber-800 sm:p-6">
                    <details data-application-card-collapsible class="group">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center gap-4 [&::-webkit-details-marker]:hidden">
                            <div class="flex min-w-0 flex-1 basis-full items-center gap-4 sm:basis-auto">
                                @if($application->job->company && $application->job->company->logo_url)
                                    <img
                                        src="{{ $application->job->company->logo_url }}"
                                        alt="{{ $application->job->company->name }}"
                                        class="h-12 w-12 shrink-0 rounded-lg object-cover"
                                    >
                                @else
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                        {{ $application->job->company ? substr($application->job->company->name, 0, 1) : 'J' }}
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-xl font-semibold leading-snug text-stone-900 dark:text-white">
                                        {{ $application->job->title }}
                                    </h3>
                                    @if($application->job->company)
                                        <p class="mt-0.5 text-sm text-stone-600 dark:text-stone-400">
                                            {{ $application->job->company->name }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @php
                                $statusBadgeClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400',
                                    'shortlisted' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
                                    'interview' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400',
                                    'accepted' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
                                    'rejected' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
                                    'withdrawn' => 'bg-stone-200 text-stone-700 dark:bg-stone-700/40 dark:text-stone-300',
                                ];
                                $statusLabelKey = $application->status->value === 'pending' ? 'pending_review' : $application->status->value;
                            @endphp
                            <span class="inline-flex shrink-0 items-center rounded-full {{ $statusBadgeClasses[$application->status->value] ?? 'bg-stone-100 text-stone-700' }} px-3 py-1 text-xs font-medium">
                                <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                {{ __('applications.'.$statusLabelKey) }}
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </summary>

                        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1">
                            <div class="mb-3 flex flex-wrap items-center gap-4 text-sm text-stone-600 dark:text-stone-400">
                                        @if($application->job->location)
                                            <div class="flex items-center gap-1">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                                </svg>
                                                {{ $application->job->location }}
                                            </div>
                                        @endif
                                        @if($application->job->remote_type)
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                                {{ ucfirst($application->job->remote_type) }}
                                            </span>
                                        @endif
                                        @if($application->job->category)
                                            <span class="rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-500/10 dark:text-teal-300">
                                                {{ $application->job->category }}
                                            </span>
                                        @endif
                            </div>

                            <div class="text-sm text-stone-500 dark:text-stone-500 mb-3">
                                {{ __('applications.applied', ['time' => $application->created_at->diffForHumans()]) }}
                            </div>

                            @if($application->cover_letter)
                                <details data-cover-letter-collapsible class="group mb-3">
                                    <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between rounded-lg bg-stone-50 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 [&::-webkit-details-marker]:hidden">
                                        {{ __('applications.your_cover_letter') }}
                                        <svg class="h-4 w-4 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                    </summary>
                                    <div class="mt-2 whitespace-pre-line rounded-lg bg-stone-50 p-4 text-sm leading-6 text-stone-700 dark:bg-stone-800 dark:text-stone-300">{{ trim($application->cover_letter) }}</div>
                                </details>
                            @endif

                            @if($application->notes)
                                <div class="mb-3">
                                    <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">{{ __('applications.recruiter_notes') }}</h4>
                                    <div class="rounded-lg bg-blue-50 p-4 text-sm text-stone-700 dark:bg-blue-500/10 dark:text-stone-300">
                                        {{ $application->notes }}
                                    </div>
                                </div>
                            @endif

                            @if($application->statusEvents->isNotEmpty())
                                <section class="mt-4 mb-3" aria-labelledby="status-timeline-{{ $application->id }}">
                                    <h4 id="status-timeline-{{ $application->id }}" class="text-sm font-semibold text-stone-700 dark:text-stone-300 mb-2">
                                        {{ __('applications.status_timeline') }}
                                    </h4>
                                    <ol data-application-status-timeline class="space-y-3">
                                        @foreach($application->statusEvents as $event)
                                            <li class="relative pl-6">
                                                <span aria-hidden="true" class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                                <p class="text-sm font-semibold text-stone-800 dark:text-stone-200">
                                                    {{ __('applications.status_'.$event->to_status) }}
                                                </p>
                                                <time datetime="{{ $event->created_at->toIso8601String() }}" class="text-xs text-stone-500 dark:text-stone-400">
                                                    {{ $event->created_at->translatedFormat('M j, Y H:i') }}
                                                </time>
                                            </li>
                                        @endforeach
                                    </ol>
                                </section>
                            @endif

                            @if($application->status->value === 'interview' && $application->interview_at)
                                <div class="mb-3 rounded-lg border border-violet-200 bg-violet-50 p-4 text-sm text-stone-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-stone-300">
                                    <h4 class="text-sm font-medium text-stone-700 dark:text-stone-300 mb-2">{{ __('applications.interview_scheduled') }}</h4>
                                    <p><strong>{{ __('applications.interview_when') }}</strong> {{ $application->interview_at->translatedFormat('l, F j, Y \a\t g:i A') }}</p>
                                    @if($application->interview_mode === 'online')
                                        <p><strong>{{ __('applications.interview_online') }}</strong></p>
                                    @else
                                        <p><strong>{{ __('applications.interview_onsite') }}</strong></p>
                                    @endif
                                    @if($application->interview_location) <p><strong>{{ __('applications.interview_where') }}</strong> {{ $application->interview_location }}</p> @endif
                                    @if($application->interview_url)
                                        <p><strong>{{ __('applications.interview_link') }}</strong> <a href="{{ $application->interview_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-amber-700 hover:underline dark:text-amber-400">{{ $application->interview_url }}</a></p>
                                    @endif
                                    @if($application->interview_instructions) <p class="mt-2 whitespace-pre-line">{{ $application->interview_instructions }}</p> @endif
                                </div>
                            @endif

                            @if($application->job->salary_min && $application->job->salary_max)
                                <div class="text-sm">
                                    <span class="font-medium text-stone-700 dark:text-stone-300">{{ __('applications.salary') }}</span>
                                    <span class="text-stone-600 dark:text-stone-400">
                                        ${{ number_format($application->job->salary_min) }} - ${{ number_format($application->job->salary_max) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-col gap-3 sm:ml-6 sm:items-start">
                            <a
                                href="{{ localized_route('jobs.show', $application->job) }}"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-stone-100 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 sm:w-auto"
                            >
                                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ __('applications.view_job') }}
                            </a>
                            @if(in_array($application->status->value, ['pending', 'shortlisted', 'interview']))
                                <form method="POST" action="{{ localized_route('candidate.applications.withdraw', $application) }}" onsubmit="return confirm(@js(__('applications.withdraw_confirm')));">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:border-red-500/30 dark:text-red-400 dark:hover:bg-red-500/10 sm:w-auto min-h-11">
                                        {{ __('applications.withdraw_application') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    </details>
                </div>
            @endforeach
        </div>
        </div>
    @endif
</div>
@endsection
