<?php

declare(strict_types=1);

namespace App\Services;

use App\Version;

/**
 * Dosya doğrulama: release manifest (yol => SHA-256) üretir,
 * GitHub'dan senkronize eder ve yerel dosyaları bu manifest ile karşılaştırır.
 *
 * @version 1.1.3
 */
class FileVerificationService
{
    private const MANIFEST_DIR = 'verification';
    private const MANIFEST_PREFIX = 'manifest-';
    private const MANIFEST_EXT = '.json';
    private const HASH_ALGO = 'sha256';

    /**
     * Kapsam: varsayılan olarak sadece MegaforBB çekirdek kod ve şablonları.
     * Vendor, runtime ve upload dizinleri burada yer almaz.
     */
    private const DEFAULT_INCLUDE_ROOTS = [
        'App',
        'Forecor',
        'Inc',
        'Route',
        'public/index.php',
        'public/cron.php',
    ];

    /** Bu pattern'lere uyan dosyalar doğrulama dışıdır (runtime veya yerel dosyalar). */
    private const EXCLUDE_PATTERNS = [
        // Uygulama runtime/veri dizinleri
        '/^Content\/storage\//',
        '/^Content\/uploads\//',
        '/^Content\/logs\//',
        '/^Content\/sessions\//',
        '/^public\/uploads\//',

        // Vendor / bağımlılıklar (tamamı kapsam dışı)
        '/^Library\/vendor\//',
        '/^Library\/composer\.lock$/',
        '/^Library\/vendor\/bin\//',

        // VCS / IDE
        '/^\.git(?:\/|$)/',
        '/^node_modules(?:\/|$)/',
        '/^\.idea(?:\/|$)/',
        '/^\.vscode(?:\/|$)/',

        // Ortam ve meta dosyaları
        '/^\.env(?:\.|$)/',
        '/^version\.json$/',
        '/^manifest-[A-Za-z0-9._-]+\.json$/',

        // Dinamik assetler ve özel durumlar
        '/^public\/smileys\/lost_\d+\.(?:gif|png|jpe?g|webp|svg)$/i',

        // Log / cache / dokümantasyon dosyaları
        '/\.log$/i',
        '/\.cache$/i',
        '/\.md$/i',
        '/^Thumbs\.db$/i',
    ];

    public function __construct(private readonly string $basePath)
    {
    }

    /** Mevcut sürüm için release manifest üretir ve local verification dizinine kaydeder. */
    public function generateManifest(string $version = null, ?array $includeRoots = null): array
    {
        $version = $version ?? Version::VERSION;
        $includeRoots = $this->normalizeIncludeRoots($includeRoots ?? self::DEFAULT_INCLUDE_ROOTS);
        $files = $this->collectFiles($includeRoots);

        $manifestFiles = [];
        foreach ($files as $relativePath) {
            $full = $this->toFullPath($relativePath);
            if (!is_file($full)) {
                continue;
            }
            $hash = hash_file(self::HASH_ALGO, $full);
            if (!is_string($hash) || $hash === '') {
                continue;
            }
            $size = @filesize($full);
            $manifestFiles[$relativePath] = [
                self::HASH_ALGO => $hash,
                'size' => is_int($size) ? $size : 0,
            ];
        }
        ksort($manifestFiles);

        $manifest = [
            'version' => $version,
            'generated_at' => date('c'),
            'hash_algo' => self::HASH_ALGO,
            'scope' => [
                'include_roots' => $includeRoots,
                'exclude_patterns' => self::EXCLUDE_PATTERNS,
            ],
            'file_count' => count($manifestFiles),
            'files' => $manifestFiles,
        ];

        $dir = $this->manifestStorageDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $path = $dir . DIRECTORY_SEPARATOR . self::MANIFEST_PREFIX . $version . self::MANIFEST_EXT;
        $written = @file_put_contents($path, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return [
            'success' => $written !== false,
            'version' => $version,
            'file_count' => count($manifestFiles),
            'path' => $path,
            'manifest' => $manifest,
        ];
    }

    /** Uzak manifest dosyasını indirir, doğrular ve local verification dizinine kaydeder. */
    public function syncManifestFromUrl(string $url, ?string $expectedVersion = null, ?string $expectedSha256 = null): array
    {
        $json = $this->fetchRemote($url);
        if ($json === null || $json === '') {
            return [
                'success' => false,
                'error' => 'remote_manifest_fetch_failed',
                'message' => 'GitHub manifest dosyası alınamadı.',
            ];
        }

        if (is_string($expectedSha256) && trim($expectedSha256) !== '') {
            $actual = strtolower(hash(self::HASH_ALGO, $json));
            if ($actual !== strtolower(trim($expectedSha256))) {
                return [
                    'success' => false,
                    'error' => 'remote_manifest_checksum_mismatch',
                    'message' => 'GitHub manifest SHA-256 doğrulaması başarısız.',
                    'expected_sha256' => strtolower(trim($expectedSha256)),
                    'actual_sha256' => $actual,
                ];
            }
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'error' => 'remote_manifest_invalid_json',
                'message' => 'GitHub manifest JSON formatı geçersiz.',
            ];
        }

        $manifest = $this->normalizeManifest($decoded);
        if ($manifest === null) {
            return [
                'success' => false,
                'error' => 'remote_manifest_invalid_payload',
                'message' => 'GitHub manifest içeriği doğrulama için uygun değil.',
            ];
        }

        if (is_string($expectedVersion) && $expectedVersion !== '' && $manifest['version'] !== $expectedVersion) {
            return [
                'success' => false,
                'error' => 'remote_manifest_version_mismatch',
                'message' => sprintf('Manifest sürümü beklenen sürümle eşleşmiyor (beklenen: %s, gelen: %s).', $expectedVersion, $manifest['version']),
                'expected_version' => $expectedVersion,
                'actual_version' => $manifest['version'],
            ];
        }

        $path = $this->getManifestPath($manifest['version']);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $written = @file_put_contents($path, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        if ($written === false) {
            return [
                'success' => false,
                'error' => 'remote_manifest_write_failed',
                'message' => 'Manifest yerel diske kaydedilemedi.',
            ];
        }

        return [
            'success' => true,
            'version' => $manifest['version'],
            'path' => $path,
            'file_count' => count($manifest['files']),
            'manifest' => $manifest,
        ];
    }

    /** Belirtilen sürümün manifest dosya yolunu döndürür. */
    public function getManifestPath(string $version): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . self::MANIFEST_DIR . DIRECTORY_SEPARATOR . self::MANIFEST_PREFIX . $version . self::MANIFEST_EXT;
    }

