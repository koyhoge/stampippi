<?php

return [
    'name' => env('APP_NAME', 'Stampippi'),
    'env' => env('APP_ENV', 'develop'),
    'debug' => (bool) env('APP_DEBUG', false),
    'dev_tools' => (bool) env('APP_DEV_TOOLS', env('APP_ENV', 'develop') !== 'production'),
    'initial_admins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APP_INITIAL_ADMINS', env('APP_ADMINS', '')))
    ))),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Tokyo'),
    'locale' => env('APP_LOCALE', 'ja'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'ja_JP'),
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];

