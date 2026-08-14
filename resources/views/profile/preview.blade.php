@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div>
        <a href="{{ localized_route('profile.edit') }}" class="text-sm font-medium text-amber-600 hover:text-amber-500 dark:text-amber-400">← {{ __('profile.back_to_profile_settings') }}</a>
        <p class="mt-5 text-sm font-medium text-stone-500 dark:text-stone-400">{{ __('profile.recruiter_preview') }}</p>
        <div class="mt-3 flex items-center gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-amber-100 text-2xl font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                {{ mb_strtoupper(mb_substr($applicant->name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-3xl font-bold text-stone-900 dark:text-white">{{ $applicant->name }}</h1>
                @if($applicant->candidateProfile?->headline)
                    <p class="mt-1 text-stone-600 dark:text-stone-400">{{ $applicant->candidateProfile->headline }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <aside class="space-y-6">
            <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
                <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('recruiter.contact_information') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-stone-500">{{ __('profile.email_address') }}</dt><dd class="mt-1 break-all text-stone-800 dark:text-stone-200">{{ $applicant->email }}</dd></div>
                    <div><dt class="text-stone-500">{{ __('profile.phone_number') }}</dt><dd class="mt-1 text-stone-800 dark:text-stone-200">{{ $applicant->phone ?: __('recruiter.not_provided') }}</dd></div>
                </dl>
            </section>
        </aside>

        <div class="space-y-6 lg:col-span-2">
            @include('recruiter.applicants.partials.structured-profile')
        </div>
    </div>
</div>
@endsection