    /** Local manifest dosyasını yükler. */
    public function loadManifest(string $version): ?array
    {
        $path = $this->getManifestPath($version);
        if (is_file($path) && is_readable($path)) {
            $json = file_get_contents($path);
            $data = json_decode($json, true);
            if (!is_array($data)) {
                return null;
            }
            return $this->normalizeManifest($data);
        }
        return null;
    }

    /** Yerel dosyaları manifest ile doğrular. Sonuç: ok, modified, missing, unexpected listeleri. */
    public function verify(string $version = null, bool $includeUnexpected = true): array
    {
        $version = $version ?? Version::VERSION;
        $manifest = $this->loadManifest($version);
        if ($manifest === null) {
            return [
                'success' => false,
                'error' => 'manifest_not_found',
                'message' => "Bu sürüm için lokal manifest bulunamadı (sürüm: {$version}). Önce bu kurulumda manifest oluşturun (CLI veya admin üzerinden).",
                'version' => $version,
                'total' => 0,
                'ok' => [],
                'modified' => [],
                'missing' => [],
                'unexpected' => [],
            ];
        }

        return $this->verifyWithManifest($manifest, $version, $includeUnexpected);
    }

    /** Verilen manifest verisi ile doğrulama yapar. */
    public function verifyWithManifest(array $manifest, ?string $version = null, bool $includeUnexpected = true): array
    {
        $normalizedManifest = $this->normalizeManifest($manifest);
        if ($normalizedManifest === null) {
            return [
                'success' => false,
                'error' => 'manifest_invalid',
                'message' => 'Manifest içeriği doğrulama için geçersiz.',
                'version' => $version ?? Version::VERSION,
                'total' => 0,
                'ok' => [],
                'modified' => [],
                'missing' => [],
                'unexpected' => [],
            ];
        }

        $version = $version ?? (string) ($normalizedManifest['version'] ?? Version::VERSION);
        $files = $normalizedManifest['files'];
        $ok = [];
        $modified = [];
        $missing = [];

        foreach ($files as $relativePath => $expectedHash) {
            $full = $this->toFullPath($relativePath);
            if (!is_file($full)) {
                $missing[] = $relativePath;
                continue;
            }

            $actualHash = hash_file(self::HASH_ALGO, $full);
            if ($actualHash !== $expectedHash) {
                $modified[] = $relativePath;
            } else {
                $ok[] = $relativePath;
            }
        }

        $unexpected = [];
        if ($includeUnexpected) {
            $scanRoots = $this->extractIncludeRoots($normalizedManifest);
            if ($scanRoots !== []) {
                $localFiles = $this->collectFiles($scanRoots);
                foreach ($localFiles as $relativePath) {
                    if (!isset($files[$relativePath])) {
                        $unexpected[] = $relativePath;
                    }
                }
            }
        }

        sort($ok);
        sort($modified);
        sort($missing);
        sort($unexpected);

        $modifiedCount = count($modified);
        $missingCount = count($missing);
        $unexpectedCount = count($unexpected);

        return [
            'success' => true,
            'version' => $version,
            'total' => count($files),
            'ok_count' => count($ok),
            'modified_count' => $modifiedCount,
            'missing_count' => $missingCount,
            'unexpected_count' => $unexpectedCount,
            'has_issues' => ($modifiedCount + $missingCount + $unexpectedCount) > 0,
            'ok' => $ok,
            'modified' => $modified,
            'missing' => $missing,
            'unexpected' => $unexpected,
        ];
    }

