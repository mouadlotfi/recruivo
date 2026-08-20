<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="{{ request()->cookie('recruivo:theme') === 'light' ? 'light' : 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Recruivo') }}</title>

    {{-- Inertia v3 head manager. The slot is static fallback content for
         full-page loads; Vue <Head> components take over page-specific
         metadata once the app boots. --}}
    <x-inertia::head>
        <meta name="description" content="Recruivo connects IT professionals with modern teams — engineering, cloud, security, and data roles with transparent hiring.">

        {{-- Hreflang tags for SEO (route-safe: only rendered on a resolved web route) --}}
        @php
            $currentRoute = Route::current() !== null ? Route::currentRouteName() : null;
            $routeParams = [];

            if ($currentRoute !== null) {
                $routeParams = collect(Route::current()->parameters())
                    ->except('locale')
                    ->map(function ($param) {
                        if (is_object($param) && method_exists($param, 'getRouteKey')) {
                            return $param->getRouteKey();
                        }
                        return $param;
                    })
                    ->toArray();
            }

            $availableLocales = config('locales.available', []);
        @endphp
        @if($currentRoute !== null)
            @foreach($availableLocales as $locale => $localeConfig)
                @if($localeConfig['enabled'] ?? true)
                    <link rel="alternate" hreflang="{{ $locale }}" href="{{ localized_route($currentRoute, $routeParams, $locale) }}" />
                @endif
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ localized_route($currentRoute, $routeParams, config('locales.default', 'en')) }}" />
        @endif
    </x-inertia::head>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    @vite('resources/js/app.ts')
</head>
<body class="min-h-screen bg-stone-100 font-sans text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
    <x-inertia::app />
</body>
</html>