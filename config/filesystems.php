<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    /*
    |--------------------------------------------------------------------------
    | Application Resume Directory
    |--------------------------------------------------------------------------
    |
    | Sub-directory on the private disk where resumes submitted with a job
    | application are stored. One source of truth shared by every surface
    | that accepts an application (the Inertia web flow and the API), so
    | uploads can't land in different folders depending on entry point.
    |
    */

    'application_resumes' => 'application-resumes',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'visibility' => 'private',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