    private function manifestStorageDir(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . self::MANIFEST_DIR;
    }

    private function collectFiles(array $includeRoots): array
    {
        $out = [];
        $base = rtrim($this->basePath, DIRECTORY_SEPARATOR);

        foreach ($includeRoots as $root) {
            $root = $this->normalizePath($root);
            $fullRoot = $root === '.' ? $base : $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $root);

            if (is_file($fullRoot)) {
                $relative = $root;
                if (!$this->shouldExclude($relative)) {
                    $out[] = $relative;
                }
                continue;
            }

            if (!is_dir($fullRoot)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fullRoot, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();
                $relative = str_replace($base . DIRECTORY_SEPARATOR, '', $path);
                $relative = str_replace('\\', '/', $relative);
                $relative = $this->normalizePath($relative);

                if ($this->shouldExclude($relative)) {
                    continue;
                }

                $out[] = $relative;
            }
        }

        sort($out);
        return array_values(array_unique($out));
    }

    private function extractIncludeRoots(array $manifest): array
    {
        $scope = $manifest['scope'] ?? null;
        if (!is_array($scope)) {
            return [];
        }

        $roots = $scope['include_roots'] ?? null;
        if (!is_array($roots)) {
            return [];
        }

        return $this->normalizeIncludeRoots($roots);
    }

    private function normalizeIncludeRoots(array $roots): array
    {
        $normalized = [];
        foreach ($roots as $root) {
            if (!is_string($root)) {
                continue;
            }
            $clean = trim(str_replace('\\', '/', $root));
            if ($clean === '' || $clean === '/') {
                $clean = '.';
            }
            $clean = trim($clean, '/');
            $clean = $clean === '' ? '.' : $clean;
            $normalized[$clean] = true;
        }

        if ($normalized === []) {
            return ['.'];
        }

        $roots = array_keys($normalized);
        sort($roots);
        if (in_array('.', $roots, true)) {
            return ['.'];
        }
        return $roots;
    }

    private function normalizeManifest(array $manifest): ?array
    {
        $version = isset($manifest['version']) ? trim((string) $manifest['version']) : '';
        if ($version === '' || !isset($manifest['files']) || !is_array($manifest['files'])) {
            return null;
        }

        $files = [];
        foreach ($manifest['files'] as $path => $entry) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $relativePath = $this->normalizePath($path);
            if ($relativePath === '' || $this->shouldExclude($relativePath)) {
                continue;
            }

            $hash = null;
            if (is_string($entry)) {
                $hash = strtolower(trim($entry));
            } elseif (is_array($entry)) {
                $rawHash = $entry[self::HASH_ALGO] ?? ($entry['hash'] ?? null);
                if (is_string($rawHash)) {
                    $hash = strtolower(trim($rawHash));
                }
            }

            if (!is_string($hash) || !preg_match('/^[a-f0-9]{64}$/', $hash)) {
                continue;
            }

            $files[$relativePath] = $hash;
        }

        if ($files === []) {
            return null;
        }

        ksort($files);

        $scope = $manifest['scope'] ?? null;
        if (!is_array($scope)) {
            $scope = [];
        }

        return [
            'version' => $version,
            'generated_at' => isset($manifest['generated_at']) ? (string) $manifest['generated_at'] : '',
            'hash_algo' => self::HASH_ALGO,
            'scope' => $scope,
            'files' => $files,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        $path = preg_replace('#^\./#', '', $path) ?? $path;
        $path = ltrim($path, '/');
        return trim($path, '/');
    }

    private function toFullPath(string $relativePath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function shouldExclude(string $relativePath): bool
    {
        foreach (self::EXCLUDE_PATTERNS as $pattern) {
            if (preg_match($pattern, $relativePath)) {
                return true;
            }
        }
        return false;
    }

    private function fetchRemote(string $url): ?string
    {
        $url = trim($url);
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
                CURLOPT_TIMEOUT => 12,
                CURLOPT_USERAGENT => 'MegaforBB-FileVerification/1.0',
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
                'timeout' => 12,
                'user_agent' => 'MegaforBB-FileVerification/1.0',
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : null;
    }
}
