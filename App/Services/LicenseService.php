<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

/**
 * Lisans yönetim servisi.
 *
 * Yapılandırma .env üzerinden okunur:
 *   LM_API_BASE, LM_PRODUCT_ID, LM_LICENSE_KEY, LM_SITE_DOMAIN
 *
 * Token  → Content/storage/license/.lm_token
 * Cache  → Content/storage/license/.lm_verify_cache
 */
final class LicenseService
{
    private const SETTING_STATUS      = 'license_status';
    private const SETTING_ACTIVATED   = 'license_activated_at';
    private const SETTING_DOMAIN      = 'license_domain';
    private const SETTING_KEY         = 'license_key_masked';

    private readonly string $apiBase;
    private readonly string $productId;
    private readonly string $licenseKey;
    private readonly string $domain;

    private readonly string $tokenPath;
    private readonly string $cachePath;

    public function __construct()
    {
        $this->apiBase    = rtrim((string) env('LM_API_BASE', ''), '/');
        $this->productId  = (string) env('LM_PRODUCT_ID', '');
        $this->licenseKey = (string) env('LM_LICENSE_KEY', '');
        $this->domain     = rtrim((string) env('LM_SITE_DOMAIN', ''), '/');

        $storageDir = $this->resolveStorageDir();
        $hash = md5($this->licenseKey . $this->domain);

        $this->tokenPath = $storageDir . '/.lm_token_' . $hash;
        $this->cachePath = $storageDir . '/.lm_verify_' . $hash;
    }

    // ──────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────

    /** Lisansı aktifleştirir ve token'ı kaydeder. */
    public function activate(): array
    {
        if ($this->licenseKey === '' || $this->productId === '' || $this->apiBase === '') {
            return ['success' => false, 'message' => 'LM_LICENSE_KEY, LM_PRODUCT_ID veya LM_API_BASE .env dosyasında tanımlı değil.'];
        }

        $res = $this->post('activate', [
            'product_id'  => $this->productId,
            'license_key' => $this->licenseKey,
            'domain'      => $this->domain,
        ]);

        if (($res['status'] ?? '') === 'success' && ! empty($res['data']['token'])) {
            $this->writeToken((string) $res['data']['token']);
            $this->touchCache();
            $this->persistStatus('active');

            return ['success' => true, 'message' => 'Lisans başarıyla aktifleştirildi.'];
        }

        $errMsg = (string) ($res['message'] ?? ($res['error'] ?? 'Aktivasyon başarısız.'));

        return ['success' => false, 'message' => $errMsg];
    }

    /**
     * Lisansı doğrular.
     *
     * @param bool $forceRemote true → cache atla, API'ye her seferinde sor
     */
    public function verify(bool $forceRemote = false): bool
    {
        if ($this->licenseKey === '' || $this->productId === '' || $this->apiBase === '') {
            return false;
        }

        // 24 saatlik yerel cache (cron/her istek üretim yükünü azaltır)
        if (! $forceRemote && $this->isCacheValid()) {
            return true;
        }

        $token = $this->readToken();
        if ($token === '') {
            return false;
        }

        $res = $this->post('verify', [
            'product_id'  => $this->productId,
            'license_key' => $this->licenseKey,
            'domain'      => $this->domain,
            'token'       => $token,
        ]);

        if (($res['status'] ?? '') === 'success') {
            $this->touchCache();
            $this->persistStatus('active');

            return true;
        }

        // Sunucu geçersiz dedi; yerel durumu güncelle ama token'ı silme
        $this->persistStatus('invalid');

        return false;
    }

    /** Lisansı deaktif eder; token ve cache temizlenir. */
    public function deactivate(): array
    {
        if ($this->licenseKey !== '' && $this->productId !== '' && $this->apiBase !== '') {
            $this->post('deactivate', [
                'product_id'  => $this->productId,
                'license_key' => $this->licenseKey,
                'domain'      => $this->domain,
            ]);
        }

        $this->removeToken();
        $this->removeCache();
        $this->persistStatus('inactive');

        return ['success' => true, 'message' => 'Lisans deaktif edildi.'];
    }

