<?php
// config/app.php
return [
    'name' => env('APP_NAME', 'PHP Mini StarterKit'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'key' => env('APP_KEY', ''),

    'locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => 'en',

    'cipher' => 'AES-256-CBC',
];

