<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="{{ request()->cookie('recruivo:theme') === 'light' ? 'light' : 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Rendered here because social crawlers do not run JavaScript, so anything
        // set through Vue's <Head> is invisible to them.
        $pageComponent = $page['component'] ?? '';
        $pageProps = $page['props'] ?? [];

        $siteName = config('app.name', 'Recruivo');
        $siteDescription = 'Recruivo connects IT professionals with modern teams — engineering, cloud, security, and data roles with transparent hiring.';

        // A controller may supply this itself; the derivation below is the fallback.
        $suppliedMeta = is_array($pageProps['meta'] ?? null) ? $pageProps['meta'] : [];

        $metaTitle = $suppliedMeta['title'] ?? null;
        $metaDescription = $suppliedMeta['description'] ?? null;
        $metaImage = $suppliedMeta['image'] ?? null;
        $metaType = $suppliedMeta['type'] ?? 'website';

        if ($metaTitle === null) {
            if ($pageComponent === 'Jobs/Show') {
                $jobTitle = $pageProps['job']['title'] ?? '';
                $jobCompany = $pageProps['job']['company']['name'] ?? null;

                $metaTitle = $jobTitle ?: null;
                $metaDescription = $jobCompany
                    ? __('jobs.meta_description_with_company', ['title' => $jobTitle, 'company' => $jobCompany])
                    : null;
                $metaImage = $pageProps['job']['company']['logo_url'] ?? null;
            } elseif ($pageComponent === 'Companies/Show') {
                $metaTitle = $pageProps['company']['name'] ?? null;
                $metaDescription = $pageProps['company']['tagline'] ?? ($pageProps['company']['mission'] ?? null);
                $metaImage = $pageProps['company']['logo_url'] ?? null;
            } elseif ($pageComponent === 'Posts/Show') {
                $metaTitle = $pageProps['post']['title'] ?? null;
                $metaDescription = $pageProps['post']['excerpt'] ?? null;
                $metaImage = $pageProps['post']['featured_image_url'] ?? null;
                $metaType = 'article';
            }
        }

        $metaDescription = filled($metaDescription)
            ? Str::limit(trim(strip_tags($metaDescription)), 160)
            : $siteDescription;
        $metaTitle = $metaTitle ? $metaTitle.' — '.$siteName : $siteName;

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

        $canonicalUrl = $currentRoute !== null
            ? localized_route($currentRoute, $routeParams, app()->getLocale())
            : url()->current();

        $availableLocales = config('locales.available', []);
    @endphp

    <title inertia>{{ $metaTitle }}</title>

    {{-- Inertia v3 head manager. The slot is static fallback content for
         full-page loads; Vue <Head> components take over page-specific
         metadata once the app boots. --}}
    <x-inertia::head>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:type" content="{{ $metaType }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        @if($metaImage)
            <meta property="og:image" content="{{ $metaImage }}">
        @endif
        <meta name="twitter:card" content="{{ $metaImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        @if($metaImage)
            <meta name="twitter:image" content="{{ $metaImage }}">
        @endif

        {{-- Hreflang tags for SEO (route-safe: only rendered on a resolved web route) --}}
        @if($currentRoute !== null)
            @foreach($availableLocales as $locale => $localeConfig)
                @if($localeConfig['enabled'] ?? true)
                    <link rel="alternate" hreflang="{{ $locale }}" href="{{ localized_route($currentRoute, $routeParams, $locale) }}" />
                @endif
            @endforeach
            <link rel="alternate" hreflang="x-default" href="{{ localized_route($currentRoute, $routeParams, config('locales.default', 'en')) }}" />
        @endif
    </x-inertia::head>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    @vite('resources/js/app.ts')
</head>
<body class="min-h-screen bg-stone-100 font-sans text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
    <x-inertia::app />

    @if(config('services.umami.website_id'))
        <script defer src="{{ config('services.umami.script_url') }}" data-website-id="{{ config('services.umami.website_id') }}"></script>
    @endif
</body>
</html>