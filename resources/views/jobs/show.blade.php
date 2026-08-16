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
                            <a data-job-location-link href="{{ localized_route('search', ['location' => $job->location, 'filter' => 'jobs']) }}" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-3 py-1 text-sm text-stone-700 transition hover:bg-amber-100 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                                {{ $job->location }}
                            </a>
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
                        {!! App\Support\JobDescriptionFormatter::format($job->description) !!}
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Apply Card -->
            <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                @auth
                    @if($isDemoCandidate)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                            {{ __('applications.demo_cannot_apply') }}
                        </div>
                    @elseif($canApply)
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
                            <form method="POST" action="{{ localized_route('jobs.apply', $job->id) }}" enctype="multipart/form-data" class="space-y-4" x-data="{ submitting: false, resumeSource: @js(old('resume_source', auth()->user()->candidateProfile?->resume_path ? 'profile' : 'upload')) }" @submit="submitting = true">
                                @csrf
                                <input type="hidden" name="submission_token" value="{{ $applicationSubmissionToken }}">
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-3">
                                        <label for="cover_letter" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                                            {{ __('jobs.cover_letter') }}
                                        </label>
                                        <x-expanded-textarea
                                            title="{{ __('jobs.write_cover_letter') }}"
                                            field-id="cover_letter"
                                            placeholder="{{ __('jobs.cover_letter_placeholder') }}"
                                        />
                                    </div>
                                    <textarea
                                        id="cover_letter"
                                        name="cover_letter"
                                        rows="4"
                                        x-autosize
                                        required
                                        placeholder="{{ __('jobs.cover_letter_placeholder') }}"
                                        class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 dark:focus:border-amber-400 dark:focus:ring-amber-800/50"
                                    >{{ old('cover_letter') }}</textarea>
                                    @error('cover_letter')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <fieldset class="space-y-3">
                                    <legend class="text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('jobs.resume_source') }}</legend>
                                    @if(auth()->user()->candidateProfile?->resume_path)
                                        <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-stone-200 px-3 py-2 text-sm dark:border-stone-700">
                                            <input type="radio" name="resume_source" value="profile" x-model="resumeSource" required>
                                            <span>{{ __('jobs.use_profile_resume') }}</span>
                                        </label>
                                    @endif
                                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-stone-200 px-3 py-2 text-sm dark:border-stone-700">
                                        <input type="radio" name="resume_source" value="upload" x-model="resumeSource" required>
                                        <span>{{ __('jobs.upload_application_resume') }}</span>
                                    </label>
                                    @error('resume_source')
                                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </fieldset>
                                <div x-show="resumeSource === 'upload'" x-cloak>
                                    <label for="application_resume" class="mb-2 block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('jobs.application_resume') }}</label>
                                    <input
                                        id="application_resume"
                                        name="resume"
                                        type="file"
                                        accept=".pdf,.doc,.docx"
                                        :required="resumeSource === 'upload'"
                                        aria-describedby="application-resume-help"
                                        class="block w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm text-stone-700 file:mr-3 file:rounded-md file:border-0 file:bg-amber-100 file:px-3 file:py-2 file:font-semibold file:text-amber-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:file:bg-amber-500/10 dark:file:text-amber-300"
                                    >
                                    <p id="application-resume-help" class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ __('jobs.application_resume_help') }}</p>
                                    @error('resume')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    :aria-busy="submitting.toString()"
                                    class="w-full inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                >
                                    <span x-show="!submitting">{{ __('jobs.apply_now_button') }}</span>
                                    <span x-show="submitting" x-cloak>{{ __('common.loading') }}</span>
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
                @if($job->isClosingSoon())
                    <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        {{ __('jobs.closing_soon') }} · {{ __('jobs.closes_on', ['date' => $job->closes_at->format('M j, Y')]) }}
                    </div>
                @endif
            </div>

            <!-- Save Job Card -->
            @auth
                @if($isDemoCandidate)
                    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full text-stone-300 dark:text-stone-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                </svg>
                            </span>
                            <p class="text-sm text-stone-500 dark:text-stone-400">{{ __('jobs.demo_cannot_save_jobs') }}</p>
                        </div>
                    </div>
                @elseif($canApply)
                    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-6 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60">
                        @if($job->is_saved ?? false)
                            <form method="POST" action="{{ localized_route('candidate.saved-jobs.destroy', $job) }}">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-3 text-base font-semibold text-amber-700 transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
                                >
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0111.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 01-1.085.67L12 18.089l-7.165 3.583A.75.75 0 013.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('jobs.remove_saved_job') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ localized_route('candidate.saved-jobs.store', $job) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-stone-100 px-6 py-3 text-base font-semibold text-stone-700 transition hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                                    </svg>
                                    {{ __('jobs.save_job') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            @endauth

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
