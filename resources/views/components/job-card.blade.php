@props(['job'])

@php
    $userHasApplied = auth()->check() && auth()->user()->hasRole('Candidate') && auth()->user()->applications()->where('job_id', $job->id)->exists();
@endphp

<div class="group relative rounded-xl border border-stone-200/60 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60">
    {{-- Applied Badge --}}
    @if($userHasApplied)
        <div class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-500/10 dark:text-green-400">
            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            {{ __('common.applied') }}
        </div>
    @endif
    
    <a href="{{ localized_route('jobs.show', $job->id) }}" class="block">
        <div class="flex items-start gap-4 {{ $userHasApplied ? 'pr-20' : '' }}">
            @if($job->company && $job->company->logo_url)
                <img src="{{ $job->company->logo_url }}" alt="{{ $job->company->name }}" class="h-12 w-12 flex-shrink-0 rounded-lg object-cover" />
            @else
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white font-semibold">
                    {{ $job->company ? substr($job->company->name, 0, 1) : 'J' }}
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-stone-900 group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400 transition">
                    {{ $job->title }}
                </h3>
                @if($job->company)
                    <p class="text-sm text-stone-600 dark:text-stone-400">{{ $job->company->name }}</p>
                @endif
                <div class="mt-2 flex flex-wrap gap-2 text-xs text-stone-500 dark:text-stone-400">
                    @if($job->location)
                        <span class="flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            {{ $job->location }}
                        </span>
                    @endif
                    @if($job->remote_type)
                        <a href="{{ localized_route('search', ['search' => '', 'remote_type' => strtolower($job->remote_type)]) }}" 
                           onclick="event.stopPropagation()" 
                           class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20">
                            {{ ucfirst($job->remote_type) }}
                        </a>
                    @endif
                    @if($job->category)
                        <a href="{{ localized_route('search', ['search' => $job->category]) }}" 
                           onclick="event.stopPropagation()" 
                           class="rounded-full bg-stone-100 px-2 py-0.5 font-medium text-stone-700 transition hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600">
                            {{ $job->category }}
                        </a>
                    @endif
                </div>
                @if($job->salary_min || $job->salary_max)
                    <p class="mt-2 text-sm font-medium text-stone-700 dark:text-stone-300">
                        ${{ number_format($job->salary_min ?? 0) }} - ${{ number_format($job->salary_max ?? 0) }}
                    </p>
                @endif
            </div>
        </div>
    </a>
</div>

