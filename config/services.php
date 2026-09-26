<?php

return [
    'umami' => [
        // Self-hosted analytics. The script is proxied under our own origin
        // (Caddyfile: /u/*) so the browser never talks to a third party and the
        // content security policy keeps script-src/connect-src at 'self'.
        'website_id' => env('UMAMI_WEBSITE_ID'),
        'script_url' => env('UMAMI_SCRIPT_URL', '/u/script.js'),
        // Comma-delimited hostnames the tracker may run on (data-domains). Left
        // empty by default so every environment tracks; the deployed stacks set
        // their own host (recruivo.work / demo.recruivo.work) to keep staging
        // hosts from reporting into production.
        'domains' => env('UMAMI_DOMAINS'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
];
