<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use League\Flysystem\Filesystem;

/**
 * Birleşik depolama: yerel disk, AWS S3 veya Cloudflare R2.
 * Admin panelden storage_driver (local | aws_s3 | r2) ve ilgili sağlayıcı ayarları ile yönetilir.
 * S3/R2 seçiliyse yüklemeler buluta gider (yerel dizin kullanılmaz).
 * Yerel seçiliyse storage_local_path ile yükleme kök dizini admin’den seçilebilir (varsayılan: uploads).
 */
class StorageService
{
    private string $basePath;

    private ?Filesystem $cloudFilesystem = null;

    /** URL’de ve veritabanında path hep "uploads/..." ile başlar; yerel dosya yolu bu kökle birleştirilir. */
    private const UPLOADS_PREFIX = 'uploads/';

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    }

    /** Yerel sürücüde yükleme kök dizini (basePath’e göre). Sadece local driver için. */
    public function getLocalUploadsRoot(): string
    {
        $root = trim(str_replace('\\', '/', (string) Setting::getValue('storage_local_path', 'uploads')), '/');
        if ($root === '' || preg_match('#\.\.|/\.\.#', $root)) {
            return 'uploads';
        }
        return $root ?: 'uploads';
    }

    /** Path "uploads/..." ise yerel dosya yoluna çevirir. */
    private function toLocalPath(string $path): string
    {
        $path = $this->normalizePath($path);
        if (strpos($path, self::UPLOADS_PREFIX) === 0) {
            $suffix = substr($path, strlen(self::UPLOADS_PREFIX));
            return $this->getLocalUploadsRoot() . '/' . $suffix;
        }
        return $path;
    }

    /** local | aws_s3 | r2 */
    public function getDriver(): string
    {
        $driver = Setting::getValue('storage_driver', 'local');
        if (in_array($driver, ['aws_s3', 'r2'], true)) {
            return $driver;
        }
        return 'local';
    }

    public function isCloudEnabled(): bool
    {
        return in_array($this->getDriver(), ['aws_s3', 'r2'], true);
    }

    /** Eski storage_s3_enabled ile uyumluluk */
    public function isS3Enabled(): bool
    {
        return $this->isCloudEnabled();
    }

    /**
     * Path: "uploads/avatars/2025/02/name.jpg" gibi (uploads/ ile başlamalı).
     */
    public function put(string $path, string $contents): bool
    {
        $path = $this->normalizePath($path);
        if ($this->isCloudEnabled()) {
            return $this->putCloud($path, $contents);
        }
        return $this->putLocal($path, $contents);
    }

    public function putFile(string $path, string $tmpFilePath): bool
    {
        $contents = file_get_contents($tmpFilePath);
        if ($contents === false) {
            return false;
        }
        return $this->put($path, $contents);
    }

    public function get(string $path): ?string
    {
        $path = $this->normalizePath($path);
        if ($this->isCloudEnabled()) {
            try {
                return $this->getCloudFilesystem()->read($path) ?: null;
            } catch (\Throwable $e) {
                return null;
            }
        }
        $localPath = $this->toLocalPath($path);
        $full = $this->basePath . '/' . $localPath;
        if (!is_file($full)) {
            return null;
        }
        $content = file_get_contents($full);
        return $content !== false ? $content : null;
    }

    public function exists(string $path): bool
    {
        $path = $this->normalizePath($path);
        if ($this->isCloudEnabled()) {
            try {
                return $this->getCloudFilesystem()->fileExists($path);
            } catch (\Throwable $e) {
                return false;
            }
        }
        $localPath = $this->toLocalPath($path);
        return is_file($this->basePath . '/' . $localPath);
    }

    /** @param string|null $forDriver Ekte kayıtlı driver (aws_s3, r2, s3); verilirse o sağlayıcıdan silinir. */
    public function delete(string $path, ?string $forDriver = null): bool
    {
        $path = $this->normalizePath($path);
        $driver = $forDriver ?? $this->getDriver();
        if ($forDriver === 's3') {
            $driver = $this->getDriver();
        }
        if (in_array($driver, ['aws_s3', 'r2'], true)) {
            try {
                $fs = $driver === $this->getDriver() ? $this->getCloudFilesystem() : $this->buildFilesystemForDriver($driver);
                $fs->delete($path);
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }
        $localPath = $this->toLocalPath($path);
        $full = $this->basePath . '/' . $localPath;
        if (is_file($full)) {
            return @unlink($full);
        }
        return false;
    }

    /**
     * Yüklenen dosya için tam URL. $forDriver verilirse o sağlayıcının CDN ayarı kullanılır (ek indirmede).
     */
    public function url(string $path, ?string $forDriver = null): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        $driver = $forDriver ?? $this->getDriver();
        if ($driver === 'aws_s3') {
            $cdn = rtrim((string) Setting::getValue('storage_aws_s3_cdn_url', ''), '/');
            if ($cdn !== '') {
                return $cdn . '/' . $path;
            }
        } elseif ($driver === 'r2') {
            $cdn = rtrim((string) Setting::getValue('storage_r2_cdn_url', ''), '/');
            if ($cdn !== '') {
                return $cdn . '/' . $path;
            }
        } elseif ($forDriver === 's3') {
            return $this->url($path, $this->getDriver());
        }
        $base = function_exists('core_config') ? rtrim((string) core_config('app.url', ''), '/') : '';
        if ($base === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base = $scheme . '://' . $_SERVER['HTTP_HOST'] . (function_exists('app_url_base_path') ? app_url_base_path() : '');
        }
        return $base . '/' . $path;
    }

    private function normalizePath(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    private function putLocal(string $path, string $contents): bool
    {
        $localPath = $this->toLocalPath($path);
        $full = $this->basePath . '/' . $localPath;
        $dir = dirname($full);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return false;
            }
        }
        return file_put_contents($full, $contents) !== false;
    }

    private function putCloud(string $path, string $contents): bool
    {
        try {
            $this->getCloudFilesystem()->write($path, $contents);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getCloudFilesystem(): Filesystem
    {
        if ($this->cloudFilesystem !== null) {
            return $this->cloudFilesystem;
        }
        $driver = $this->getDriver();
        if ($driver === 'aws_s3') {
            $this->cloudFilesystem = $this->buildAwsS3Filesystem();
        } elseif ($driver === 'r2') {
            $this->cloudFilesystem = $this->buildR2Filesystem();
        } else {
            throw new \RuntimeException(lang('storage.no_cloud_driver'));
        }
        return $this->cloudFilesystem;
    }

    private function buildFilesystemForDriver(string $driver): Filesystem
    {
        if ($driver === 'aws_s3') {
            return $this->buildAwsS3Filesystem();
        }
        if ($driver === 'r2') {
            return $this->buildR2Filesystem();
        }
        throw new \RuntimeException(lang('storage.invalid_driver', ['driver' => $driver]));
    }

    private function buildAwsS3Filesystem(): Filesystem
    {
        $key = Setting::getValue('storage_aws_s3_key', '');
        $secret = Setting::getValue('storage_aws_s3_secret', '');
        $region = Setting::getValue('storage_aws_s3_region', 'us-east-1');
        $bucket = Setting::getValue('storage_aws_s3_bucket', '');
        $prefix = rtrim((string) Setting::getValue('storage_aws_s3_prefix', ''), '/');
        if ($prefix !== '') {
            $prefix .= '/';
        }
        if ($bucket === '' || $key === '' || $secret === '') {
            throw new \RuntimeException(lang('storage.aws_s3_missing'));
        }
        $client = new \Aws\S3\S3Client([
            'credentials' => ['key' => $key, 'secret' => $secret],
            'region' => $region,
            'version' => 'latest',
        ]);
        $adapter = new \League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $bucket, $prefix);
        return new Filesystem($adapter);
    }

    private function buildR2Filesystem(): Filesystem
    {
        $key = Setting::getValue('storage_r2_key', '');
        $secret = Setting::getValue('storage_r2_secret', '');
        $endpoint = trim((string) Setting::getValue('storage_r2_endpoint', ''));
        $bucket = Setting::getValue('storage_r2_bucket', '');
        $prefix = rtrim((string) Setting::getValue('storage_r2_prefix', ''), '/');
        if ($prefix !== '') {
            $prefix .= '/';
        }
        if ($bucket === '' || $key === '' || $secret === '' || $endpoint === '') {
            throw new \RuntimeException(lang('storage.r2_missing'));
        }
        $client = new \Aws\S3\S3Client([
            'credentials' => ['key' => $key, 'secret' => $secret],
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => $endpoint,
        ]);
        $adapter = new \League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $bucket, $prefix);
        return new Filesystem($adapter);
    }
}
