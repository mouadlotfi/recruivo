@php
    $user = auth()->user();
    $isRecruiter = $user && $user->hasRole('Recruiter');
    $isCandidate = $user && $user->hasRole('Candidate');
    $isAdmin = $user && $user->hasRole('Admin');
    $itemClass = 'flex min-w-0 flex-col items-center justify-center rounded-lg px-1 py-2 transition';
    $activeClass = 'text-amber-600 dark:text-amber-400';
    $inactiveClass = 'text-stone-600 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-800';
@endphp

<nav aria-label="{{ __('common.primary_navigation') }}" class="fixed bottom-0 left-0 right-0 z-40 border-t border-stone-200 bg-white/95 backdrop-blur-xl dark:border-stone-800 dark:bg-stone-950/95 sm:hidden">
    <div class="grid grid-cols-4 gap-1 px-2 py-2">
        @auth
            @if(!$isRecruiter && ($isCandidate || $isAdmin))
                <a href="{{ localized_route($isCandidate ? 'candidate.dashboard' : 'admin.dashboard') }}" class="{{ $itemClass }} {{ request()->routeIs($isCandidate ? 'candidate.dashboard' : 'admin.*') ? $activeClass : $inactiveClass }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25A2.25 2.25 0 018.25 10.5H6a2.25 2.25 0 01-2.25-2.25V6zm9.75 0a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zm9.75 0a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                    <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.dashboard') }}</span>
                </a>
            @endif
        @else
            <a href="{{ localized_route('home') }}" class="{{ $itemClass }} {{ request()->routeIs('home') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l9-9 9 9M4.5 9.75V21h5.25v-6h4.5v6h5.25V9.75" /></svg>
                <span class="mt-1 text-[11px] font-medium">{{ __('common.home') }}</span>
            </a>
        @endauth

        @if($isRecruiter)
            <div class="relative" x-data="{ open: false }" data-recruiter-mobile-explore-menu>
                <button type="button" @click="open = !open" @click.away="open = false" @keydown.escape.window="open = false" :aria-expanded="open.toString()" aria-haspopup="menu" class="{{ $itemClass }} w-full {{ request()->routeIs('jobs.*', 'companies.*') ? $activeClass : $inactiveClass }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.25-2.25 3.375-5.25 3.375-9S14.25 5.25 12 3m0 18c-2.25-2.25-3.375-5.25-3.375-9S9.75 5.25 12 3M3.375 12h17.25" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ __('common.explore') }}</span>
                </button>
                <div x-show="open" x-transition style="display: none;" role="menu" class="fixed bottom-16 left-4 right-4 mb-3 w-auto max-w-[calc(100vw-1rem)] overflow-hidden rounded-xl border border-stone-200 bg-white py-1 text-left shadow-xl dark:border-stone-700 dark:bg-stone-900">
                    <a href="{{ localized_route('jobs.index') }}" role="menuitem" class="block px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">{{ __('common.jobs') }}</a>
                    <a href="{{ localized_route('companies.index') }}" role="menuitem" class="block px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">{{ __('common.companies') }}</a>
                </div>
            </div>
            <a href="{{ localized_route('recruiter.dashboard') }}" class="{{ $itemClass }} {{ request()->routeIs('recruiter.dashboard') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25A2.25 2.25 0 018.25 10.5H6a2.25 2.25 0 01-2.25-2.25V6zm9.75 0a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zm9.75 0a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.dashboard') }}</span>
            </a>
        @elseif(!$isAdmin)
            <a href="{{ localized_route('jobs.index') }}" class="{{ $itemClass }} {{ request()->routeIs('jobs.*') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75V5.25A2.25 2.25 0 0110.5 3h3a2.25 2.25 0 012.25 2.25v1.5M3.75 9h16.5v9.75A2.25 2.25 0 0118 21H6a2.25 2.25 0 01-2.25-2.25V9z" /></svg>
                <span class="mt-1 text-[11px] font-medium">{{ __('common.jobs') }}</span>
            </a>
            @if(!$isCandidate)
                <a href="{{ localized_route('companies.index') }}" class="{{ $itemClass }} {{ request()->routeIs('companies.*') ? $activeClass : $inactiveClass }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V3h10.5v18m0-13.5h6V21M7.5 7.5h3m-3 4h3m-3 4h3" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ __('common.companies') }}</span>
                </a>
            @endif
        @endif

        @if($isRecruiter)
            <a href="{{ localized_route('recruiter.jobs.index') }}" class="{{ $itemClass }} {{ request()->routeIs('recruiter.jobs.*') && !request()->routeIs('recruiter.jobs.applications') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h10.5M6.75 12h10.5M6.75 17.25h10.5" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.manage') }}</span>
            </a>
            <a href="{{ localized_route('recruiter.applicants.index') }}" class="{{ $itemClass }} {{ request()->routeIs('recruiter.applicants.*') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('recruiter.applicants') }}</span>
            </a>

        @elseif($isCandidate)
            <a href="{{ localized_route('candidate.saved-jobs.index') }}" class="{{ $itemClass }} {{ request()->routeIs('candidate.saved-jobs.*') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.saved_jobs_short') }}</span>
            </a>
            <a href="{{ localized_route('candidate.applications') }}" class="{{ $itemClass }} {{ request()->routeIs('candidate.applications') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75M6.75 3.75h10.5A2.25 2.25 0 0119.5 6v12A2.25 2.25 0 0117.25 20.25H6.75A2.25 2.25 0 014.5 18V6a2.25 2.25 0 012.25-2.25z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.my_applications_short') }}</span>
            </a>
        @elseif(!$isAdmin)
            <a href="{{ localized_route('login') }}" class="{{ $itemClass }} {{ request()->routeIs('login', 'register') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" /></svg>
                <span class="mt-1 text-[11px] font-medium">{{ __('common.log_in') }}</span>
            </a>
        @endif
    </div>
</nav>
