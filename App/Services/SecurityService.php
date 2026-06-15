<?php

declare(strict_types=1);

namespace App\Services;

use Forecor\Core\Application;

/**
 * Merkezi güvenlik: global rate limit (DDoS/brute-force), aksiyon bazlı cooldown (anti-bump),
 * ihlal sayacı, tekrarlayan saldırgan için uzatılmış engel ve güvenlik olay günlüğü.
 * Kurumsal müşteriler için XenForo/Woltlab/MyBB alternatifi güvenlik katmanları.
 */
class SecurityService
{
    private const CACHE_PREFIX = 'security:';
    private const BLOCK_TTL = 86400 * 7; // 7 gün max block key retention
    private const VIOLATIONS_TTL = 3600;  // 1 saat
    private const LAST_ACTION_TTL = 3600;
    private const GLOBAL_RATE_WINDOW = 60; // saniye

    /** Aksiyon adları (settings key: security_cooldown_{action}) */
    public const ACTION_REPLY = 'reply';
    public const ACTION_NEW_TOPIC = 'new_topic';
    public const ACTION_EDIT_POST = 'edit_post';
    public const ACTION_EDIT_TOPIC = 'edit_topic';
    public const ACTION_LOGIN = 'login';
    public const ACTION_REGISTER = 'register';
    public const ACTION_SEND_PM = 'send_pm';
    public const ACTION_REPORT = 'report';
    public const ACTION_LIKE = 'like';

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    private function cache(): Cache
    {
        return $this->app->cache();
    }

    private function getSetting(string $key, string $default = ''): string
    {
        return (string) $this->app->getSetting($key, $default);
    }

    private function clientKey(?int $userId, string $ip): string
    {
        if ($userId !== null && $userId > 0) {
            return 'u' . $userId;
        }
        return 'i' . md5($ip);
    }

    private function ipHash(string $ip): string
    {
        return md5($ip);
    }

    /** Geçici engelli mi? (kullanıcı veya IP) */
    public function isBlocked(?int $userId, string $ip): ?array
    {
        $cache = $this->cache();
        $now = time();
        if ($userId !== null && $userId > 0) {
            $key = self::CACHE_PREFIX . 'block_user:' . $userId;
            $blockUntil = $cache->get($key);
            if (is_numeric($blockUntil) && (int) $blockUntil > $now) {
                $msg = $this->getSetting('security_block_message', '');
                if ($msg === '') {
                    $msg = lang('security.block_message_default');
                }
                return [
                    'reason' => 'blocked',
                    'message' => $msg,
                    'block_until' => (int) $blockUntil,
                    'wait_seconds' => (int) $blockUntil - $now,
                ];
            }
        }
        $ipKey = self::CACHE_PREFIX . 'block_ip:' . $this->ipHash($ip);
        $blockUntil = $cache->get($ipKey);
        if (is_numeric($blockUntil) && (int) $blockUntil > $now) {
            $msg = $this->getSetting('security_block_message', '');
            if ($msg === '') {
                $msg = lang('security.block_message_default');
            }
            return [
                'reason' => 'blocked',
                'message' => $msg,
                'block_until' => (int) $blockUntil,
                'wait_seconds' => (int) $blockUntil - $now,
            ];
        }
        return null;
    }

