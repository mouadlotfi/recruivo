<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

return [
    'name' => env('APP_NAME', 'Recruivo'),

    'env' => env('APP_ENV', 'production'),

    'is_demo' => (bool) env('APP_IS_DEMO', env('APP_ENV') === 'demo'),

    'demo_scheduled_reset' => (bool) env('DEMO_SCHEDULED_RESET', false),

    // Canonical accounts the demo login page can autofill. UserSeeder and
    // ApplicationSeeder read these so the seeded credentials cannot drift from
    // the ones the login form offers.
    'demo_accounts' => [
        'candidate' => ['email' => 'candidate@recruivo.tech', 'password' => 'password'],
        'recruiter' => ['email' => 'recruiter@recruivo.tech', 'password' => 'password'],
        'admin' => ['email' => 'admin@recruivo.tech', 'password' => 'password'],
    ],

    'production_url' => env('PRODUCTION_URL'),

    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => 'UTC',

    'locale' => 'en',

    'fallback_locale' => 'en',

    'faker_locale' => 'fr_FR',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    // Blade compiles without `use` statements, so the two aliases
    // resources/views/inertia.blade.php resolves (Route::, Str::) must stay.
    // Every other facade consumer imports the class directly, so the rest of
    // the global alias list was dead weight.
    'aliases' => [
        'Route' => Route::class,
        'Str' => Str::class,
    ],
];
