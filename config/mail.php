<?php

return [
    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', 'sandbox.smtp.mailtrap.io'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', null),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    // Without this block the Markdown renderer falls back to the copies inside
    // the framework, so a published theme in resources/views/vendor/mail is
    // ignored and every edit to it silently does nothing. Point it at the
    // published views so restyling the mail actually takes effect.
    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Recruivo'),
    ],

    // Contact-page messages land here and the legal pages publish it: a monitored
    // mailbox, not a noreply address.
    'contact_address' => env('MAIL_CONTACT_ADDRESS', 'mouad.lotfi.work@gmail.com'),

    // Where the system reports trouble with itself, as opposed to mail being
    // sent: currently a queue that has stopped moving. Defaults to the contact
    // mailbox so it reaches a human with no extra setup.
    'operations_address' => env('MAIL_OPERATIONS_ADDRESS', env('MAIL_CONTACT_ADDRESS', 'mouad.lotfi.work@gmail.com')),
];
