<?php
// config/mail.php
return [
    'driver' => env('MAIL_DRIVER', 'smtp'),

    'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
    'from_name' => env('MAIL_FROM_NAME', 'PHP Mini StarterKit'),

    'smtp' => [
        'host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'), // tls, ssl, or null
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'from_name' => env('MAIL_FROM_NAME', 'PHP Mini StarterKit'),

        // Office 365 Settings
        'office365' => [
            'host' => 'smtp.office365.com',
            'port' => 587,
            'encryption' => 'tls',
        ],
    ],

    'sendmail' => [
        'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs'),
    ],
];