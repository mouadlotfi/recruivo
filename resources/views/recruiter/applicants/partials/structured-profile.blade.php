@php
    use Carbon\Carbon;

    $profile = $applicant->candidateProfile;
    $formatProfileDate = function (?string $value): string {
        if (!$value) return '';
        if (preg_match('/^\d{4}$/', $value)) return $value;
        return Carbon::createFromFormat('Y-m', $value)->translatedFormat('M Y');
    };
@endphp

@if($profile)
    @if($applicant->profile_summary)
        <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
            <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('profile.about') }}</h2>
            <p class="mt-3 whitespace-pre-line text-sm text-stone-700 dark:text-stone-300">{{ $applicant->profile_summary }}</p>
        </section>
    @endif

    @if($profile->skills)
        <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
            <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('recruiter.skills') }}</h2>
            <p class="mt-3 whitespace-pre-line text-sm text-stone-700 dark:text-stone-300">{{ $profile->skills }}</p>
        </section>
    @endif

    @if(count($profile->languages_data ?? []))
        <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
            <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('recruiter.languages') }}</h2>
            <div class="mt-4 space-y-3">
                @foreach($profile->languages_data as $language)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-stone-50 px-4 py-3 text-sm dark:bg-stone-800">
                        <span class="font-medium text-stone-900 dark:text-white">{{ $language['language'] }}</span>
                        <span class="text-stone-500 dark:text-stone-400">{{ __('profile.proficiency_'.$language['proficiency']) }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if(count($profile->profile_links ?? []))
        <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
            <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('profile.links') }}</h2>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($profile->profile_links as $link)
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-xl bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20">{{ $link['name'] }} ↗</a>
                @endforeach
            </div>
        </section>
    @endif

    @if(count($profile->experiences ?? []))
        <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
            <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('recruiter.experience') }}</h2>
            <div class="mt-4 divide-y divide-stone-200 dark:divide-stone-700">
                @foreach($profile->experiences as $experience)
                    <article class="py-4 first:pt-0 last:pb-0">
                        <h3 class="font-semibold text-stone-900 dark:text-white">{{ $experience['job_title'] }}</h3>
                        <p class="text-sm text-stone-700 dark:text-stone-300">{{ $experience['company_name'] }}@if($experience['location']) · {{ $experience['location'] }}@endif</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $formatProfileDate($experience['start_date']) }} – {{ $experience['is_current'] ? __('profile.present') : $formatProfileDate($experience['end_date']) }}</p>
                        @if($experience['description'])<p class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400">{{ $experience['description'] }}</p>@endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if(count($profile->educations ?? []))
        <section class="rounded-xl border border-stone-200/60 bg-white/80 p-6 dark:border-stone-700/60 dark:bg-stone-900/60">
            <h2 class="font-semibold text-stone-900 dark:text-white">{{ __('recruiter.education') }}</h2>
            <div class="mt-4 divide-y divide-stone-200 dark:divide-stone-700">
                @foreach($profile->educations as $education)
                    <article class="py-4 first:pt-0 last:pb-0">
                        <h3 class="font-semibold text-stone-900 dark:text-white">{{ $education['school'] }}</h3>
                        <p class="text-sm text-stone-700 dark:text-stone-300">{{ $education['degree'] }} · {{ $education['field_of_study'] }}</p>
                        <p class="mt-1 text-sm text-stone-500">{{ $formatProfileDate($education['start_date']) }} – {{ $education['is_current'] ? __('profile.present') : $formatProfileDate($education['end_date']) }}</p>
                        @if($education['description'])<p class="mt-3 whitespace-pre-line text-sm text-stone-600 dark:text-stone-400">{{ $education['description'] }}</p>@endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endif
