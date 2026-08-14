@props(['job'])

@php
    $user = auth()->user();
    $isCandidate = $user && $user->hasRole('Candidate');
    $userHasApplied = $isCandidate && $user->applications()->where('job_id', $job->id)->exists();
    $isSaved = (bool) ($job->is_saved ?? false);
    $isDemoCandidate = $isCandidate && $user->is_demo;
    $remoteTypeStyles = [
        'hybrid' => 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-500/10 dark:text-green-300 dark:hover:bg-green-500/20',
        'onsite' => 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-500/10 dark:text-orange-300 dark:hover:bg-orange-500/20',
        'remote' => 'bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-500/10 dark:text-purple-300 dark:hover:bg-purple-500/20',
    ];
@endphp

<div class="group relative h-full rounded-xl border border-stone-200/60 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60">
    {{-- Applied Badge + Bookmark Control --}}
    @if($userHasApplied || $isCandidate)
        <div class="absolute right-3 top-3 z-10 flex items-center gap-2">
            @if($userHasApplied)
                <div class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ __('common.applied') }}
                </div>
            @endif
            @if($isDemoCandidate)
                <button
                    type="button"
                    disabled
                    title="{{ __('jobs.demo_cannot_save_jobs') }}"
                    aria-label="{{ __('jobs.demo_cannot_save_jobs') }}"
                    class="relative z-10 inline-flex h-11 w-11 cursor-not-allowed items-center justify-center rounded-full text-stone-300 dark:text-stone-600"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                    </svg>
                </button>
            @elseif($isSaved)
                <form method="POST" action="{{ localized_route('candidate.saved-jobs.destroy', $job) }}">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        aria-label="{{ __('jobs.remove_saved_job') }}"
                        class="relative z-10 inline-flex h-11 w-11 items-center justify-center rounded-full text-amber-600 transition hover:bg-stone-100 hover:text-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-400 dark:hover:bg-stone-800"
                    >
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0111.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 01-1.085.67L12 18.089l-7.165 3.583A.75.75 0 013.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ localized_route('candidate.saved-jobs.store', $job) }}">
                    @csrf
                    <button
                        type="submit"
                        aria-label="{{ __('jobs.save_job') }}"
                        class="relative z-10 inline-flex h-11 w-11 items-center justify-center rounded-full text-stone-400 transition hover:bg-stone-100 hover:text-amber-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:hover:bg-stone-800"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    @endif
    
    <div class="flex items-start gap-4 {{ $userHasApplied ? 'pr-36' : ($isCandidate ? 'pr-16' : '') }}">
            @if($job->company && $job->company->logo_url)
                <div class="h-16 w-16 flex-shrink-0 overflow-hidden rounded-xl">
                    <img src="{{ $job->company->logo_url }}" alt="{{ $job->company->name }}" class="h-full w-full object-cover" />
                </div>
            @else
                <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-lg font-semibold text-white">
                    {{ $job->company ? substr($job->company->name, 0, 1) : 'J' }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                    <a href="{{ localized_route('jobs.show', $job->id) }}" class="before:absolute before:inset-0">
                        {{ $job->title }}
                    </a>
                </h3>
                @if($job->company)
                    <p class="text-sm text-stone-600 dark:text-stone-400">{{ $job->company->name }}</p>
                @endif
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-stone-500 dark:text-stone-400">
                    @if($job->location)
                        <a data-job-location-link href="{{ localized_route('search', ['location' => $job->location, 'filter' => 'jobs']) }}" class="relative z-10 flex items-center gap-1 rounded-md transition hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:hover:text-amber-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            {{ $job->location }}
                        </a>
                    @endif
                    @if($job->remote_type)
                        <a href="{{ localized_route('search', ['search' => '', 'remote_type' => strtolower($job->remote_type)]) }}"
                           class="relative z-10 rounded-full px-2 py-0.5 font-medium transition {{ $remoteTypeStyles[strtolower($job->remote_type)] ?? 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20' }}">
                            {{ ucfirst($job->remote_type) }}
                        </a>
                    @endif
                    @if($job->category)
                        <a href="{{ localized_route('search', ['search' => $job->category]) }}"
                           class="relative z-10 rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 transition hover:bg-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20">
                            {{ $job->category }}
                        </a>
                    @endif
                </div>
                @if($job->salary_min || $job->salary_max)
                    <p class="mt-2 text-sm font-medium text-stone-700 dark:text-stone-300">
                        ${{ number_format($job->salary_min ?? 0) }} - ${{ number_format($job->salary_max ?? 0) }}
                    </p>
                @endif
                @if($job->isClosingSoon())
                    <p class="mt-2 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        {{ __('jobs.closing_soon') }} · {{ __('jobs.closes_on', ['date' => $job->closes_at->format('M j, Y')]) }}
                    </p>
                @endif
            </div>
    </div>
</div>
