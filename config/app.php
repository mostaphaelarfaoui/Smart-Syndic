<?php

return [

    'name' => env('APP_NAME', 'Smart Syndic'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL'),
    'timezone' => 'Africa/Casablanca',
    'locale' => 'ar',
    'fallback_locale' => 'fr',
    'faker_locale' => 'fr_FR',

    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),

    'maintenance' => [
        'driver' => 'file',
    ],

];
