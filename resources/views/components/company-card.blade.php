@props(['company'])

<div class="group relative rounded-xl border border-stone-200/60 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60">
    <div class="flex items-start gap-4">
        @if($company->logo_url)
            <div class="h-20 w-20 flex-shrink-0 overflow-hidden rounded-lg">
                <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="h-full w-full object-cover" />
            </div>
        @else
            <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white text-xl font-semibold">
                {{ substr($company->name, 0, 1) }}
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-lg text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                <a href="{{ localized_route('companies.show', ['slug' => $company->slug]) }}" class="before:absolute before:inset-0 focus:outline-none focus-visible:before:rounded-xl focus-visible:before:ring-2 focus-visible:before:ring-amber-500 focus-visible:before:ring-offset-2 dark:focus-visible:before:ring-offset-stone-950">
                    {{ $company->name }}
                </a>
            </h3>
            @if($company->tagline)
                <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $company->tagline }}</p>
            @endif
            <div class="mt-2 flex flex-wrap gap-2 text-xs text-stone-500 dark:text-stone-400">
                @if($company->location)
                    <a href="{{ localized_route('search', ['location' => $company->location, 'filter' => 'jobs']) }}"
                       data-company-location-link
                       class="relative z-10 flex items-center gap-1 rounded-md transition hover:text-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:hover:text-amber-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        {{ $company->location }}
                    </a>
                @endif
                @if(isset($company->jobs_count))
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        {{ $company->jobs_count }} {{ Str::plural('job', $company->jobs_count) }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
