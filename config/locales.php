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
    | Supported Locale Codes
    |--------------------------------------------------------------------------
    |
    | Array of supported locale codes for quick validation.
    |
    */

    'supported' => ['en', 'fr'],
];
