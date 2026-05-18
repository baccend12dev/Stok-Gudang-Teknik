<?php

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 10080), // 120 menit
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION', null),
    'table' => 'sessions',
    'store' => env('SESSION_STORE', null),
    'lottery' => [2, 100],
    'cookie' => env(
        'SESSION_COOKIE',
        str_slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', false),
    'http_only' => true,
    'same_site' => 'lax',
    // KONFIGURASI KRITIS UNTUK PHP 5.6
    'cookie_httponly' => true,
    'cookie_secure' => false,
    'use_cookies' => true,
    'gc_probability' => 1,
    'gc_divisor' => 100,
    'gc_maxlifetime' => 14400, // 4 jam
    'cookie_lifetime' => 86400, // 24 jam
];