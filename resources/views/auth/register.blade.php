@extends('layouts.guest')

@section('content')
<div class="mx-auto flex max-w-xl flex-col items-center py-12">
    <div class="w-full space-y-8 rounded-3xl border border-stone-200/70 bg-white/80 p-10 shadow-2xl shadow-amber-500/10 dark:border-stone-800/60 dark:bg-stone-950/80">
        <div class="space-y-3 text-center">
            <h1 class="font-display text-3xl font-bold text-stone-900 dark:text-white">{{ __('auth.register_title') }}</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">
                {{ __('auth.register_subtitle') }}
            </p>
        </div>

        @if($errors->any())
            <x-alert type="error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </x-alert>
        @endif

        <form method="POST" action="{{ localized_route('register') }}" enctype="multipart/form-data" class="space-y-6" x-data="{ accountType: '{{ old('account_type', 'candidate') }}', showPassword: false, showPasswordConfirmation: false }">
            @csrf
            
            <!-- Account Type Selection -->
            <div class="space-y-3">
                <label class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.account_type') }}
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        @click="accountType = 'candidate'"
                        :class="accountType === 'candidate' ? 'border-amber-500 bg-amber-50 text-amber-700 dark:border-amber-400 dark:bg-amber-900/20 dark:text-amber-300' : 'border-stone-200 bg-white text-stone-600 hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:hover:border-stone-600'"
                        class="rounded-xl border p-3 text-left text-sm transition"
                    >
                        <div class="font-medium">{{ __('auth.account_type_candidate') }}</div>
                        <div class="text-xs opacity-75">{{ __('auth.account_type_candidate_desc') }}</div>
                    </button>
                    <button
                        type="button"
                        @click="accountType = 'company'"
                        :class="accountType === 'company' ? 'border-amber-500 bg-amber-50 text-amber-700 dark:border-amber-400 dark:bg-amber-900/20 dark:text-amber-300' : 'border-stone-200 bg-white text-stone-600 hover:border-stone-300 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:hover:border-stone-600'"
                        class="rounded-xl border p-3 text-left text-sm transition"
                    >
                        <div class="font-medium">{{ __('auth.account_type_company') }}</div>
                        <div class="text-xs opacity-75">{{ __('auth.account_type_company_desc') }}</div>
                    </button>
                </div>
                <input type="hidden" name="account_type" :value="accountType" />
            </div>

            <!-- Candidate Name -->
            <div x-show="accountType === 'candidate'" class="space-y-2">
                <label for="name" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.full_name') }}
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    autocomplete="name"
                    placeholder="{{ __('auth.full_name_placeholder') }}"
                    value="{{ old('name') }}"
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
            </div>

            <!-- Email -->
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.email') }}
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    placeholder="{{ __('auth.email_placeholder') }}"
                    required
                    value="{{ old('email') }}"
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
            </div>

            <!-- Phone (Candidate only) -->
            <div x-show="accountType === 'candidate'" class="space-y-2">
                <label for="phone" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.phone_number_optional') }}
                </label>
                <input
                    id="phone"
                    name="phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="+21261234567"
                    value="{{ old('phone') }}"
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <label for="password" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.password') }}
                </label>
                <div class="relative">
                    <input
                        id="password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="{{ __('auth.password_create_placeholder') }}"
                        required
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 pr-12 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300"
                    >
                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Password Confirmation -->
            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.confirm_password_label') }}
                </label>
                <div class="relative">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        :type="showPasswordConfirmation ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="{{ __('auth.password_confirm_placeholder') }}"
                        required
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 pr-12 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                    <button
                        type="button"
                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300"
                    >
                        <svg x-show="!showPasswordConfirmation" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showPasswordConfirmation" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Resume Upload (Candidate only) -->
            <div x-show="accountType === 'candidate'" class="space-y-2">
                <label for="resume" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.resume_optional') }}
                </label>
                <input
                    id="resume"
                    name="resume"
                    type="file"
                    accept=".pdf,.doc,.docx"
                    class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                />
                <p class="text-xs text-stone-500 dark:text-stone-400">
                    {{ __('auth.resume_help_text') }}
                </p>
            </div>

            <!-- Company Fields -->
            <template x-if="accountType === 'company'">
                <div class="space-y-6">
                    <div class="space-y-2">
                        <label for="recruiter_name" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                            {{ __('auth.your_full_name') }}
                        </label>
                        <input
                            id="recruiter_name"
                            name="name"
                            type="text"
                            placeholder="{{ __('auth.your_full_name_placeholder') }}"
                            value="{{ old('name') }}"
                            class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            {{ __('auth.recruiter_name_help') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="company_name" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                            {{ __('auth.company_name_label') }}
                        </label>
                        <input
                            id="company_name"
                            name="company[name]"
                            type="text"
                            placeholder="{{ __('auth.company_name_placeholder') }}"
                            value="{{ old('company.name') }}"
                            class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="company_email" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                            {{ __('auth.company_email') }}
                        </label>
                        <input
                            id="company_email"
                            name="company[email]"
                            type="email"
                            placeholder="{{ __('auth.company_email_placeholder') }}"
                            value="{{ old('company.email') }}"
                            class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            {{ __('auth.company_email_help') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="company_tagline" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                            {{ __('auth.company_tagline_optional') }}
                        </label>
                        <input
                            id="company_tagline"
                            name="company[tagline]"
                            type="text"
                            placeholder="{{ __('auth.company_tagline_placeholder') }}"
                            value="{{ old('company.tagline') }}"
                            class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="company_location" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                            {{ __('auth.company_location_optional') }}
                        </label>
                        <input
                            id="company_location"
                            name="company[location]"
                            type="text"
                            placeholder="{{ __('auth.company_location_placeholder') }}"
                            value="{{ old('company.location') }}"
                            class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="company_website" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                            {{ __('auth.website_url_optional') }}
                        </label>
                        <input
                            id="company_website"
                            name="company[website_url]"
                            type="url"
                            placeholder="{{ __('auth.website_url_placeholder') }}"
                            value="{{ old('company.website_url') }}"
                            class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="company_linkedin" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                            {{ __('auth.linkedin_url_optional') }}
                        </label>
                        <input
                            id="company_linkedin"
                            name="company[linkedin_url]"
                            type="url"
                            placeholder="{{ __('auth.linkedin_url_placeholder') }}"
                            value="{{ old('company.linkedin_url') }}"
                            class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                        />
                    </div>
                </div>
            </template>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
            >
                {{ __('auth.create_account_button') }}
            </button>
        </form>

        <p class="text-center text-sm text-stone-500 dark:text-stone-400">
            {{ __('auth.already_member') }}
            <a href="{{ localized_route('login') }}" class="font-semibold text-amber-600 transition hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">
                {{ __('auth.sign_in') }}
            </a>
        </p>
    </div>
</div>

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection

