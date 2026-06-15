<?php

return [
    'name'       => env('APP_NAME', 'MegaforBB'),
    'version'    => \App\Version::VERSION,
    'version_check_url' => env('VERSION_CHECK_URL', \App\Version::DEFAULT_VERSION_CHECK_URL),
    'env'        => env('APP_ENV', 'production'),
    'debug'      => (bool) env('APP_DEBUG', false),
    'url'        => rtrim(env('APP_URL', 'http://localhost'), '/'),
    'locale'     => env('APP_LOCALE', 'tr'),
    'timezone'   => env('APP_TIMEZONE', 'Europe/Istanbul'),
    'key'        => env('APP_KEY', ''),
    'theme'     => env('THEME', 'default'),
];
