<?php
return [
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        'max_tokens' => 1024,
    ],
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        // Alias para compatibilidad: se prefiere account_sid, pero algunos verifiers leen "sid".
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'validate_signature' => filter_var(env('TWILIO_VALIDATE_SIGNATURE', 'true'), FILTER_VALIDATE_BOOLEAN),
        'reminder_content_sid' => env('TWILIO_REMINDER_CONTENT_SID', ''),
        'confirmation_content_sid' => env('TWILIO_CONFIRMATION_CONTENT_SID', ''),
    ],
    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],
    'mail' => [
        'driver' => env('MAIL_DRIVER', 'smtp'),
        'host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@turneroya.app'),
        'from_name' => env('MAIL_FROM_NAME', 'TurneroYa'),
        'resend_api_key' => env('RESEND_API_KEY'),
    ],
    'cron' => [
        'secret' => env('CRON_SECRET', ''),
    ],
];
