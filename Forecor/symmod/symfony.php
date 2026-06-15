<?php declare(strict_types=1);

/**
 * Symfony bileşenleri bootstrap.
 * - Dotenv: .env dosyasını yükler, $_ENV ve getenv() doldurulur.
 * - İleride: Request, Session, CSRF, RateLimiter burada başlatılabilir.
 */

(function () {
    $basePath = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2);
    $envFile = $basePath . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($envFile)) {
        $legacyEnvFile = $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . '.env';
        if (is_file($legacyEnvFile)) {
            $envFile = $legacyEnvFile;
        }
    }

    if (!is_file($envFile)) {
        return;
    }

    if (class_exists(\Symfony\Component\Dotenv\Dotenv::class)) {
        $dotenv = new \Symfony\Component\Dotenv\Dotenv();
        $dotenv->loadEnv($envFile);
    }
})();
