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
            <h1 class="font-display text-3xl font-bold text-stone-900 dark:text-white">{{ __('auth.forgot_password_title') }}</h1>
            <p class="text-sm text-stone-600 dark:text-stone-400">
                {{ __('auth.forgot_password_desc') }}
            </p>
        </div>

        @if(session('status'))
            <x-alert type="success">
                {{ session('status') }}
            </x-alert>
        @endif

        @if($errors->any())
            <x-alert type="error">
                @foreach($errors->all() as $error)
                    {{ $error }}
                @endforeach
            </x-alert>
        @endif

        <form method="POST" action="{{ localized_route('password.email') }}" class="space-y-6">
            @csrf
            
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

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
            >
                {{ __('auth.email_reset_link') }}
            </button>
        </form>

        <div class="flex items-center justify-between text-sm">
            <a href="{{ localized_route('login') }}" class="font-semibold text-amber-600 transition hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">
                {{ __('auth.back_to_login') }}
            </a>
            <a href="{{ localized_route('register') }}" class="font-semibold text-amber-600 transition hover:text-amber-500 dark:text-amber-300 dark:hover:text-amber-200">
                {{ __('auth.create_account') }}
            </a>
        </div>
    </div>
</div>
@endsection

