<?php

declare(strict_types=1);

namespace Forecor\Core;

use Symfony\Component\HttpFoundation\Response;

/**
 * Nelmio tarzı güvenlik başlıkları. İki mod: Response nesnesine uygula veya doğrudan header() ile gönder.
 * CSP varsayılan kapalı (tema/asset bozulmasın); X-Frame-Options, X-Content-Type-Options, HSTS (opsiyonel) açık.
 */
final class SecurityHeaders
{
    private static string $defaultCsp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';";

    public static function apply(Response $response): Response
    {
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Content-Security-Policy', self::$defaultCsp);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        return $response;
    }

    /**
     * Tema/CSS/JS bozulmadan güvenli başlıkları gönderir (CSP yok).
     * Application::run() içinde echo'dan önce çağrılır. Ayarlar settings üzerinden okunur.
     */
    public static function sendSafeHeaders(Application $app): void
    {
        if ($app->getSetting('security_headers_enabled', '1') !== '1') {
            return;
        }
        if (headers_sent()) {
            return;
        }
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        $appUrl = (string) core_config('app.url', '');
        $useHsts = $app->getSetting('security_hsts_enabled', '0') === '1';
        if ($useHsts && (str_starts_with($appUrl, 'https://') || ($_SERVER['HTTPS'] ?? '') === 'on')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    public static function setDefaultCsp(string $csp): void
    {
        self::$defaultCsp = $csp;
    }
}
