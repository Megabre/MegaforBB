<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Basit önbellek katmanı: dosya veya Redis.
 * Symfony/Laravel tarzı get/set/delete/has/clear; Redis yoksa veya bağlantı hatası varsa dosya kullanılır.
 * Aynı sunucuda birden fazla kurulum (farklı domain) aynı Redis kullanıyorsa keyPrefix ile anahtarlar ayrılır.
 */
class Cache
{
    private const DEFAULT_PREFIX = 'megaforbb:cache:';
    private const FILE_PREFIX = 'cache_';

    private string $driver;
    private string $filePath;
    private string $keyPrefix;
    private ?\Redis $redis = null;

    /**
     * @param string|null $keyPrefix Redis için namespace (örn. domain bazlı). Boşsa DEFAULT_PREFIX kullanılır. Aynı Redis kullanan her site için farklı olmalı.
     */
    public function __construct(string $driver = 'file', ?string $redisHost = null, ?int $redisPort = null, ?string $redisPassword = null, ?string $redisUsername = null, ?string $filePath = null, ?string $keyPrefix = null)
    {
        $this->driver = $driver === 'redis' ? 'redis' : 'file';
        $this->keyPrefix = $keyPrefix !== null && $keyPrefix !== '' ? $keyPrefix : self::DEFAULT_PREFIX;
        $basePath = dirname(__DIR__, 2);
        $this->filePath = $filePath ?? $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        if ($this->driver === 'redis' && $redisHost !== null && $redisHost !== '') {
            $this->connectRedis($redisHost, $redisPort ?? 6379, $redisPassword, $redisUsername);
            if ($this->redis === null) {
                $this->driver = 'file';
            }
        }
    }

    private function connectRedis(string $host, int $port, ?string $password, ?string $username): void
    {
        if (!extension_loaded('redis')) {
            return;
        }
        try {
            $r = new \Redis();
            if (!$r->connect($host, $port, 2.0)) {
                return;
            }
            if ($password !== null && $password !== '') {
                if ($username !== null && $username !== '') {
                    $r->auth(['user' => $username, 'pass' => $password]);
                } else {
                    $r->auth($password);
                }
            }
            $r->setOption(\Redis::OPT_PREFIX, $this->keyPrefix);
            $this->redis = $r;
        } catch (\Throwable $e) {
            $this->redis = null;
        }
    }

    /** Redis bağlantısını test et (host, port, password, username ile). Başarılı ise true. */
    public static function testRedisConnection(string $host, int $port = 6379, ?string $password = null, ?string $username = null): array
    {
        $ok = false;
        $message = '';
        if (!extension_loaded('redis')) {
            return ['ok' => false, 'message' => lang('cache.redis_ext_missing')];
        }
        try {
            $r = new \Redis();
            if (!$r->connect($host, $port, 3.0)) {
                $message = lang('cache.redis_connection_failed');
                return ['ok' => false, 'message' => $message];
            }
            if ($password !== null && $password !== '') {
                if ($username !== null && $username !== '') {
                    $r->auth(['user' => $username, 'pass' => $password]);
                } else {
                    $r->auth($password);
                }
            }
            $r->ping();
            $ok = true;
            $message = lang('cache.redis_connected');
        } catch (\Throwable $e) {
            $message = $e->getMessage();
        }
        return ['ok' => $ok, 'message' => $message];
    }

    public function get(string $key)
    {
        if ($this->driver === 'redis' && $this->redis !== null) {
            try {
                $v = $this->redis->get($key);
                return $v === false ? null : unserialize($v, ['allowed_classes' => true]);
            } catch (\Throwable $e) {
                return null;
            }
        }
        $f = $this->filePath . DIRECTORY_SEPARATOR . self::FILE_PREFIX . md5($key) . '.cache';
        if (!is_file($f)) {
            return null;
        }
        $data = @file_get_contents($f);
        if ($data === false) {
            return null;
        }
        $exp = (int) substr($data, 0, 10);
        if ($exp > 0 && $exp < time()) {
            @unlink($f);
            return null;
        }
        $payload = substr($data, 10);
        return unserialize($payload, ['allowed_classes' => true]);
    }

    public function set(string $key, $value, ?int $ttlSeconds = null): bool
    {
        $ser = serialize($value);
        if ($this->driver === 'redis' && $this->redis !== null) {
            try {
                if ($ttlSeconds !== null && $ttlSeconds > 0) {
                    return $this->redis->setex($key, $ttlSeconds, $ser);
                }
                return $this->redis->set($key, $ser);
            } catch (\Throwable $e) {
                return false;
            }
        }
        if (!is_dir($this->filePath)) {
            @mkdir($this->filePath, 0755, true);
        }
        $exp = $ttlSeconds !== null && $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        $f = $this->filePath . DIRECTORY_SEPARATOR . self::FILE_PREFIX . md5($key) . '.cache';
        return @file_put_contents($f, sprintf('%010d', $exp) . $ser, LOCK_EX) !== false;
    }

    public function delete(string $key): bool
    {
        if ($this->driver === 'redis' && $this->redis !== null) {
            try {
                $this->redis->del($key);
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }
        $f = $this->filePath . DIRECTORY_SEPARATOR . self::FILE_PREFIX . md5($key) . '.cache';
        if (is_file($f)) {
            @unlink($f);
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /** Tüm önbelleği temizle (sadece bu prefix altındaki anahtarlar). */
    public function clear(): bool
    {
        if ($this->driver === 'redis' && $this->redis !== null) {
            try {
                $keys = $this->redis->keys('*');
                if (!empty($keys)) {
                    $prefixLen = strlen($this->keyPrefix);
                    foreach ($keys as $fullKey) {
                        $shortKey = substr((string) $fullKey, $prefixLen);
                        if ($shortKey !== '') {
                            $this->redis->del($shortKey);
                        }
                    }
                }
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }
        if (!is_dir($this->filePath)) {
            return true;
        }
        $files = glob($this->filePath . DIRECTORY_SEPARATOR . self::FILE_PREFIX . '*.cache');
        foreach ($files ?: [] as $f) {
            @unlink($f);
        }
        return true;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}
