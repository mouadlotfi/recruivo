@extends('layouts.guest')

@section('content')
<div class="mx-auto flex max-w-xl flex-col items-center py-12">
    <div class="w-full space-y-8 rounded-3xl border border-stone-200/70 bg-white/80 p-10 shadow-2xl shadow-amber-500/10 dark:border-stone-800/60 dark:bg-stone-950/80">
        <div class="space-y-3 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-500/10">
                <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
            </div>
            <h1 class="font-display text-3xl font-bold text-stone-900 dark:text-white">{{ __('auth.verify_email_short') }}</h1>
            <p class="text-sm text-stone-600 dark:text-stone-400">
                {{ __('auth.verify_email_desc_short') }}
            </p>
        </div>

        @if(session('message'))
            <x-alert type="success">
                {{ session('message') }}
            </x-alert>
        @endif

        <form method="POST" action="{{ localized_route('verification.send') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 focus:ring-offset-2 dark:focus:ring-offset-stone-950"
            >
                {{ __('auth.resend_verification') }}
            </button>
        </form>

        <form method="POST" action="{{ localized_route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full text-center text-sm text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-200"
            >
                {{ __('auth.log_out') }}
            </button>
        </form>
    </div>
</div>
@endsection

