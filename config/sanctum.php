<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf('%s%s', 'localhost', env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''))),

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'verify_csrf_token' => VerifyCsrfToken::class,
        'ensure_front_cookie' => EnsureFrontendRequestsAreStateful::class,
    ],
];