    /**
     * Check if action is allowed (not blocked, cooldown passed).
     * Returns: ['allowed' => true] or ['allowed' => false, 'reason' => 'blocked'|'cooldown', 'message' => ..., 'wait_seconds' => ...]
     */
    public function checkAction(string $action, ?int $userId, string $ip): array
    {
        $block = $this->isBlocked($userId, $ip);
        if ($block !== null) {
            return [
                'allowed' => false,
                'reason' => 'blocked',
                'message' => $block['message'],
                'block_until' => $block['block_until'],
                'wait_seconds' => $block['wait_seconds'],
            ];
        }

        if ($this->getSetting('security_enabled', '1') !== '1') {
            return ['allowed' => true];
        }

        $cooldownKey = 'security_cooldown_' . $action;
        $cooldown = (int) $this->getSetting($cooldownKey, '0');
        if ($cooldown <= 0) {
            return ['allowed' => true];
        }

        $clientId = $this->clientKey($userId, $ip);
        $lastKey = self::CACHE_PREFIX . 'last:' . $action . ':' . $clientId;
        $lastTime = $this->cache()->get($lastKey);
        if ($lastTime !== null && is_numeric($lastTime)) {
            $waitUntil = (int) $lastTime + $cooldown;
            if (time() < $waitUntil) {
                $wait = $waitUntil - time();
                return [
                    'allowed' => false,
                    'reason' => 'cooldown',
                    'message' => lang('security.cooldown_wait', ['seconds' => $wait]),
                    'wait_seconds' => $wait,
                ];
            }
        }

        return ['allowed' => true];
    }

    /** Record successful action (for cooldown). */
    public function recordAction(string $action, ?int $userId, string $ip): void
    {
        $clientId = $this->clientKey($userId, $ip);
        $lastKey = self::CACHE_PREFIX . 'last:' . $action . ':' . $clientId;
        $this->cache()->set($lastKey, (string) time(), self::LAST_ACTION_TTL);
    }

    /** İhlal kaydet; eşik aşılırsa geçici engel uygula. */
    public function recordViolation(?int $userId, string $ip): void
    {
        $cache = $this->cache();
        $clientId = $this->clientKey($userId, $ip);
        $violationsKey = self::CACHE_PREFIX . 'violations:' . $clientId;
        $windowMinutes = (int) $this->getSetting('security_violation_window_minutes', '5');
        $maxViolations = (int) $this->getSetting('security_violations_before_block', '5');
        $blockMinutes = (int) $this->getSetting('security_block_duration_minutes', '15');

        $data = $cache->get($violationsKey);
        $timestamps = is_array($data) ? $data : [];
        $now = time();
        $cutoff = $now - ($windowMinutes * 60);
        $timestamps = array_values(array_filter($timestamps, function ($t) use ($cutoff) {
            return (int) $t > $cutoff;
        }));
        $timestamps[] = $now;
        $cache->set($violationsKey, $timestamps, self::VIOLATIONS_TTL);

        if (count($timestamps) >= $maxViolations && $maxViolations > 0) {
            // Tekrarlayan saldırgan: son 24 saatte birden fazla engellenmişse süreyi uzat
            $blockMinutes = $this->getSuspiciousBlockMinutes($ip, $blockMinutes);
            $blockUntil = $now + ($blockMinutes * 60);
            if ($userId !== null && $userId > 0) {
                $cache->set(self::CACHE_PREFIX . 'block_user:' . $userId, (string) $blockUntil, self::BLOCK_TTL);
            }
            $cache->set(self::CACHE_PREFIX . 'block_ip:' . $this->ipHash($ip), (string) $blockUntil, self::BLOCK_TTL);
            SecurityLogger::log('block_applied', [
                'ip' => $ip,
                'user_id' => $userId,
                'violations' => count($timestamps),
                'block_minutes' => $blockMinutes,
            ]);
        }
    }

    /** Tekrarlayan saldırgan: aynı IP son 24 saatte N kez engellendiyse süreyi uzat. */
    private function getSuspiciousBlockMinutes(string $ip, int $defaultMinutes): int
    {
        $threshold = (int) $this->getSetting('security_suspicious_blocks_threshold', '3');
        $extendedMinutes = (int) $this->getSetting('security_suspicious_block_minutes', '1440'); // 24h
        if ($threshold <= 0 || $extendedMinutes <= $defaultMinutes) {
            return $defaultMinutes;
        }
        $cache = $this->cache();
        $key = self::CACHE_PREFIX . 'block_count_24h:' . $this->ipHash($ip);
        $blockTimestamps = $cache->get($key);
        $blockTimestamps = is_array($blockTimestamps) ? $blockTimestamps : [];
        $cutoff = time() - 86400; // 24 saat
        $blockTimestamps = array_values(array_filter($blockTimestamps, fn ($t) => (int) $t > $cutoff));
        $blockTimestamps[] = time();
        $cache->set($key, $blockTimestamps, 86400 * 2);
        return count($blockTimestamps) >= $threshold ? $extendedMinutes : $defaultMinutes;
    }

