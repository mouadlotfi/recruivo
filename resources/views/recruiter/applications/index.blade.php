@extends('layouts.app')

@section('content')
<div class="space-y-7">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ __('recruiter.applications_for', ['job' => $job->title]) }}</h1>
            <p class="mt-2 text-stone-600 dark:text-stone-400">
                {{ $status === 'all'
                    ? trans_choice('recruiter.applications_received', $applications->total(), ['count' => $applications->total()])
                    : trans_choice('recruiter.filtered_applications_received', $applications->total(), ['count' => $applications->total()]) }}
            </p>
        </div>
        <div class="flex shrink-0 flex-col items-stretch gap-2 self-start sm:flex-row sm:items-center">
            <a href="{{ localized_route('recruiter.note-templates.index', ['back' => request()->fullUrl()]) }}" class="inline-flex items-center justify-center self-start whitespace-nowrap rounded-xl bg-stone-100 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                {{ __('recruiter.manage_templates') }}
            </a>
            <a href="{{ localized_route('recruiter.jobs.index') }}" class="inline-flex items-center justify-center self-start whitespace-nowrap rounded-xl bg-stone-100 px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                {{ __('recruiter.back_to_jobs_list') }}
            </a>
        </div>
    </div>

    @if(session('success')) <x-alert type="success">{{ session('success') }}</x-alert> @endif
    @if(session('error')) <x-alert type="error">{{ session('error') }}</x-alert> @endif

    @php
        $tabs = [
            'all' => ['label' => __('recruiter.all_statuses'), 'count' => $statusCounts->sum()],
        ];
        foreach (\App\Enums\ApplicationStatus::cases() as $case) {
            $tabs[$case->value] = ['label' => __('recruiter.'.$case->value), 'count' => $statusCounts->get($case->value, 0)];
        }
        $statusBadgeClasses = [
            'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400',
            'shortlisted' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
            'interview' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400',
            'accepted' => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
            'withdrawn' => 'bg-stone-200 text-stone-700 dark:bg-stone-700/40 dark:text-stone-300',
        ];
    @endphp
    <nav data-application-status-tabs aria-label="{{ __('recruiter.all_applications') }}" class="flex gap-2 overflow-x-auto border-b border-stone-200 pb-3 dark:border-stone-800">
        @foreach($tabs as $tabStatus => $tab)
            <a
                href="{{ localized_route('recruiter.jobs.applications', array_filter(['job' => $job, 'status' => $tabStatus === 'all' ? null : $tabStatus])) }}"
                @if($status === $tabStatus) aria-current="page" @endif
                class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition {{ $status === $tabStatus ? 'bg-amber-600 text-white shadow-sm' : 'bg-stone-100 text-stone-600 hover:bg-stone-200 hover:text-stone-900 dark:bg-stone-900 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-white' }}"
            >
                {{ $tab['label'] }}
                <span class="rounded-full px-2 py-0.5 text-xs {{ $status === $tabStatus ? 'bg-white/20 text-white' : 'bg-white text-stone-500 dark:bg-stone-800 dark:text-stone-400' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>

    @if($applications->isEmpty())
        <div class="rounded-xl border border-stone-200/60 bg-white/80 p-12 text-center backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-stone-100 dark:bg-stone-800">
                <svg class="h-8 w-8 text-stone-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
            </div>
            <h2 class="text-lg font-semibold text-stone-900 dark:text-white">
                {{ $status === 'all' ? __('recruiter.no_applications_received') : __('recruiter.no_applications_with_status', ['status' => strtolower(__('recruiter.'.$status))]) }}
            </h2>
            <p class="mt-2 text-stone-600 dark:text-stone-400">{{ $status === 'all' ? __('recruiter.applications_appear_message') : __('recruiter.no_applications_with_status_message') }}</p>
        </div>
    @else
        <div data-infinite-scroll data-infinite-key="recruiter-applications" data-next-url="{{ $applications->nextPageUrl() }}" data-show-more-label="{{ __('common.show_more') }}" data-loading-label="{{ __('common.loading_more') }}" data-retry-label="{{ __('common.load_more_failed') }}">
            <div class="space-y-4" data-infinite-items>
                @foreach($applications as $application)
                    <article class="rounded-2xl border border-stone-200/70 bg-white/85 p-5 shadow-sm backdrop-blur transition hover:border-amber-300/70 sm:p-6 dark:border-stone-800 dark:bg-stone-900/70 dark:hover:border-amber-700/70">
                        @php($showReviewPanel = !in_array($application->status->value, ['accepted', 'rejected']))
                        <details data-application-card-collapsible class="group" @if($errors->any()) open @endif>
                            <summary class="flex cursor-pointer list-none flex-wrap items-center gap-3 [&::-webkit-details-marker]:hidden">
                                <div class="flex min-w-0 flex-1 basis-full items-center gap-3 sm:basis-auto">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">{{ substr($application->candidate->name, 0, 1) }}</div>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-lg font-semibold leading-snug text-stone-900 dark:text-white">{{ $application->candidate->name }}</h2>
                                        <p class="mt-0.5 break-words text-sm text-stone-600 dark:text-stone-400">{{ $application->candidate->email }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadgeClasses[$application->status->value] ?? 'bg-stone-100 text-stone-700' }}">{{ __('recruiter.'.$application->status->value) }}</span>
                                <svg class="h-4 w-4 shrink-0 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                            </summary>

                            <div class="mt-5 grid gap-6 {{ $showReviewPanel ? 'lg:grid-cols-[minmax(0,1fr)_18rem]' : '' }}">
                            <div class="min-w-0 space-y-5">
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-stone-600 dark:text-stone-400">
                                    <span class="break-words">{{ __('recruiter.applied_time', ['time' => $application->created_at->diffForHumans()]) }}</span>
                                    <span class="break-words"><strong>{{ __('recruiter.phone') }}</strong> {{ $application->candidate->phone ?? __('recruiter.not_provided') }}</span>
                                    @if($application->resume_path || $application->candidate->candidateProfile?->resume_path)
                                        <a href="{{ localized_route('recruiter.applications.resume', $application) }}" target="_blank" rel="noopener" class="font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">{{ __('recruiter.view_resume') }}</a>
                                    @endif
                                </div>

                                @if($application->cover_letter)
                                    <details data-cover-letter-collapsible class="group">
                                        <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between rounded-lg bg-stone-50 px-4 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 [&::-webkit-details-marker]:hidden">
                                            {{ __('recruiter.cover_letter') }}
                                            <svg class="h-4 w-4 text-stone-500 transition group-open:rotate-180 dark:text-stone-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                        </summary>
                                        <div class="mt-2 whitespace-pre-line rounded-xl bg-stone-50 p-4 text-sm leading-6 text-stone-700 dark:bg-stone-800/80 dark:text-stone-300">{{ trim($application->cover_letter) }}</div>
                                    </details>
                                @endif
                                @if($application->notes)
                                    <section><h3 class="mb-2 text-sm font-semibold text-stone-700 dark:text-stone-300">{{ __('recruiter.your_notes') }}</h3><p class="rounded-xl bg-blue-50 p-4 text-sm text-stone-700 dark:bg-blue-500/10 dark:text-stone-300">{{ $application->notes }}</p></section>
                                @endif

                                @if($application->statusEvents->isNotEmpty())
                                    <section class="mt-4" aria-labelledby="status-timeline-{{ $application->id }}">
                                        <h3 id="status-timeline-{{ $application->id }}" class="mb-2 text-sm font-semibold text-stone-700 dark:text-stone-300">
                                            {{ __('recruiter.status_timeline') }}
                                        </h3>
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
                            </div>

                            @if($showReviewPanel)
                            <aside data-application-review-panel class="self-start rounded-xl border border-stone-200 bg-stone-50/80 p-4 dark:border-stone-700 dark:bg-stone-800/60">
                                @if($application->status->value === 'withdrawn')
                                    <p class="text-sm font-semibold text-stone-800 dark:text-stone-200">{{ __('recruiter.withdrawn_by_candidate') }}</p>
                                @else
                                    <form action="{{ localized_route('recruiter.applications.update', $application) }}" method="POST" class="space-y-3" x-data="{ status: @js(old('status', '')), interviewMode: @js(old('interview_mode', 'onsite')), notes: @js(old('notes', '')) }">
                                        @csrf @method('PATCH')
                                        <label for="status-{{ $application->id }}" class="block text-sm font-semibold text-stone-700 dark:text-stone-300">{{ __('recruiter.review_application') }}</label>
                                        <div class="relative">
                                            <select id="status-{{ $application->id }}" name="status" x-model="status" class="min-h-11 w-full appearance-none rounded-lg border border-stone-300 bg-white py-2 pl-3 pr-10 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20">
                                                <option value="" disabled @selected(old('status', '') === '')>{{ __('recruiter.select_status') }}</option>
                                                @foreach(\App\Enums\ApplicationStatus::cases() as $optionStatus)
                                                    @continue($optionStatus === \App\Enums\ApplicationStatus::Withdrawn)
                                                    <option value="{{ $optionStatus->value }}" @selected(old('status', '') === $optionStatus->value)>{{ __('recruiter.'.$optionStatus->value) }}</option>
                                                @endforeach
                                            </select>
                                            <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                        </div>
                                        @if(isset($noteTemplates) && $noteTemplates->isNotEmpty())
                                            <div data-note-template-picker class="space-y-1">
                                                <span class="block text-xs font-semibold text-stone-500 dark:text-stone-400">{{ __('recruiter.note_templates') }}</span>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($noteTemplates as $template)
                                                        <button type="button" @click="notes = {{ \Illuminate\Support\Js::from($template->body) }}"
                                                            class="min-h-9 rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600 transition hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700">
                                                            {{ $template->name }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        <div class="flex justify-end">
                                            <x-expanded-textarea
                                                title="{{ __('recruiter.expand_notes') }}"
                                                model="notes"
                                                placeholder="{{ __('recruiter.add_notes_placeholder') }}"
                                            />
                                        </div>
                                        <textarea name="notes" x-model="notes" x-autosize placeholder="{{ __('recruiter.add_notes_placeholder') }}" class="min-h-24 w-full resize-y rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20" rows="4"></textarea>
                                        @error('notes') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        <fieldset x-show="status === 'interview'" x-cloak class="space-y-3 rounded-lg border border-violet-200 p-3 dark:border-violet-500/30">
                                            <legend class="text-sm font-semibold text-stone-700 dark:text-stone-300">{{ __('recruiter.interview_details') }}</legend>
                                            <div class="space-y-2">
                                                <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.interview_mode') }}</span>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm transition focus-within:ring-2 focus-within:ring-amber-400 dark:focus-within:ring-amber-500/40 {{ old('interview_mode', 'onsite') === 'onsite' ? 'border-amber-400 bg-amber-50 dark:bg-amber-500/10' : 'border-stone-300 dark:border-stone-600' }}">
                                                        <input type="radio" name="interview_mode" value="onsite" x-model="interviewMode" class="sr-only">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                                        {{ __('recruiter.interview_onsite') }}
                                                    </label>
                                                    <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm transition focus-within:ring-2 focus-within:ring-amber-400 dark:focus-within:ring-amber-500/40 {{ old('interview_mode', 'onsite') === 'online' ? 'border-amber-400 bg-amber-50 dark:bg-amber-500/10' : 'border-stone-300 dark:border-stone-600' }}">
                                                        <input type="radio" name="interview_mode" value="online" x-model="interviewMode" class="sr-only">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" /></svg>
                                                        {{ __('recruiter.interview_online') }}
                                                    </label>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="interview_at-{{ $application->id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.interview_at') }}</label>
                                                <input id="interview_at-{{ $application->id }}" type="datetime-local" name="interview_at" value="{{ old('interview_at') }}" class="mt-1 min-h-11 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20">
                                                @error('interview_at') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>
                                            <div x-show="interviewMode === 'onsite'" x-cloak>
                                                <label for="interview_location-{{ $application->id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.interview_location') }}</label>
                                                <input id="interview_location-{{ $application->id }}" type="text" name="interview_location" value="{{ old('interview_location') }}" class="mt-1 min-h-11 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20">
                                                @error('interview_location') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>
                                            <div x-show="interviewMode === 'online'" x-cloak>
                                                <label for="interview_url-{{ $application->id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.interview_url') }}</label>
                                                <input id="interview_url-{{ $application->id }}" type="url" name="interview_url" value="{{ old('interview_url') }}" class="mt-1 min-h-11 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20">
                                                @error('interview_url') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <label for="interview_instructions-{{ $application->id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('recruiter.interview_instructions') }}</label>
                                                    <x-expanded-textarea
                                                        title="{{ __('recruiter.expand_interview_instructions') }}"
                                                        field-id="interview_instructions-{{ $application->id }}"
                                                    />
                                                </div>
                                                <textarea id="interview_instructions-{{ $application->id }}" name="interview_instructions" rows="2" class="mt-1 w-full resize-y rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:focus:ring-amber-500/20">{{ old('interview_instructions') }}</textarea>
                                                @error('interview_instructions') <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>
                                            <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('recruiter.interview_details_hint') }}</p>
                                        </fieldset>
                                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 dark:focus:ring-offset-stone-900">{{ __('common.update') }}</button>
                                    </form>
                                @endif
                            </aside>
                            @endif
                            </div>
                        </details>
                    </article>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection