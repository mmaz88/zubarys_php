<?php
// config/services.php
return [
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'provider' => env('WHATSAPP_PROVIDER', 'twilio'), // twilio, meta, custom

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_WHATSAPP_FROM'), // e.g., +14155238886
        ],

        'meta' => [
            'access_token' => env('META_WHATSAPP_ACCESS_TOKEN'),
            'phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
            'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN'),
        ],

        'custom' => [
            'webhook_url' => env('WHATSAPP_WEBHOOK_URL'),
            'api_key' => env('WHATSAPP_API_KEY'),
        ],
    ],

    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, nexmo, aws, custom

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_SMS_FROM'), // Your Twilio phone number
        ],

        'nexmo' => [
            'api_key' => env('NEXMO_API_KEY'),
            'api_secret' => env('NEXMO_API_SECRET'),
            'from' => env('NEXMO_SMS_FROM', 'YourApp'),
        ],

        'aws' => [
            'access_key' => env('AWS_ACCESS_KEY_ID'),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],

        'custom' => [
            'api_url' => env('SMS_API_URL'),
            'api_key' => env('SMS_API_KEY'),
        ],
    ],
];