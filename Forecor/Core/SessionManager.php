<?php

declare(strict_types=1);

namespace Forecor\Core;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Session başlatma ve erişim. Symfony HttpFoundation Session kullanır.
 */
class SessionManager
{
    protected static ?Session $instance = null;

    public static function start(): Session
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $cfg = core_config('session', []);
        $isHttpsRequest = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if (class_exists(\App\Services\SecurityService::class)) {
            $isHttpsRequest = \App\Services\SecurityService::isHttpsRequest();
        }
        $options = [
            'name'            => $cfg['name'] ?? 'MEGAFORBB_SESSION',
            'cookie_lifetime' => (int) ($cfg['lifetime'] ?? 120) * 60,
            'cookie_path'     => $cfg['path'] ?? '/',
            'cookie_secure'   => (bool) ($cfg['secure'] ?? $isHttpsRequest),
            'cookie_httponly' => (bool) ($cfg['httponly'] ?? true),
            'cookie_samesite' => $cfg['samesite'] ?? 'Lax',
        ];
        $driver = env('SESSION_DRIVER', 'file');
        $storage = null;

        if ($driver === 'redis' && extension_loaded('redis')) {
            $redis = new \Redis();
            $host = env('REDIS_HOST', '127.0.0.1');
            $port = (int) env('REDIS_PORT', 6379);
            $timeout = 2.0; // Localhost'ta Redis yoksa uzun süre beklemesin (sunucuda Redis varsa hızlı bağlanır)
            try {
                if ($redis->connect($host, $port, $timeout)) {
                    $redisPassword = trim((string) env('REDIS_PASSWORD', ''));
                    if ($redisPassword !== '') {
                        $redis->auth($redisPassword);
                    }
                    $handler = new \Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler($redis);
                    $storage = new NativeSessionStorage($options, $handler);
                }
            } catch (\Throwable $e) {
                // Redis ulaşılamaz; file session'a düş
            }
        }

        if ($storage === null) {
            $storage = new NativeSessionStorage($options);
        }

        self::$instance = new Session($storage);
        self::$instance->start();
        return self::$instance;
    }

    public static function get(): Session
    {
        if (self::$instance === null) {
            self::start();
        }
        return self::$instance;
    }
}
