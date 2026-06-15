<?php

declare(strict_types=1);

/**
 * Load .env file and provide env() helper.
 * Replace with Symfony Dotenv when composer is used.
 */

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        if (array_key_exists($key, $_ENV)) {
            $v = $_ENV[$key];
            if ($v === false || $v === '') {
                return $default;
            }
            return $v;
        }
        static $loaded = false;
        static $vars = [];
        if (!$loaded) {
            $basePath = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2);
            $candidates = [
                $basePath . DIRECTORY_SEPARATOR . '.env',
                $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . '.env',
            ];
            $path = null;
            foreach ($candidates as $candidate) {
                if (is_file($candidate) && is_readable($candidate)) {
                    $path = $candidate;
                    break;
                }
            }
            if ($path !== null) {
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
                        $vars[$m[1]] = trim($m[2], " \t\"'");
                    }
                }
            }
            $loaded = true;
        }
        return $vars[$key] ?? $default;
    }
}
