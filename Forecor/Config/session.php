<?php

return [
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'secure'   => (bool) (env('SESSION_SECURE_COOKIE', false) || str_starts_with(rtrim((string) env('APP_URL', ''), '/'), 'https://')),
    'name'     => env('SESSION_NAME', 'MEGAFORBB_SESSION'),
    'path'     => '/',
    'httponly' => true,
    'samesite' => in_array(env('SESSION_SAMESITE', 'Lax'), ['Strict', 'Lax', 'None'], true) ? env('SESSION_SAMESITE', 'Lax') : 'Lax',
];
