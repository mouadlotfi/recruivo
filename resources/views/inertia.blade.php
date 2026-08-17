<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="{{ request()->cookie('recruivo:theme') === 'light' ? 'light' : 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Recruivo') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="bg-stone-100 font-sans text-stone-900 antialiased dark:bg-stone-950 dark:text-stone-100">
    @inertia
</body>
</html>
