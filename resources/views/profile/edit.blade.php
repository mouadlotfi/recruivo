@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-3xl font-bold text-stone-900 dark:text-white">
            @if($user->hasRole('Recruiter'))
                {{ __('profile.company_profile') }}
            @else
                {{ __('profile.profile_settings') }}
            @endif
        </h1>
        @if($user->hasRole('Candidate'))
            <a href="{{ localized_route('candidate.profile-preview') }}" target="_blank" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-amber-500/40 dark:text-amber-300 dark:hover:bg-amber-500/10">
                {{ __('profile.preview_as_recruiter') }}
            </a>
        @endif
    </div>

    @if(session('success'))
        <x-alert type="success" class="mb-6">
            {{ session('success') }}
        </x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" class="mb-6">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </x-alert>
    @endif

    @if($user->is_demo)
        <div data-demo-read-only-settings class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/30">
            <h2 class="font-semibold text-amber-900 dark:text-amber-200">{{ __('common.demo_account_profile_read_only') }}</h2>
            <p class="mt-2 text-sm text-amber-800 dark:text-amber-300">{{ __('common.demo_account_profile_read_only_description') }}</p>
        </div>
    @endif

    <!-- Profile Information -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 mb-6">
        <h2 class="text-xl font-semibold text-stone-900 dark:text-white mb-6">{{ __('profile.profile_information') }}</h2>
        
        <form method="POST" action="{{ localized_route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <fieldset data-profile-settings-fields @disabled($user->is_demo) class="space-y-6 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-60">

            @if($user->hasRole('Candidate'))
                <!-- Profile Completion Summary (candidates only, while incomplete) -->
                @if($profileCompletion && $profileCompletion['percentage'] < 100)
                <div class="mb-6 rounded-xl border border-amber-200/60 bg-amber-50/60 p-4 dark:border-amber-500/20 dark:bg-amber-500/5">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-stone-900 dark:text-white">{{ __('profile.profile_completion') }}</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-400">{{ __('profile.profile_completion_help') }}</p>
                        </div>
                        <span class="text-lg font-bold text-stone-900 dark:text-white">{{ $profileCompletion['percentage'] }}%</span>
                    </div>
                    <div class="mt-2" role="progressbar" aria-valuenow="{{ $profileCompletion['percentage'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('profile.profile_completion') }}">
                        <div class="h-2 overflow-hidden rounded-full bg-stone-200 dark:bg-stone-700">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-600 transition-all duration-500" style="width: {{ $profileCompletion['percentage'] }}%;"></div>
                        </div>
                    </div>
                    @if($profileCompletion['missing'] !== [])
                        <p class="mt-2 text-xs text-stone-600 dark:text-stone-400">{{ trans_choice('profile.profile_completion_steps_left', count($profileCompletion['missing']), ['count' => count($profileCompletion['missing'])]) }}</p>
                    @else
                        <p class="mt-2 text-xs font-medium text-green-700 dark:text-green-400">{{ __('profile.profile_complete') }}</p>
                    @endif
                </div>
                @endif

                <div class="space-y-2">
                    <label for="name" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.full_name') }}
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>


                <div class="space-y-2">
                    <label for="phone" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.phone_number') }}
                    </label>
                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        value="{{ old('phone', $user->phone) }}"
                        placeholder="{{ __('profile.phone_placeholder') }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>

                <div class="space-y-2">
                    <label for="profile_summary" class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ __('profile.about') }}</label>
                    <textarea id="profile_summary" name="profile_summary" rows="5" maxlength="1000" placeholder="{{ __('profile.about_placeholder') }}" class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500">{{ old('profile_summary', $user->profile_summary) }}</textarea>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('profile.about_help') }}</p>
                </div>

                <div class="space-y-2">
                    <label for="headline" class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ __('profile.professional_headline') }}</label>
                    <input id="headline" name="headline" type="text" value="{{ old('headline', $user->candidateProfile?->headline) }}" placeholder="{{ __('profile.headline_placeholder') }}" class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100" />
                </div>

                <div class="space-y-2">
                    <label for="skills" class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ __('profile.skills') }}</label>
                    <textarea id="skills" name="skills" rows="3" placeholder="{{ __('profile.skills_placeholder') }}" class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100">{{ old('skills', $user->candidateProfile?->skills) }}</textarea>
                </div>

                @include('profile.partials.structured-profile-builder')

                <div class="space-y-2">
                    <label class="text-sm font-medium text-stone-700 dark:text-stone-200">{{ __('profile.job_interests') }}</label>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('profile.job_interests_help') }}</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach(\App\Enums\ItCategory::cases() as $category)
                            <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-stone-200 bg-white/60 px-3 py-2 text-sm text-stone-700 transition hover:border-amber-300 dark:border-stone-700 dark:bg-stone-900/60 dark:text-stone-200">
                                <input type="checkbox" name="preferred_categories[]" value="{{ $category->value }}"
                                    @checked(in_array($category->value, old('preferred_categories', $user->candidateProfile?->preferred_categories ?? [])))
                                    class="h-5 w-5 rounded border-stone-300 text-amber-600 focus:ring-amber-500 dark:border-stone-600">
                                {{ $category->value }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="resume" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.resume') }}
                    </label>
                    @if($user->candidateProfile && $user->candidateProfile->resume_path)
                        <div class="mb-3 flex flex-col gap-4 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 dark:border-emerald-500/25 dark:bg-emerald-500/10 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </span>
                                <div class="min-w-0 pt-0.5">
                                    <p class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">{{ __('profile.resume_uploaded') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-emerald-700 dark:text-emerald-300/80">{{ __('profile.resume_uploaded_help') }}</p>
                                </div>
                            </div>
                            <a href="{{ route('candidate.resume.view', ['locale' => app()->getLocale()]) }}" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ __('profile.view_resume') }}
                            </a>
                        </div>
                    @endif
                    <div id="resume-section" data-resume-upload class="flex flex-col gap-3 rounded-xl border border-dashed border-stone-300 bg-stone-50/70 p-4 dark:border-stone-700 dark:bg-stone-950/40 sm:flex-row sm:items-center">
                        <input
                            id="resume"
                            name="resume"
                            type="file"
                            accept=".pdf,.doc,.docx"
                            aria-describedby="resume-help"
                            class="peer sr-only"
                            onchange="document.getElementById('resume-name').textContent = this.files[0]?.name || '{{ __('profile.no_file_chosen') }}'"
                        />
                        <label for="resume" class="inline-flex min-h-11 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 peer-focus-visible:ring-2 peer-focus-visible:ring-amber-400 peer-focus-visible:ring-offset-2 dark:peer-focus-visible:ring-offset-stone-900">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V4.5m0 0L7.5 9M12 4.5 16.5 9M4.5 15.75v2.625A2.625 2.625 0 007.125 21h9.75a2.625 2.625 0 002.625-2.625V15.75" /></svg>
                            {{ $user->candidateProfile?->resume_path ? __('profile.replace_resume') : __('profile.choose_file') }}
                        </label>
                        <span id="resume-name" aria-live="polite" class="min-w-0 flex-1 truncate text-sm text-stone-600 dark:text-stone-400">{{ __('profile.no_file_chosen') }}</span>
                    </div>
                    <p id="resume-help" class="flex items-center gap-1.5 text-xs text-stone-500 dark:text-stone-400">
                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                        {{ __('profile.resume_formats') }}
                    </p>
                </div>
            @elseif($user->hasRole('Recruiter') && $user->company)
                <div class="space-y-2">
                    <label for="company_name" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.company_name') }}
                    </label>
                    <input
                        id="company_name"
                        name="company[name]"
                        type="text"
                        value="{{ old('company.name', $user->company->name) }}"
                        required
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>

                <div class="space-y-2">
                    <label for="company_tagline" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.tagline') }}
                    </label>
                    <input
                        id="company_tagline"
                        name="company[tagline]"
                        type="text"
                        value="{{ old('company.tagline', $user->company->tagline) }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>


                <div class="space-y-2">
                    <label for="company_location" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.location') }}
                    </label>
                    <input
                        id="company_location"
                        name="company[location]"
                        type="text"
                        value="{{ old('company.location', $user->company->location) }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>

                <div class="space-y-2">
                    <label for="company_website_url" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.website_url') }}
                    </label>
                    <input
                        id="company_website_url"
                        name="company[website_url]"
                        type="url"
                        value="{{ old('company.website_url', $user->company->website_url) }}"
                        placeholder="{{ __('profile.website_placeholder') }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>

                <div class="space-y-2">
                    <label for="company_linkedin_url" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.linkedin_url') }}
                    </label>
                    <input
                        id="company_linkedin_url"
                        name="company[linkedin_url]"
                        type="url"
                        value="{{ old('company.linkedin_url', $user->company->linkedin_url) }}"
                        placeholder="{{ __('profile.linkedin_placeholder') }}"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>

                <div class="space-y-2">
                    <label for="logo" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.company_logo') }}
                    </label>
                    @if($user->company->logo_url)
                        <img src="{{ $user->company->logo_url }}" alt="Company logo" class="mb-2 h-20 w-20 rounded-lg object-cover">
                    @endif
                    <div class="flex items-center gap-3">
                        <label for="logo" class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                            {{ __('profile.choose_logo') }}
                        </label>
                        <input
                            id="logo"
                            name="logo"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            onchange="document.getElementById('logo-name').textContent = this.files[0]?.name || '{{ __('profile.no_file_chosen') }}'"
                        />
                        <span id="logo-name" class="text-sm text-stone-600 dark:text-stone-400">{{ __('profile.no_file_chosen') }}</span>
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('profile.logo_formats') }}</p>
                </div>

                <div class="space-y-2">
                    <label for="company_mission" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.mission') }}
                    </label>
                    <textarea
                        id="company_mission"
                        name="company[mission]"
                        rows="3"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    >{{ old('company.mission', $user->company->mission) }}</textarea>
                </div>

                <div class="space-y-2">
                    <label for="company_culture" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.culture') }}
                    </label>
                    <textarea
                        id="company_culture"
                        name="company[culture]"
                        rows="3"
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    >{{ old('company.culture', $user->company->culture) }}</textarea>
                </div>
            @endif

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                >
                    {{ __('profile.update_profile') }}
                </button>
            </div>
            </fieldset>
        </form>
    </div>

    <!-- Change Email -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 mb-6">
        <h2 class="text-xl font-semibold text-stone-900 dark:text-white mb-6">{{ __('profile.change_email_address') }}</h2>
        
        @if($user->pending_email)
            <div class="mb-6 rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200">
                <p class="font-medium">{{ __('profile.pending_email_change', ['email' => $user->pending_email]) }}</p>
                <p class="mt-1">{{ __('profile.check_new_email_inbox') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ localized_route('profile.email.request') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <fieldset data-email-settings-fields @disabled($user->is_demo) class="space-y-6 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-60">

            <div class="space-y-2">
                <label for="current_email" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('profile.current_email') }}
                </label>
                <input
                    id="current_email"
                    type="email"
                    value="{{ $user->email }}"
                    disabled
                    class="w-full rounded-2xl border border-stone-200/80 bg-stone-100 px-4 py-3 text-sm text-stone-500 shadow-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400"
                />
            </div>

            <div class="space-y-2">
                <label for="new_email" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('profile.new_email_address') }}
                </label>
                <input
                    id="new_email"
                    name="email"
                    type="email"
                    required
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
                <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('profile.verification_email_sent') }}</p>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                >
                    {{ __('profile.request_email_change') }}
                </button>
            </div>
            </fieldset>
        </form>
    </div>

    <!-- Change Password -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 mb-6">
        <h2 class="text-xl font-semibold text-stone-900 dark:text-white mb-6">{{ __('profile.change_password') }}</h2>
        
        <form method="POST" action="{{ localized_route('profile.password') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <fieldset data-password-settings-fields @disabled($user->is_demo) class="space-y-6 [&_:disabled]:cursor-not-allowed [&_:disabled]:opacity-60">

            <div class="space-y-2">
                <label for="current_password" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('profile.current_password') }}
                </label>
                <input
                    id="current_password"
                    name="current_password"
                    type="password"
                    required
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
            </div>

            <div class="space-y-2">
                <label for="password" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('profile.new_password') }}
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('profile.confirm_new_password') }}
                </label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                >
                    {{ __('profile.change_password') }}
                </button>
            </div>
            </fieldset>
        </form>
    </div>

    <!-- Delete Account -->
    @if($user->is_demo)
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 dark:border-amber-800 dark:bg-amber-900/30">
        <h2 class="mb-4 text-xl font-semibold text-amber-900 dark:text-amber-200">{{ __('common.demo_account') }}</h2>
        <p class="text-sm text-amber-800 dark:text-amber-300">
            {{ __('common.demo_account_protected_description') }}
        </p>
    </div>
    @else
    <div class="rounded-xl border border-red-200 bg-red-50 p-8 dark:border-red-800 dark:bg-red-900/30" x-data="{ showModal: false }">
        <h2 class="text-xl font-semibold text-red-900 dark:text-red-200 mb-4">{{ __('profile.delete_account') }}</h2>
        <p class="text-sm text-red-800 dark:text-red-300 mb-6">
            {{ __('profile.delete_account_warning') }}
        </p>
        
        <button
            @click="showModal = true"
            type="button"
            class="inline-flex items-center justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-200 active:bg-red-700"
        >
            {{ __('profile.delete_account') }}
        </button>

        <!-- Delete Account Modal -->
        <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

                <!-- Center modal -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <!-- Modal panel -->
                <div x-show="showModal" x-trap.noscroll="showModal" role="dialog" aria-modal="true" aria-labelledby="delete-account-title" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <h3 id="delete-account-title" class="text-xl font-semibold text-stone-900 dark:text-white">
                                {{ __('profile.delete_account') }}
                            </h3>
                            <div class="mt-3">
                                <p class="text-sm text-stone-600 dark:text-stone-400">
                                    {{ __('profile.delete_account_confirmation') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-stone-50/80 px-6 py-4 dark:bg-stone-800/40 sm:flex sm:flex-row-reverse sm:px-8">
                        <form method="POST" action="{{ localized_route('profile.destroy') }}" class="w-full sm:w-auto">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full justify-center rounded-2xl bg-red-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-red-500/30 transition hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900 sm:w-auto">
                                {{ __('profile.delete_account') }}
                            </button>
                        </form>
                        <button @click="showModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-2xl border border-stone-200/80 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-200 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-900 sm:mr-3 sm:mt-0 sm:w-auto">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
