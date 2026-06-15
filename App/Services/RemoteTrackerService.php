<?php

declare(strict_types=1);

namespace App\Services;

/**
 * MegaforBB Uzak Takip Servisi
 *
 * Kurulum bilgilerini (domain, sunucu IP, dizin, PHP sürümü) uzak takip sunucusuna gönderir.
 * - Cron: ping() — her çalışmada gönderir
 * - Sayfa kapanışı: pingIfDue() — günde en fazla 1 kez (index.php shutdown)
 */
class RemoteTrackerService
{
    private const DEFAULT_TRACKER_URL = 'https://www.megaforbb.org/api.php';

    private const LOCK_FILENAME = 'tracker_last_ping.txt';

    private const LOCK_TTL = 86400;

    private const LOG_FILENAME = 'tracker-ping.log';

    /**
     * Cron tarafından çağrılır. Kilitsiz doğrudan ping atar.
     *
     * @return array{success: bool, message: string, skipped?: bool}
     */
    public static function ping(): array
    {
        return self::sendPing(false);
    }

    /**
     * Sayfa kapanışında çağrılır. Son başarılı ping 24 saatten yeniyse atlanır.
     *
     * @return array{success: bool, message: string, skipped?: bool}
     */
    public static function pingIfDue(): array
    {
        return self::sendPing(true);
    }

    /**
     * @return array{success: bool, message: string, skipped?: bool}
     */
    private static function sendPing(bool $respectDailyLock): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            if (!function_exists('curl_init')) {
                $result['message'] = 'cURL uzantısı yüklü değil.';
                self::log($result['message']);
                return $result;
            }

            $basePath = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2);
            $storageDir = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage';
            $pingFile = $storageDir . DIRECTORY_SEPARATOR . self::LOCK_FILENAME;
            $trackerUrl = self::trackerUrl();

            if ($respectDailyLock && self::isDailyLockActive($pingFile, $trackerUrl)) {
                $result = ['success' => true, 'message' => 'Günlük ping zaten gönderildi.', 'skipped' => true];
                self::log($result['message'] . ' [' . self::resolveDomain() . ']');
                return $result;
            }

            if ($respectDailyLock && function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            $data = self::collectPayload($basePath);
            $httpCode = 0;
            $curlError = '';
            $response = self::executeRequest($data, $trackerUrl, $httpCode, $curlError);

            if ($response === false || $httpCode !== 200) {
                $body = is_string($response) ? trim(substr($response, 0, 120)) : '';
                $result['message'] = "Başarısız – HTTP {$httpCode}"
                    . ($curlError !== '' ? " – {$curlError}" : '')
                    . ($body !== '' ? " – {$body}" : '');
                self::log($result['message'] . ' | domain=' . $data['domain'] . ' | url=' . $trackerUrl);
                return $result;
            }

            if ($respectDailyLock) {
                if (!is_dir($storageDir)) {
                    @mkdir($storageDir, 0755, true);
                }
                @file_put_contents($pingFile, time() . '|' . md5($trackerUrl));
            }

            $result['success'] = true;
            $result['message'] = "Başarılı – {$data['domain']} bilgisi gönderildi.";
            self::log($result['message'] . ' | url=' . $trackerUrl);
        } catch (\Throwable $e) {
            $result['message'] = 'Hata: ' . $e->getMessage();
            self::log($result['message']);
        }

        return $result;
    }

    private static function isDailyLockActive(string $pingFile, string $trackerUrl): bool
    {
        if (!is_file($pingFile)) {
            return false;
        }

        $raw = trim((string) @file_get_contents($pingFile));
        if ($raw === '') {
            return false;
        }

        // Yeni format: timestamp|url_hash — takip URL değiştiyse kilidi yok say
        if (str_contains($raw, '|')) {
            [$ts, $urlHash] = explode('|', $raw, 2);
            if (md5($trackerUrl) !== $urlHash) {
                return false;
            }

            return (time() - (int) $ts) < self::LOCK_TTL;
        }

        // Eski format: sadece timestamp — hangi URL/domain bilinmediği için kilidi yok say
        return false;
    }

    /**
     * @return array{domain: string, server_ip: string, path: string, php_version: string}
     */
    private static function collectPayload(string $basePath): array
    {
        return [
            'domain'      => self::resolveDomain(),
            'server_ip'   => $_SERVER['SERVER_ADDR'] ?? self::getServerIp(),
            'path'        => $basePath,
            'php_version' => phpversion(),
        ];
    }

    private static function resolveDomain(): string
    {
        $override = trim((string) env('MEGAFORBB_TRACKER_DOMAIN', ''));
        if ($override !== '') {
            return self::normalizeHost($override);
        }

        // Web isteği: ziyaretçinin gördüğü domain (forum.megaforbb.org vhost)
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== '' && $host !== 'unknown' && PHP_SAPI !== 'cli') {
            return self::normalizeHost($host);
        }

        // Cron/CLI: APP_URL / core_config tek doğru kaynak
        $appUrl = self::canonicalAppUrl();
        if ($appUrl !== '') {
            $parsed = parse_url($appUrl, PHP_URL_HOST);
            if (is_string($parsed) && $parsed !== '') {
                return self::normalizeHost($parsed);
            }
        }

        $serverName = trim((string) ($_SERVER['SERVER_NAME'] ?? ''));
        if ($serverName !== '' && $serverName !== 'unknown' && !self::looksLikeMachineHostname($serverName)) {
            return self::normalizeHost($serverName);
        }

        $fallback = php_uname('n');
        return $fallback !== '' ? self::normalizeHost($fallback) : 'unknown';
    }

    private static function canonicalAppUrl(): string
    {
        if (function_exists('core_config')) {
            $url = trim((string) core_config('app.url', ''));
            if ($url !== '') {
                return $url;
            }
        }

        return trim((string) env('APP_URL', ''));
    }

    private static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        if (str_contains($host, ':')) {
            $host = (string) parse_url('http://' . $host, PHP_URL_HOST);
        }

        return $host !== '' ? $host : 'unknown';
    }

    private static function looksLikeMachineHostname(string $host): bool
    {
        return (bool) preg_match('/^(localhost|ip-|server\d+|ns\d+|web\d+|cpanel\.)/i', $host);
    }

    private static function trackerUrl(): string
    {
        $url = trim((string) env('MEGAFORBB_TRACKER_URL', self::DEFAULT_TRACKER_URL));
        return $url !== '' ? $url : self::DEFAULT_TRACKER_URL;
    }

    /**
     * @param array{domain: string, server_ip: string, path: string, php_version: string} $data
     */
    private static function executeRequest(array $data, string $trackerUrl, int &$httpCode, string &$curlError): string|false
    {
        $ch = curl_init($trackerUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'MegaforBB-Tracker/1.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = (string) curl_error($ch);
        curl_close($ch);

        return $response;
    }

    private static function getServerIp(): string
    {
        try {
            $hostname = gethostname();
            if ($hostname !== false) {
                $ip = gethostbyname($hostname);
                if ($ip !== $hostname) {
                    return $ip;
                }
            }
        } catch (\Throwable $e) {
        }

        return 'unknown';
    }

    private static function log(string $message): void
    {
        try {
            $basePath = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2);
            $logDir = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $line = gmdate('Y-m-d H:i:s') . ' UTC | ' . $message . PHP_EOL;
            @file_put_contents($logDir . DIRECTORY_SEPARATOR . self::LOG_FILENAME, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
    }
}
