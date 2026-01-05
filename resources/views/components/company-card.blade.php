@props(['company'])

<a href="{{ localized_route('companies.show', ['slug' => $company->slug]) }}" class="group block rounded-xl border border-stone-200/60 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-stone-700/60 dark:bg-stone-900/60">
    <div class="flex items-start gap-4">
        @if($company->logo_url)
            <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="h-16 w-16 rounded-lg object-cover" />
        @else
            <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white text-xl font-semibold">
                {{ substr($company->name, 0, 1) }}
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <h3 class="font-semibold text-lg text-stone-900 group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400 transition">
                {{ $company->name }}
            </h3>
            @if($company->tagline)
                <p class="text-sm text-stone-600 dark:text-stone-400 mt-1">{{ $company->tagline }}</p>
            @endif
            <div class="mt-2 flex flex-wrap gap-2 text-xs text-stone-500 dark:text-stone-400">
                @if($company->location)
                    <span class="flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        {{ $company->location }}
                    </span>
                @endif
                @if(isset($company->jobs_count))
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        {{ $company->jobs_count }} {{ Str::plural('job', $company->jobs_count) }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</a>