    /** İzin kontrolü + ihlal varsa ihlal kaydet ve engel mesajı döndür. Başarılıysa recordAction çağrılmalı. */
    public function checkAndRecordViolationOnFail(string $action, ?int $userId, string $ip): array
    {
        $result = $this->checkAction($action, $userId, $ip);
        if (!$result['allowed']) {
            $this->recordViolation($userId, $ip);
        }
        return $result;
    }

    /**
     * Global rate limit (IP başına dakikada istek sayısı). DDoS / brute-force ilk savunma katmanı.
     * Controller'lara girmeden önce Application::run() içinde çağrılmalı.
     * Döner: ['allowed' => true] veya ['allowed' => false, 'retry_after' => saniye]
     */
    public function checkGlobalRateLimit(string $ip): array
    {
        if ($this->getSetting('security_global_rate_enabled', '0') !== '1') {
            return ['allowed' => true];
        }
        $maxPerMinute = (int) $this->getSetting('security_global_rate_per_minute', '120');
        if ($maxPerMinute <= 0) {
            return ['allowed' => true];
        }
        $cache = $this->cache();
        $key = self::CACHE_PREFIX . 'rate:' . $this->ipHash($ip);
        $data = $cache->get($key);
        $windowStart = $data['window_start'] ?? time();
        $count = isset($data['count']) ? (int) $data['count'] : 0;
        $now = time();
        if ($now - $windowStart >= self::GLOBAL_RATE_WINDOW) {
            $windowStart = $now;
            $count = 0;
        }
        $count++;
        $cache->set($key, ['window_start' => $windowStart, 'count' => $count], 120);

        if ($count > $maxPerMinute) {
            $blockMinutes = (int) $this->getSetting('security_global_rate_block_minutes', '5');
            $blockUntil = $now + ($blockMinutes * 60);
            $cache->set(self::CACHE_PREFIX . 'block_ip:' . $this->ipHash($ip), (string) $blockUntil, self::BLOCK_TTL);
            SecurityLogger::log('rate_limit_exceeded', ['ip' => $ip, 'count' => $count, 'limit' => $maxPerMinute, 'block_minutes' => $blockMinutes]);
            return ['allowed' => false, 'retry_after' => $blockMinutes * 60];
        }
        return ['allowed' => true];
    }

    /**
     * API rate limit (IP başına dakikada istek). /api/* route'ları için Application::run() içinde çağrılır.
     * Döner: ['allowed' => true] veya ['allowed' => false, 'retry_after' => saniye]
     */
    public function checkApiRateLimit(string $ip): array
    {
        $maxPerMinute = (int) $this->getSetting('api_rate_limit_per_minute', '60');
        if ($maxPerMinute <= 0) {
            $maxPerMinute = 60;
        }
        $cache = $this->cache();
        $key = self::CACHE_PREFIX . 'api_rate:' . $this->ipHash($ip);
        $data = $cache->get($key);
        $windowStart = $data['window_start'] ?? time();
        $count = isset($data['count']) ? (int) $data['count'] : 0;
        $now = time();
        if ($now - $windowStart >= self::GLOBAL_RATE_WINDOW) {
            $windowStart = $now;
            $count = 0;
        }
        $count++;
        $cache->set($key, ['window_start' => $windowStart, 'count' => $count], 120);

        if ($count > $maxPerMinute) {
            if (class_exists(SecurityLogger::class)) {
                SecurityLogger::log('rate_limit_exceeded', ['ip' => $ip, 'count' => $count, 'limit' => $maxPerMinute, 'scope' => 'api']);
            }
            return ['allowed' => false, 'retry_after' => 60];
        }
        return ['allowed' => true];
    }

