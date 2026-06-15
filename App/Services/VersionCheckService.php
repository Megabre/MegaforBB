<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Version;

/**
 * Uzak sürüm kontrolü — GitHub (veya version_check_url) üzerindeki version.json ile
 * günlük cron ile karşılaştırma. Ayrıca sürüme ait manifest indirip
 * tüm dağıtım dosyaları için bütünlük kontrolü yapar.
 *
 * @version 1.1.3
 */
class VersionCheckService
{
    private const SETTING_LATEST_REMOTE = 'latest_remote_version';
    private const SETTING_LAST_CHECK_AT = 'last_version_check_at';
    private const SETTING_REMOTE_JSON = 'version_check_remote_json';
    private const SETTING_FILE_VERIFICATION = 'version_file_verification';
    private const FILE_VERIFICATION_INTERVAL_SECONDS = 21600;
    private const ISSUE_SAMPLE_SIZE = 30;

    public function __construct(
        private readonly string $checkUrl,
        private readonly ?string $basePath = null
    )
    {
    }

    /** Cron veya admin tetiklemesi: uzak JSON'u çek, karşılaştır, ayarlara yaz. */
    public function runCheck(bool $forceFileVerification = false): array
    {
        $result = [
            'success' => false,
            'current' => Version::VERSION,
            'remote' => null,
            'upgrade_available' => false,
            'integrity_problem' => false,
            'file_verification' => self::getFileVerificationStatus(),
            'message' => '',
            'error' => null,
        ];

        $json = $this->fetchRemote();
        if ($json === null || $json === '') {
            $result['error'] = 'Uzak sürüm dosyası alınamadı.';
            $result['message'] = $result['error'];
            return $result;
        }

        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['version'])) {
            $result['error'] = 'Geçersiz version.json yapısı.';
            $result['message'] = $result['error'];
            return $result;
        }

        $remoteVersion = (string) $data['version'];
        $result['remote'] = $remoteVersion;
        $result['success'] = true;

        Setting::setValue(self::SETTING_LATEST_REMOTE, $remoteVersion, 'system');
        Setting::setValue(self::SETTING_LAST_CHECK_AT, (string) time(), 'system');
        Setting::setValue(self::SETTING_REMOTE_JSON, $json, 'system');

        $fileStatus = $this->runOrReuseFileVerification($forceFileVerification);
        $result['file_verification'] = $fileStatus;

        if (is_array($fileStatus) && in_array((string) ($fileStatus['status'] ?? ''), ['issues', 'error'], true)) {
            $result['integrity_problem'] = true;
        }

        if (version_compare($remoteVersion, Version::VERSION, '>')) {
            $result['upgrade_available'] = true;
            $result['message'] = sprintf(
                'Sistem sürümünüz (%s) güncel değil. En güncel sürüm: %s. Lütfen sürüm yükseltmesi yapın.',
                Version::VERSION,
                $remoteVersion
            );
        } elseif ($result['integrity_problem']) {
            $result['message'] = (string) (($fileStatus['message'] ?? '') !== '' ? $fileStatus['message'] : 'Sürüm numarası güncel ancak dosya bütünlüğü sorunu var.');
        } else {
            $result['message'] = 'Sürümünüz güncel.';
        }

        return $result;
    }

    /** Admin panelde uyarı göstermek için: uzak sürüm güncel mi, yükseltme gerekli mi? */
    public static function isUpgradeAvailable(): bool
    {
        $remote = Setting::getValue(self::SETTING_LATEST_REMOTE, '');
        if ($remote === '') {
            return false;
        }
        return version_compare($remote, Version::VERSION, '>');
    }

    /** Admin'de gösterilecek: en güncel uzak sürüm. */
    public static function getLatestRemoteVersion(): ?string
    {
        $v = Setting::getValue(self::SETTING_LATEST_REMOTE, '');
        return $v !== '' ? $v : null;
    }

    /** Son bütünlük kontrol sonucunu döndürür. */
    public static function getFileVerificationStatus(): ?array
    {
        $raw = Setting::getValue(self::SETTING_FILE_VERIFICATION, '');
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Uzak version.json içeriğini (son başarılı kontrolden) decode edilmiş dizi olarak döndürür.
     * Henüz kontrol yapılmadıysa veya JSON geçersizse null döner.
     *
     * @return array<string,mixed>|null
     */
    public static function getRemoteVersionPayload(): ?array
    {
        $raw = Setting::getValue(self::SETTING_REMOTE_JSON, '');
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /** Admin üst barda bütünlük uyarısı gerekip gerekmediğini döndürür. */
    public static function hasIntegrityProblems(): bool
    {
        $status = self::getFileVerificationStatus();
        if (!is_array($status)) {
            return false;
        }

        return in_array((string) ($status['status'] ?? ''), ['issues', 'error'], true);
    }

    public static function getIntegrityMessage(): ?string
    {
        $status = self::getFileVerificationStatus();
        if (!is_array($status)) {
            return null;
        }

        $message = (string) ($status['message'] ?? '');
        return $message !== '' ? $message : null;
    }

    /** Son kontrol zamanı (timestamp). */
    public static function getLastCheckAt(): ?int
    {
        $t = Setting::getValue(self::SETTING_LAST_CHECK_AT, '');
        return $t !== '' ? (int) $t : null;
    }

    private function runOrReuseFileVerification(bool $force): ?array
    {
        $existing = self::getFileVerificationStatus();
        if (!$force && !$this->shouldRunFileVerification($existing)) {
            return $existing;
        }

        $status = $this->runFileVerification();
        if (is_array($status)) {
            $encoded = json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded)) {
                Setting::setValue(
                    self::SETTING_FILE_VERIFICATION,
                    $encoded,
                    'system'
                );
            }
        }

        return $status;
    }

    private function shouldRunFileVerification(?array $existing): bool
    {
        if (!is_array($existing)) {
            return true;
        }

        $checkedAt = isset($existing['checked_at']) ? (int) $existing['checked_at'] : 0;
        if ($checkedAt <= 0) {
            return true;
        }

        if ((time() - $checkedAt) >= self::FILE_VERIFICATION_INTERVAL_SECONDS) {
            return true;
        }

        $existingVersion = (string) ($existing['version'] ?? '');
        if ($existingVersion !== Version::VERSION) {
            return true;
        }

        return false;
    }

    private function runFileVerification(): array
    {
        $fileService = new FileVerificationService($this->resolveBasePath());
        $verify = $fileService->verify(Version::VERSION, true);
        if (!($verify['success'] ?? false)) {
            $errorCode = (string) ($verify['error'] ?? '');
            if ($errorCode === 'manifest_not_found') {
                return [
                    'status' => 'skipped',
                    'checked_at' => time(),
                    'version' => Version::VERSION,
                    'message' => (string) ($verify['message'] ?? 'Bu kurulum için henüz lokal manifest oluşturulmadığı için dosya doğrulaması atlandı.'),
                    'total' => 0,
                    'ok_count' => 0,
                    'modified_count' => 0,
                    'missing_count' => 0,
                    'unexpected_count' => 0,
                    'modified_sample' => [],
                    'missing_sample' => [],
                    'unexpected_sample' => [],
                ];
            }

            return [
                'status' => 'error',
                'checked_at' => time(),
                'version' => Version::VERSION,
                'message' => (string) ($verify['message'] ?? 'Dosya doğrulaması çalıştırılamadı.'),
                'total' => 0,
                'ok_count' => 0,
                'modified_count' => 0,
                'missing_count' => 0,
                'unexpected_count' => 0,
                'modified_sample' => [],
                'missing_sample' => [],
                'unexpected_sample' => [],
            ];
        }

        $modifiedCount = (int) ($verify['modified_count'] ?? 0);
        $missingCount = (int) ($verify['missing_count'] ?? 0);
        $unexpectedCount = (int) ($verify['unexpected_count'] ?? 0);
        $hasIssues = ($modifiedCount + $missingCount + $unexpectedCount) > 0;

        return [
            'status' => $hasIssues ? 'issues' : 'ok',
            'checked_at' => time(),
            'version' => Version::VERSION,
            'total' => (int) ($verify['total'] ?? 0),
            'ok_count' => (int) ($verify['ok_count'] ?? 0),
            'modified_count' => $modifiedCount,
            'missing_count' => $missingCount,
            'unexpected_count' => $unexpectedCount,
            'modified_sample' => array_slice((array) ($verify['modified'] ?? []), 0, self::ISSUE_SAMPLE_SIZE),
            'missing_sample' => array_slice((array) ($verify['missing'] ?? []), 0, self::ISSUE_SAMPLE_SIZE),
            'unexpected_sample' => array_slice((array) ($verify['unexpected'] ?? []), 0, self::ISSUE_SAMPLE_SIZE),
            'message' => $hasIssues
                ? sprintf('Dosya bütünlüğü sorunu: %d değiştirilmiş, %d eksik, %d beklenmeyen dosya.', $modifiedCount, $missingCount, $unexpectedCount)
                : 'Dosya bütünlüğü doğrulandı: tüm dağıtım dosyaları doğru sürümde.',
        ];
    }

    private function resolveManifestMeta(array $versionPayload, string $targetVersion): ?array
    {
        $manifestList = $versionPayload['manifests'] ?? null;
        if (is_array($manifestList) && isset($manifestList[$targetVersion])) {
            $entry = $this->normalizeManifestEntry($manifestList[$targetVersion]);
            if ($entry !== null) {
                return $entry;
            }
        }

        $legacyUrl = isset($versionPayload['manifest_url']) ? trim((string) $versionPayload['manifest_url']) : '';
        $legacyVersion = isset($versionPayload['version']) ? trim((string) $versionPayload['version']) : '';
        if ($legacyUrl !== '' && ($legacyVersion === '' || $legacyVersion === $targetVersion)) {
            return [
                'url' => $legacyUrl,
                'sha256' => isset($versionPayload['manifest_sha256']) ? trim((string) $versionPayload['manifest_sha256']) : null,
            ];
        }

        return null;
    }

    private function normalizeManifestEntry(mixed $entry): ?array
    {
        if (is_string($entry)) {
            $url = trim($entry);
            if ($url === '') {
                return null;
            }
            return [
                'url' => $url,
                'sha256' => null,
            ];
        }

        if (!is_array($entry)) {
            return null;
        }

        $url = isset($entry['url']) ? trim((string) $entry['url']) : '';
        if ($url === '') {
            return null;
        }

        $sha = isset($entry['sha256']) ? trim((string) $entry['sha256']) : '';
        if ($sha === '' && isset($entry['hash'])) {
            $sha = trim((string) $entry['hash']);
        }
        $sha = $sha !== '' ? strtolower($sha) : null;

        return [
            'url' => $url,
            'sha256' => $sha,
        ];
    }

    private function resolveBasePath(): string
    {
        if (is_string($this->basePath) && trim($this->basePath) !== '') {
            return rtrim($this->basePath, "\\/");
        }

        if (defined('MEGAFORBB_BASE_PATH')) {
            return rtrim((string) MEGAFORBB_BASE_PATH, "\\/");
        }

        return dirname(__DIR__, 2);
    }

    private function fetchRemote(): ?string
    {
        $url = $this->checkUrl;
        if ($url === '') {
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
                CURLOPT_TIMEOUT => 10,
                CURLOPT_USERAGENT => 'MegaforBB-VersionCheck/1.0',
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 200 && $code < 300 && is_string($body)) {
                return $body;
            }
            return null;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'MegaforBB-VersionCheck/1.0',
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : null;
    }
}
