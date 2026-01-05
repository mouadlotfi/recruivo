<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | List all available locales for the application.
    | The key is the locale code and the value contains locale-specific configuration.
    |
    */

    'available' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇬🇧',
            'script' => 'Latn',
            'dir' => 'ltr',
            'enabled' => true,
        ],
        'fr' => [
            'name' => 'French',
            'native' => 'Français',
            'flag' => '🇫🇷',
            'script' => 'Latn',
            'dir' => 'ltr',
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale that will be used by the application.
    |
    */

    'default' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available.
    |
    */

    'fallback' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Hide Default Locale in URL
    |--------------------------------------------------------------------------
    |
    | When set to true, the default locale will be hidden from URLs.
    | Example: /en/about becomes /about
    |
    */

    'hide_default_in_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Supported Locale Codes
    |--------------------------------------------------------------------------
    |
    | Array of supported locale codes for quick validation.
    | Automatically generated from available locales where enabled = true.
    |
    */

    'supported' => ['en', 'fr'],
];