    /** Güvenlik kontrolü (captcha) geçen IP'ler whitelist'te mi? Rate limit/engelden muaf. */
    public function hasPassedSecurityCheck(string $ip): bool
    {
        $key = self::CACHE_PREFIX . 'captcha_passed:' . $this->ipHash($ip);
        return $this->cache()->get($key) === '1';
    }

    /** Captcha geçildi; bu IP'yi whitelist'e al (süre: 24 saat). */
    public function recordSecurityCheckPassed(string $ip): void
    {
        $key = self::CACHE_PREFIX . 'captcha_passed:' . $this->ipHash($ip);
        $this->cache()->set($key, '1', 86400);
    }

    /** @return array<int, string> */
    private static function trustedProxyEntries(): array
    {
        $raw = trim((string) env('TRUSTED_PROXIES', ''));
        if ($raw === '') {
            $raw = trim((string) env('TRUSTED_PROXY_IPS', ''));
        }
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts) || $parts === []) {
            return [];
        }

        return array_values(array_unique(array_map('trim', $parts)));
    }

    private static function normalizeIp(string $ip): ?string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return null;
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private static function ipMatchesCidr(string $ip, string $cidr): bool
    {
        $parts = explode('/', $cidr, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$subnet, $prefixRaw] = $parts;
        if ($subnet === '' || $prefixRaw === '' || !ctype_digit($prefixRaw)) {
            return false;
        }

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $prefix = (int) $prefixRaw;
        $maxBits = strlen($ipBin) * 8;
        if ($prefix < 0 || $prefix > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $partialBits = $prefix % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        if ($partialBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $partialBits)) & 0xFF;
        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }

    private static function isTrustedProxyIp(string $ip, array $trustedEntries): bool
    {
        foreach ($trustedEntries as $entry) {
            if ($entry === '*') {
                return true;
            }

            if (str_contains($entry, '/')) {
                if (self::ipMatchesCidr($ip, $entry)) {
                    return true;
                }
                continue;
            }

            $normalized = self::normalizeIp($entry);
            if ($normalized !== null && $normalized === $ip) {
                return true;
            }
        }

        return false;
    }

    public static function isTrustedProxyRequest(): bool
    {
        $remoteIp = self::normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remoteIp === null) {
            return false;
        }

        $trustedEntries = self::trustedProxyEntries();
        if ($trustedEntries === []) {
            return false;
        }

        return self::isTrustedProxyIp($remoteIp, $trustedEntries);
    }

    public static function isHttpsRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (!self::isTrustedProxyRequest()) {
            return false;
        }

        $xfp = trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($xfp === '') {
            return false;
        }

        $parts = explode(',', $xfp);
        $proto = strtolower(trim((string) ($parts[0] ?? '')));
        return $proto === 'https';
    }

    private static function firstForwardedIp(string $forwarded): ?string
    {
        if ($forwarded === '') {
            return null;
        }

        $parts = explode(',', $forwarded);
        foreach ($parts as $part) {
            $candidate = self::normalizeIp($part);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * İstek yapan gerçek istemci IP'si. Proxy arkasındayken önce CF-Connecting-IP (Cloudflare),
     * sonra X-Forwarded-For ilk adresi kullanılır; böylece aynı ziyaretçi tek IP ile sayılır.
     */
    public static function clientIp(): string
    {
        $remoteIp = self::normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if (!self::isTrustedProxyRequest()) {
            return $remoteIp ?? '0.0.0.0';
        }

        $cfIp = self::normalizeIp((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cfIp !== null) {
            return $cfIp;
        }

        $forwarded = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        $forwardedIp = self::firstForwardedIp($forwarded);
        if ($forwardedIp !== null) {
            return $forwardedIp;
        }

        $realIp = self::normalizeIp((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
        if ($realIp !== null) {
            return $realIp;
        }

        return $remoteIp ?? '0.0.0.0';
    }
}
