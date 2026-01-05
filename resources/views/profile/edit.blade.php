@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl">
    <h1 class="text-3xl font-bold text-stone-900 dark:text-white mb-8">
        @if($user->hasRole('Recruiter'))
            {{ __('profile.company_profile') }}
        @else
            {{ __('profile.profile_settings') }}
        @endif
    </h1>

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

    <!-- Profile Information -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 mb-6">
        <h2 class="text-xl font-semibold text-stone-900 dark:text-white mb-6">{{ __('profile.profile_information') }}</h2>
        
        <form method="POST" action="{{ localized_route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            @if($user->hasRole('Candidate'))
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
                    <label for="email" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.email_address') }}
                    </label>
                    <input
                        id="email"
                        type="email"
                        value="{{ $user->email }}"
                        disabled
                        class="w-full rounded-2xl border border-stone-200/80 bg-stone-100 px-4 py-3 text-sm text-stone-500 shadow-sm dark:border-stone-700 dark:bg-stone-800 dark:text-stone-400"
                    />
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('profile.email_change_info') }}</p>
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
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                </div>

                <div class="space-y-2">
                    <label for="resume" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.resume') }}
                    </label>
                    @if($user->candidateProfile && $user->candidateProfile->resume_path)
                        <div class="mb-3 flex items-center gap-3 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                            <div class="flex-1">
                                {{ __('profile.current_resume') }} <span class="font-medium">{{ basename($user->candidateProfile->resume_path) }}</span>
                            </div>
                            <a href="{{ route('candidate.resume.view', ['locale' => app()->getLocale()]) }}" 
                               target="_blank" 
                               class="inline-flex items-center gap-1 rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-green-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ __('profile.view_resume') }}
                            </a>
                        </div>
                    @endif
                    <div class="flex items-center gap-3">
                        <label for="resume" class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                            {{ __('profile.choose_file') }}
                        </label>
                        <input
                            id="resume"
                            name="resume"
                            type="file"
                            accept=".pdf,.doc,.docx"
                            class="hidden"
                            onchange="document.getElementById('resume-name').textContent = this.files[0]?.name || '{{ __('profile.no_file_chosen') }}'"
                        />
                        <span id="resume-name" class="text-sm text-stone-600 dark:text-stone-400">{{ __('profile.no_file_chosen') }}</span>
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('profile.resume_formats') }}</p>
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
                    <label for="company_email" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                        {{ __('profile.company_email') }}
                    </label>
                    <input
                        id="company_email"
                        name="company[email]"
                        type="email"
                        value="{{ old('company.email', $user->company->email) }}"
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
                        <img src="{{ $user->company->logo_url }}" alt="Company logo" class="w-20 h-20 rounded-lg object-cover mb-2">
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
        </form>
    </div>

    <!-- Change Password -->
    <div class="rounded-xl border border-stone-200/60 bg-white/80 p-8 backdrop-blur dark:border-stone-700/60 dark:bg-stone-900/60 mb-6">
        <h2 class="text-xl font-semibold text-stone-900 dark:text-white mb-6">{{ __('profile.change_password') }}</h2>
        
        <form method="POST" action="{{ localized_route('profile.password') }}" class="space-y-6">
            @csrf
            @method('PUT')

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
        </form>
    </div>

    <!-- Delete Account -->
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
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex min-h-screen items-center justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-stone-900/75 backdrop-blur-sm transition-opacity" @click="showModal = false"></div>

                <!-- Center modal -->
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>

                <!-- Modal panel -->
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block transform overflow-hidden rounded-2xl border border-stone-200/60 bg-white/95 text-left align-bottom shadow-2xl backdrop-blur transition-all dark:border-stone-700/60 dark:bg-stone-900/95 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="p-6 sm:p-8">
                        <div class="flex items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <h3 class="text-xl font-semibold text-stone-900 dark:text-white">
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
</div>
@endsection

