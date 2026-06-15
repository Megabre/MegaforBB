<?php

declare(strict_types=1);

/**
 * Load config array by name. Config files live in Forecor/Config/*.php
 */

if (!function_exists('core_config')) {
    function core_config(string $key, $default = null)
    {
        static $cache = [];
        $parts = explode('.', $key);
        $file = $parts[0];
        if (!isset($cache[$file])) {
            $path = (defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2)) . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . $file . '.php';
            $cache[$file] = is_file($path) ? require $path : [];
        }
        $value = $cache[$file];
        for ($i = 1; $i < count($parts); $i++) {
            $value = $value[$parts[$i]] ?? $default;
            if ($value === $default && $i < count($parts) - 1) {
                return $default;
            }
        }
        $res = $value ?? $default;
        // Panelden açılıp kapatılabilmesi için app.debug veritabanındaki app_debug ile override edilir
        if ($key === 'app.debug' && class_exists(\App\Models\Setting::class)) {
            $dbDebug = \App\Models\Setting::getValue('app_debug', null);
            if ($dbDebug !== null && $dbDebug !== '') {
                return $dbDebug === '1' || $dbDebug === true || $dbDebug === 'true';
            }
        }
        return $res;
    }
}