    /** Admin paneli için durum özeti. */
    public function getStatus(): array
    {
        $dbStatus   = Setting::getValue(self::SETTING_STATUS, 'unknown');
        $hasToken   = is_file($this->tokenPath) && filesize($this->tokenPath) > 0;
        $cacheValid = $this->isCacheValid();

        return [
            'status'          => $dbStatus,           // active | inactive | invalid | unknown
            'has_token'       => $hasToken,
            'cache_valid'     => $cacheValid,
            'cache_age_hours' => $this->cacheAgeHours(),
            'domain'          => $this->domain,
            'product_id'      => $this->productId,
            'key_masked'      => $this->maskKey($this->licenseKey),
            'activated_at'    => Setting::getValue(self::SETTING_ACTIVATED, ''),
            'api_base'        => $this->apiBase,
        ];
    }

    // ──────────────────────────────────────────────
    // Static helpers (bootstrap & bootstrap-level use)
    // ──────────────────────────────────────────────

    /**
     * Bootstrap'ta çağrılır. Cache geçerliyse DB hit yok.
     * Yerel token yoksa activate() dener; aktivasyon da başarısızsa false döner.
     */
    public static function bootCheck(): bool
    {
        $service = new self();

        if ($service->verify()) {
            return true;
        }

        // Token hiç yoksa ilk kurulum: otomatik aktifleştir
        if (! is_file($service->tokenPath)) {
            $result = $service->activate();
            return $result['success'];
        }

        return false;
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function post(string $endpoint, array $data): array
    {
        $url = $this->apiBase . '/' . ltrim($endpoint, '/');
        $ch  = curl_init($url);

        if ($ch === false) {
            return [];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        return $decoded;
    }

    private function isCacheValid(): bool
    {
        return is_file($this->cachePath) && (time() - (int) filemtime($this->cachePath)) < 86400;
    }

    private function cacheAgeHours(): float
    {
        if (! is_file($this->cachePath)) {
            return -1.0;
        }
        return round((time() - (int) filemtime($this->cachePath)) / 3600, 1);
    }

    private function touchCache(): void
    {
        $dir = dirname($this->cachePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        touch($this->cachePath);
    }

    private function removeCache(): void
    {
        if (is_file($this->cachePath)) {
            @unlink($this->cachePath);
        }
    }

    private function writeToken(string $token): void
    {
        $dir = dirname($this->tokenPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->tokenPath, $token, LOCK_EX);
    }

    private function readToken(): string
    {
        if (! is_file($this->tokenPath)) {
            return '';
        }
        return trim((string) file_get_contents($this->tokenPath));
    }

    private function removeToken(): void
    {
        if (is_file($this->tokenPath)) {
            @unlink($this->tokenPath);
        }
    }

    private function persistStatus(string $status): void
    {
        try {
            Setting::setValue(self::SETTING_STATUS, $status, 'system');
            if ($status === 'active') {
                Setting::setValue(self::SETTING_ACTIVATED, date('Y-m-d H:i:s'), 'system');
                Setting::setValue(self::SETTING_DOMAIN, $this->domain, 'system');
                Setting::setValue(self::SETTING_KEY, $this->maskKey($this->licenseKey), 'system');
            }
        } catch (\Throwable) {
            // DB henüz hazır olmayabilir (migration öncesi); sessizce geç
        }
    }

    private function maskKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }
        return substr($key, 0, 4) . str_repeat('*', max(0, strlen($key) - 8)) . substr($key, -4);
    }

    private function resolveStorageDir(): string
    {
        if (defined('MEGAFORBB_BASE_PATH')) {
            $dir = rtrim((string) MEGAFORBB_BASE_PATH, '\\/') . '/Content/storage/license';
        } else {
            $dir = dirname(__DIR__, 2) . '/Content/storage/license';
        }

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}
