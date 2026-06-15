<?php

declare(strict_types=1);

namespace App\Services\Rss;

/**
 * Basit HTTP GET (Guzzle yok); RSS URL'lerini çeker.
 */
class RssHttpFetchService
{
    private const UA = 'MegaforBB-RssImporter/1.0 (+https://www.php.net/)';

    public function get(string $url, int $timeoutSeconds = 20): ?string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            return null;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_USERAGENT => self::UA,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!is_string($body) || $body === '' || ($code > 0 && $code >= 400)) {
                return null;
            }

            return $body;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeoutSeconds,
                'follow_location' => 1,
                'user_agent' => self::UA,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if (!is_string($body) || $body === '') {
            return null;
        }

        return $body;
    }
}
