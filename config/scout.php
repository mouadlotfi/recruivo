<?php

return [
    'driver' => env('SCOUT_DRIVER', 'null'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => env('SCOUT_AFTER_COMMIT', false),

    'soft_delete' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    'identify' => env('SCOUT_IDENTIFY', false),

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
        'key' => env('MEILISEARCH_KEY'),
    ],
];
