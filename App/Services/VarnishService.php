<?php

declare(strict_types=1);

namespace App\Services;

use Forecor\Core\Application;

/**
 * Varnish FPC (Full Page Cache) Purge & Ban Yönetim Servisi
 */
class VarnishService
{
    private Application $app;
    private bool $enabled;
    private array $servers;
    private string $secret;

    public function __construct(Application $app)
    {
        $this->app = $app;

        // Admin Panel > Sistem Ayarları altından gelen değerleri al.
        $this->enabled = $app->getSetting('varnish_enabled', '0') === '1';

        $serverStr = trim((string) $app->getSetting('varnish_servers', '127.0.0.1:6081'));
        $this->servers = array_filter(array_map('trim', explode(',', $serverStr)));

        $this->secret = trim((string) $app->getSetting('varnish_secret', ''));
    }

    /**
     * Spseifik bir URL'yi Cache'den tam yol ile atar.
     */
    public function purge(string $urlPath): bool
    {
        if (!$this->enabled || empty($this->servers)) {
            return false;
        }

        $urlPath = '/' . ltrim($urlPath, '/');
        $success = true;

        foreach ($this->servers as $server) {
            $response = $this->sendRequest('PURGE', $server, $urlPath);
            if (!$response) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Regex formatında eşleşen tüm sayfaları temizler (BAN listesi).
     * Örn: ^/topic/123-.*$
     */
    public function ban(string $regexPattern): bool
    {
        if (!$this->enabled || empty($this->servers)) {
            return false;
        }

        $success = true;

        foreach ($this->servers as $server) {
            // Varnish'teki VCL config içinde X-Ban-Url header'ı dinlenmelidir.
            $headers = [
                'X-Ban-Url: ' . $regexPattern
            ];
            $response = $this->sendRequest('BAN', $server, '/', $headers);
            if (!$response) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Curl ile Varnish sunucusuna özel HTTP Method isteği yollar.
     */
    private function sendRequest(string $method, string $server, string $path, array $headers = []): bool
    {
        $host = parse_url(core_config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';

        if ($this->secret !== '') {
            $headers[] = 'X-Purge-Key: ' . $this->secret;
        }

        // Çoğu proxy yapılandırmasında Host Header'ı şarttır
        $headers[] = 'Host: ' . $host;

        $ch = curl_init();

        // Örn server: 127.0.0.1:6081
        $schema = str_starts_with($server, 'https://') ? 'https://' : 'http://';
        $target = $schema . preg_replace('#^https?://#', '', $server) . $path;

        curl_setopt($ch, CURLOPT_URL, $target);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Sistemi kitlememesi için timeout çok düşük olmalı
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        // Varnish genelde başarılı purge/ban işleminde 200 Döndürür. (Sentetik VCL dönüşü)
        return $httpCode === 200;
    }

    /**
     * VarnishService kancalarını (Event Listener'ları) sisteme dahil eder.
     * Bu fonksiyon AppServiceProvider veya EventServiceProvider içinden çağrılmalıdır.
     */
    public static function registerListeners(Application $app): void
    {
        $events = $app->events(); // Forecor\Core\Event sınıfı döndürür (Symfony EventDispatcher)
        if (!$events) {
            return;
        }

        $service = new self($app);

        // Bir konuya cevap yazıldığında o konunun tüm sayfalarını BAN'la
        $events->addListener(\Forecor\Core\Events::POST_CREATED, function ($event) use ($service) {
            $post = $event->getSubject(); // Post modeli geldiğini varsayıyoruz
            $topicId = $post->topic_id ?? null;
            if ($topicId) {
                // Regex: /topic/ID... ile başlayan tüm sayfaları (sayfalamalar dahil) sil
                $service->ban('^/topic/' . $topicId . '(-|/|$)');
                // Ana sayfanın ilk FPC'sini de temizleyelim (yeni mesaj görünmesi için)
                $service->purge('/');
                $service->purge('/forum');
            }
        });

        // Yeni konu açıldığında
        $events->addListener(\Forecor\Core\Events::TOPIC_CREATED, function ($event) use ($service) {
            $topic = $event->getSubject();
            $forumId = $topic->forum_id ?? null;
            $service->purge('/');
            $service->purge('/forum');
            if ($forumId) {
                // Sadece forum ana listesini banla
                $service->ban('^/forum/' . $forumId . '(-|/|$)');
            }
        });

        // Konu kilitlendiğinde, silindiğinde vs.
        $events->addListener(\Forecor\Core\Events::TOPIC_UPDATED, function ($event) use ($service) {
            $topic = $event->getSubject();
            $topicId = $topic->id ?? null;
            if ($topicId) {
                $service->ban('^/topic/' . $topicId . '(-|/|$)');
            }
        });
    }
}
