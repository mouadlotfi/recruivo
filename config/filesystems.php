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

        /*
         * Backup archives. The root is bind-mounted from the host so artifacts
         * survive container rebuilds and stay readable without Docker.
         *
         * spatie writes every archive into a `<backup name>/` subdirectory, and
         * Flysystem creates directories 0700 by default. The container writes
         * them as its own www-data user, which leaves the host's operator
         * neither the owner nor a member of the owning group - so being able to
         * read the archives rests on the 0755 / 0644 bits. Both mappings are set
         * to those because which one Flysystem picks at write time is not
         * obvious from the outside, and because neither is touched by a 002 or
         * 022 umask, so the mode that lands on disk is the mode written here.
         * An archive nobody can read is not a backup.
         */
        'backups' => [
            'driver' => 'local',
            'root' => env('BACKUP_DISK_ROOT', storage_path('app/backups')),
            'permissions' => [
                'file' => ['public' => 0644, 'private' => 0644],
                'dir' => ['public' => 0755, 'private' => 0755],
            ],
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
