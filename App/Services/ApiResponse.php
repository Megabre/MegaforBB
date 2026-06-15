<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Ortak API JSON yanıtı. Content-Type, status code ve çıktı tek noktadan.
 * Başarılı yanıtlar: success() veya send() ile ham veri.
 * Hata yanıtları: error() ile mesaj ve status.
 */
final class ApiResponse
{
    /**
     * Ham JSON gönderir (mevcut API uyumluluğu için).
     * Header + status + echo; exit yok (controller return edebilir).
     */
    public static function send(array $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Standart başarı formatı: { "ok": true, "data": ... }
     */
    public static function success(mixed $data = null, int $statusCode = 200): void
    {
        $payload = ['ok' => true];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        self::send($payload, $statusCode);
    }

    /**
     * Standart hata formatı: { "ok": false, "error": "mesaj" }
     */
    public static function error(string $message, int $statusCode = 400): void
    {
        self::send(['ok' => false, 'error' => $message], $statusCode);
    }
}
