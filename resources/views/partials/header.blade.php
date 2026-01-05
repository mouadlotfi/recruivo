@php
    $user = auth()->user();
    $isRecruiter = $user && $user->hasRole('Recruiter');
    $isCandidate = $user && $user->hasRole('Candidate');
    $isAdmin = $user && $user->hasRole('Admin');
    $displayName = $isRecruiter ? ($user->company->name ?? $user->name) : $user?->name;
@endphp

<header class="sticky top-0 z-[9999] border-b border-stone-200/60 bg-white/75 backdrop-blur-xl dark:border-stone-800/70 dark:bg-stone-950/80">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
        <div class="flex items-center gap-8">
            <a href="{{ localized_route('home') }}" class="group flex items-center gap-2.5 text-lg font-semibold">
                <span class="inline-flex h-9 w-9 items-center justify-center transition group-hover:scale-105">
                    <svg viewBox="0 0 48 48" fill="none" class="h-9 w-9" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="logo-amber" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#F59E0B"/>
                                <stop offset="100%" stop-color="#D97706"/>
                            </linearGradient>
                            <linearGradient id="logo-teal" x1="100%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#14B8A6"/>
                                <stop offset="100%" stop-color="#0F766E"/>
                            </linearGradient>
                        </defs>
                        <path d="M8 24C8 15.163 15.163 8 24 8" stroke="url(#logo-amber)" stroke-width="6" stroke-linecap="round"/>
                        <path d="M40 24C40 32.837 32.837 40 24 40" stroke="url(#logo-teal)" stroke-width="6" stroke-linecap="round"/>
                        <circle cx="24" cy="24" r="5" fill="url(#logo-amber)"/>
                    </svg>
                </span>
                <span class="font-semibold tracking-tight text-stone-900 transition group-hover:text-amber-600 dark:text-white dark:group-hover:text-amber-400">
                    Recruivo
                </span>
            </a>
            <nav class="hidden items-center gap-4 text-sm font-medium text-stone-600 lg:flex dark:text-stone-300">
                <a href="{{ localized_route('jobs.index') }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('jobs.*') ? 'text-amber-600 dark:text-amber-400' : '' }}">
                    {{ __('common.jobs') }}
                </a>
                <a href="{{ localized_route('companies.index') }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('companies.*') && !($isRecruiter && request()->routeIs('companies.show') && request()->route('slug') == $user->company?->slug) ? 'text-amber-600 dark:text-amber-400' : '' }}">
                    {{ __('common.companies') }}
                </a>
                @if($isRecruiter)
                    <a href="{{ localized_route('recruiter.jobs.index') }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('recruiter.jobs.*') ? 'text-amber-600 dark:text-amber-400' : '' }}">
                        {{ __('common.my_jobs') }}
                    </a>
                    <a href="{{ localized_route('recruiter.dashboard') }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('recruiter.dashboard') ? 'text-amber-600 dark:text-amber-400' : '' }}">
                        {{ __('common.dashboard') }}
                    </a>
                    @if($user->company && $user->company->slug)
                        <a href="{{ localized_route('companies.show', $user->company->slug) }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('companies.show') && request()->route('slug') == $user->company->slug ? 'text-amber-600 dark:text-amber-400' : '' }}">
                            {{ __('common.profile') }}
                        </a>
                    @endif
                @endif
                @if($isAdmin)
                    <a href="{{ localized_route('admin.dashboard') }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('admin.*') ? 'text-amber-600 dark:text-amber-400' : '' }}">
                        {{ __('common.dashboard') }}
                    </a>
                @endif
                @if($isCandidate)
                    <a href="{{ localized_route('candidate.dashboard') }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('candidate.dashboard') ? 'text-amber-600 dark:text-amber-400' : '' }}">
                        {{ __('common.dashboard') }}
                    </a>
                    <a href="{{ localized_route('candidate.applications') }}" class="whitespace-nowrap transition hover:text-amber-600 dark:hover:text-amber-400 {{ request()->routeIs('candidate.applications') ? 'text-amber-600 dark:text-amber-400' : '' }}">
                        {{ __('common.my_applications') }}
                    </a>
                @endif
            </nav>
        </div>
        <div class="flex items-center gap-2 sm:gap-3">
            {{-- Mobile Search Button --}}
            <button
                id="mobile-search-toggle"
                type="button"
                class="inline-flex items-center justify-center rounded-full p-1.5 text-stone-600 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800 sm:hidden"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <span class="sr-only">{{ __('common.search') }}</span>
            </button>
            
            {{-- Desktop Search --}}
            <div class="relative hidden sm:block search-container">
                <form action="{{ localized_route('search') }}" method="GET" class="relative">
                    <label for="nav-search" class="sr-only">{{ __('common.search_jobs') }}</label>
                    <input
                        id="nav-search"
                        name="search"
                        value="{{ request('search') }}"
                        type="search"
                        placeholder="{{ __('common.search_placeholder') }}"
                        autocomplete="off"
                        class="search-input w-48 lg:w-64 rounded-full border border-stone-200 bg-white/80 py-1.5 pl-4 pr-10 text-sm text-stone-600 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900/70 dark:text-stone-200 dark:focus:border-amber-500 text-ellipsis"
                    />
                    <button
                        type="submit"
                        class="absolute inset-y-0 right-2 flex items-center justify-center rounded-full bg-amber-100 px-2 text-amber-600 transition hover:bg-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <span class="sr-only">{{ __('common.search') }}</span>
                    </button>
                </form>
            </div>
            
            @auth
                @if(!$isAdmin)
                    <div class="relative" x-data="{ open: false }">
                        <button
                            @click="open = !open"
                            @click.away="open = false"
                            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800"
                        >
                            @if($isRecruiter && $user->company && $user->company->logo_url)
                                <img
                                    src="{{ $user->company->logo_url }}"
                                    alt="{{ $user->company->name }} logo"
                                    class="h-5 w-5 rounded-full object-cover"
                                />
                            @elseif($isRecruiter)
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                                    {{ substr($displayName, 0, 1) }}
                                </span>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            @endif
                            <span class="hidden max-w-[120px] truncate sm:inline">{{ $displayName }}</span>
                        </button>
                        
                        <div
                            x-show="open"
                            x-transition
                            class="absolute right-0 mt-2 w-48 rounded-lg border border-stone-200 bg-white py-1 shadow-lg dark:border-stone-700 dark:bg-stone-800"
                            style="display: none;"
                        >
                            <div class="px-4 py-2 text-xs font-medium text-stone-500 dark:text-stone-400">
                                {{ $user->email }}
                            </div>
                            <div class="border-t border-stone-200 dark:border-stone-700"></div>
                            <a
                                href="{{ localized_route('profile.edit') }}"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $isRecruiter ? __('common.company_profile') : __('common.profile_settings') }}
                            </a>
                            <form method="POST" action="{{ localized_route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                    </svg>
                                    {{ __('common.sign_out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ localized_route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('common.sign_out') }}</span>
                        </button>
                    </form>
                @endif
            @else
                <a
                    href="{{ localized_route('login') }}"
                    class="mobile-hidden-auth inline-flex items-center justify-center whitespace-nowrap rounded-full border border-stone-200/80 px-3 py-1.5 text-sm font-semibold text-stone-600 transition hover:border-amber-300 hover:text-amber-600 dark:border-stone-700 dark:text-stone-200 dark:hover:border-amber-400 dark:hover:text-amber-300"
                >
                    {{ __('common.log_in') }}
                </a>
                <a
                    href="{{ localized_route('register') }}"
                    class="mobile-hidden-auth inline-flex items-center justify-center whitespace-nowrap rounded-full bg-amber-600 px-4 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500 dark:hover:bg-amber-500/90"
                >
                    {{ __('common.sign_up') }}
                </a>
            @endauth
            
            {{-- Hide Login/Sign Up on Mobile --}}
            @guest
                <style>
                    @media (max-width: 640px) {
                        .mobile-hidden-auth {
                            display: none !important;
                        }
                    }
                </style>
            @endguest
            
            {{-- Language Switcher --}}
            @php
                // Prepare route parameters for language switcher
                $switcherRouteParams = collect(Route::current()->parameters())
                    ->except('locale')
                    ->map(function ($param) {
                        if (is_object($param) && method_exists($param, 'getRouteKey')) {
                            return $param->getRouteKey();
                        }
                        return $param;
                    })
                    ->toArray();
            @endphp
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open"
                    @click.away="open = false"
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-stone-600 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" />
                    </svg>
                    <span class="hidden sm:inline">{{ app()->getLocale() === 'fr' ? 'FR' : 'EN' }}</span>
                </button>
                <div
                    x-show="open"
                    x-transition
                    class="absolute right-0 mt-2 w-36 rounded-lg border border-stone-200 bg-white py-1 shadow-lg dark:border-stone-700 dark:bg-stone-800"
                    style="display: none;"
                >
                <a href="{{ localized_route(Route::currentRouteName(), $switcherRouteParams, 'en') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700 {{ app()->getLocale() === 'en' ? 'bg-amber-50 font-semibold text-amber-600 dark:bg-amber-500/10 dark:text-amber-400' : '' }}">
                    <span class="text-lg">🇬🇧</span>
                    <span>English</span>
                </a>
                <a href="{{ localized_route(Route::currentRouteName(), $switcherRouteParams, 'fr') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-stone-700 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700 {{ app()->getLocale() === 'fr' ? 'bg-amber-50 font-semibold text-amber-600 dark:bg-amber-500/10 dark:text-amber-400' : '' }}">
                    <span class="text-lg">🇫🇷</span>
                    <span>Français</span>
                </a>
                </div>
            </div>
            
            <button
                id="theme-toggle"
                type="button"
                class="inline-flex items-center justify-center rounded-full p-1.5 text-stone-600 transition hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-800"
            >
                <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
                <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
                <span class="sr-only">{{ __('common.toggle_theme') }}</span>
            </button>
        </div>
    </div>
</header>

{{-- Mobile Search Modal --}}
<div id="mobile-search-modal" class="fixed inset-0 z-[10000] hidden bg-black/60 backdrop-blur-sm">
    <div class="flex min-h-full items-start justify-center p-3 pt-4">
        <div class="w-full max-w-md rounded-xl bg-white p-4 shadow-2xl dark:bg-stone-800">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-base font-semibold text-stone-900 dark:text-white">{{ __('common.search') }}</h3>
                <button
                    id="mobile-search-close"
                    type="button"
                    class="rounded-full p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-stone-600 dark:hover:bg-stone-700 dark:hover:text-stone-300"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="relative search-container">
                <form action="{{ localized_route('search') }}" method="GET">
                    <input
                        type="search"
                        name="search"
                        placeholder="{{ __('common.search_placeholder') }}"
                        autocomplete="off"
                        autofocus
                        class="search-input w-full rounded-lg border border-stone-200 bg-white py-2.5 pl-4 pr-11 text-sm text-stone-900 shadow-sm transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500"
                    />
                    <button
                        type="submit"
                        class="absolute inset-y-0 right-1.5 my-1 flex items-center justify-center rounded-md bg-amber-600 px-2.5 text-white transition hover:bg-amber-500"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </button>
                </form>
            </div>
            <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">{{ __('common.search_hint') }}</p>
        </div>
    </div>
</div>

<!-- Alpine.js for dropdown -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

