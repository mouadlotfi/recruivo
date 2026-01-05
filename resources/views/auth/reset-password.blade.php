@extends('layouts.guest')

@section('content')
<div class="mx-auto flex max-w-xl flex-col items-center py-12">
    <div class="w-full space-y-8 rounded-3xl border border-stone-200/70 bg-white/80 p-10 shadow-2xl shadow-amber-500/10 dark:border-stone-800/60 dark:bg-stone-950/80">
        <div class="space-y-3 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                </svg>
            </div>
            <h1 class="font-display text-3xl font-bold text-stone-900 dark:text-white">{{ __('auth.set_new_password') }}</h1>
            <p class="text-sm text-stone-600 dark:text-stone-400">
                {{ __('auth.enter_new_password') }}
            </p>
        </div>

        @if($errors->any())
            <x-alert type="error">
                @foreach($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </x-alert>
        @endif

        <form method="POST" action="{{ localized_route('password.update') }}" class="space-y-6">
            @csrf
            
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email ?? old('email') }}">
            
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.email') }}
                </label>
                <input
                    id="email"
                    type="email"
                    value="{{ $email ?? old('email') }}"
                    disabled
                    class="w-full rounded-2xl border border-stone-200/80 bg-stone-100 px-4 py-3 text-sm text-stone-700 shadow-sm dark:border-stone-700 dark:bg-stone-800/70 dark:text-stone-300"
                />
            </div>

            <div class="space-y-2">
                <label for="password" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.new_password') }}
                </label>
                <div class="relative" x-data="{ show: false }">
                    <input
                        id="password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="{{ __('auth.new_password_placeholder') }}"
                        required
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 pr-12 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300"
                    >
                        <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="text-sm font-medium text-stone-700 dark:text-stone-200">
                    {{ __('auth.password_confirmation') }}
                </label>
                <div class="relative" x-data="{ show: false }">
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        :type="show ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="{{ __('auth.confirm_password_placeholder') }}"
                        required
                        class="w-full rounded-2xl border border-stone-200/80 bg-white/80 px-4 py-3 pr-12 text-sm text-stone-700 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300"
                    >
                        <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
            >
                {{ __('auth.reset_password_button') }}
            </button>
        </form>

        <p class="text-center text-sm text-stone-500 dark:text-stone-400">
            <a href="{{ localized_route('login') }}" class="font-semibold text-amber-600 transition hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">
                {{ __('auth.back_to_login') }}
            </a>
        </p>
    </div>
</div>

<!-- Alpine.js for password toggle -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection

