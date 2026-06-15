<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Setting;

/**
 * Yüklenen dosyaları (avatar, kapak, ek) sunar.
 * Storage local iken dosya basePath/{storage_local_path}/ altındadır; URL hep /uploads/... ile gelir.
 * S3/R2 kullanılıyorsa dosya buluttan CDN ile sunulur, bu controller çağrılmaz.
 */
class UploadsServeController
{
    private \Forecor\Core\Application $app;

    public function __construct(\Forecor\Core\Application $app)
    {
        $this->app = $app;
    }

    public function serve(string $path): void
    {
        $path = str_replace(['../', '..\\', "\0"], '', $path);
        $path = trim($path, '/\\');
        if ($path === '' || preg_match('#\.\.|/\.\.|\\\\#', $path)) {
            $this->send404();
            return;
        }
        $basePath = $this->app->getBasePath();
        $root = trim(str_replace('\\', '/', (string) Setting::getValue('storage_local_path', 'uploads')), '/');
        if ($root === '' || preg_match('#\.\.|/\.\.#', $root)) {
            $root = 'uploads';
        }
        $uploadsDir = $basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $root);
        $filePath = $uploadsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $realUploads = realpath($uploadsDir);
        if ($realUploads === false || !is_dir($realUploads)) {
            $this->send404();
            return;
        }
        $realFile = realpath($filePath);
        if ($realFile === false || !is_file($realFile)) {
            $this->send404();
            return;
        }
        if (strpos($realFile, $realUploads) !== 0) {
            $this->send404();
            return;
        }
        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $mimes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . (string) filesize($realFile));
        readfile($realFile);
    }

    private function send404(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Not Found';
    }
}
