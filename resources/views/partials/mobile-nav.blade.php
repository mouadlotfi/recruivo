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
            {{-- Dashboard --}}
            <a href="{{ localized_route('recruiter.dashboard') }}" class="{{ $itemClass }} {{ request()->routeIs('recruiter.dashboard') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25A2.25 2.25 0 018.25 10.5H6a2.25 2.25 0 01-2.25-2.25V6zm9.75 0a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zm9.75 0a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.dashboard') }}</span>
            </a>
            {{-- Manage --}}
            <a href="{{ localized_route('recruiter.jobs.index') }}" class="{{ $itemClass }} {{ request()->routeIs('recruiter.jobs.*') && !request()->routeIs('recruiter.jobs.applications') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h10.5M6.75 12h10.5M6.75 17.25h10.5" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.manage') }}</span>
            </a>
            {{-- Explore --}}
            <div class="relative" x-data="{ open: false }" data-recruiter-mobile-explore-menu>
                <button type="button" @click="open = !open" @click.away="open = false" @keydown.escape.window="open = false" :aria-expanded="open.toString()" aria-haspopup="menu" class="{{ $itemClass }} w-full {{ request()->routeIs('jobs.*', 'companies.*') ? $activeClass : $inactiveClass }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.25-2.25 3.375-5.25 3.375-9S14.25 5.25 12 3m0 18c-2.25-2.25-3.375-5.25-3.375-9S9.75 5.25 12 3M3.375 12h17.25" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ __('common.explore') }}</span>
                </button>
                <div x-show="open" x-transition style="display: none;" role="menu" class="absolute bottom-full right-0 mb-2 w-max min-w-36 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 text-left shadow-xl dark:border-stone-700 dark:bg-stone-900">
                    <a href="{{ localized_route('jobs.index') }}" role="menuitem" class="block whitespace-nowrap px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">{{ __('common.jobs') }}</a>
                    <a href="{{ localized_route('companies.index') }}" role="menuitem" class="block whitespace-nowrap px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">{{ __('common.companies') }}</a>
                </div>
            </div>
            {{-- Settings --}}
            <a href="{{ localized_route('profile.edit') }}" class="{{ $itemClass }} {{ request()->routeIs('profile.edit') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ $isRecruiter ? __('common.company_profile') : __('common.profile_settings') }}</span>
            </a>
        @elseif($isCandidate)
            {{-- Dashboard already rendered by the @auth block above --}}
            {{-- Explore --}}
            <div class="relative" x-data="{ open: false }" data-candidate-mobile-explore-menu>
                <button type="button" @click="open = !open" @click.away="open = false" @keydown.escape.window="open = false" :aria-expanded="open.toString()" aria-haspopup="menu" class="{{ $itemClass }} w-full {{ request()->routeIs('jobs.*', 'companies.*') ? $activeClass : $inactiveClass }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.25-2.25 3.375-5.25 3.375-9S14.25 5.25 12 3m0 18c-2.25-2.25-3.375-5.25-3.375-9S9.75 5.25 12 3M3.375 12h17.25" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ __('common.explore') }}</span>
                </button>
                <div x-show="open" x-transition style="display: none;" role="menu" class="absolute bottom-full right-0 mb-2 w-max min-w-36 overflow-hidden rounded-xl border border-stone-200 bg-white py-1 text-left shadow-xl dark:border-stone-700 dark:bg-stone-900">
                    <a href="{{ localized_route('jobs.index') }}" role="menuitem" class="block whitespace-nowrap px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">{{ __('common.jobs') }}</a>
                    <a href="{{ localized_route('companies.index') }}" role="menuitem" class="block whitespace-nowrap px-4 py-3 text-sm font-medium text-stone-700 hover:bg-amber-50 hover:text-amber-700 dark:text-stone-200 dark:hover:bg-amber-500/10 dark:hover:text-amber-300">{{ __('common.companies') }}</a>
                </div>
            </div>
            {{-- Saved Jobs --}}
            <a href="{{ localized_route('candidate.saved-jobs.index') }}" class="{{ $itemClass }} {{ request()->routeIs('candidate.saved-jobs.*') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.saved_jobs_short') }}</span>
            </a>
            {{-- Applications --}}
            <a href="{{ localized_route('candidate.applications') }}" class="{{ $itemClass }} {{ request()->routeIs('candidate.applications') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75M6.75 3.75h10.5A2.25 2.25 0 0119.5 6v12A2.25 2.25 0 0117.25 20.25H6.75A2.25 2.25 0 014.5 18V6a2.25 2.25 0 012.25-2.25z" /></svg>
                <span class="mt-1 truncate text-[11px] font-medium">{{ __('common.my_applications_short') }}</span>
            </a>
        @elseif(!$isAdmin)
            {{-- Guest / admin-less: Home, Jobs, Companies, Log in --}}
            <a href="{{ localized_route('jobs.index') }}" class="{{ $itemClass }} {{ request()->routeIs('jobs.*') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75V5.25A2.25 2.25 0 0110.5 3h3a2.25 2.25 0 012.25 2.25v1.5M3.75 9h16.5v9.75A2.25 2.25 0 0118 21H6a2.25 2.25 0 01-2.25-2.25V9z" /></svg>
                <span class="mt-1 text-[11px] font-medium">{{ __('common.jobs') }}</span>
            </a>
            <a href="{{ localized_route('companies.index') }}" class="{{ $itemClass }} {{ request()->routeIs('companies.*') ? $activeClass : $inactiveClass }}">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21V3h10.5v18m0-13.5h6V21M7.5 7.5h3m-3 4h3m-3 4h3" /></svg>
                <span class="mt-1 text-[11px] font-medium">{{ __('common.companies') }}</span>
            </a>
            @guest
                <a href="{{ localized_route('login') }}" class="{{ $itemClass }} {{ request()->routeIs('login', 'register') ? $activeClass : $inactiveClass }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" /></svg>
                    <span class="mt-1 text-[11px] font-medium">{{ __('common.log_in') }}</span>
                </a>
            @endguest
        @endif
    </div>
</nav>
