<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * TinyMCE self-hosted dosyalarını public/ui-vendor/tinymce/ altından sunar.
 * CDN kullanılmaz. TinyMCE paketini varient-v2.4.3 veya resmi siteden
 * public/ui-vendor/tinymce/ içine kopyalamanız gerekir.
 */
class TinyMCEAssetController
{
    private \Forecor\Core\Application $app;

    public function __construct(\Forecor\Core\Application $app)
    {
        $this->app = $app;
    }

    public function serve(string $path): void
    {
        $path = str_replace(['../', '..\\'], '', $path);
        $path = trim($path, '/');
        if ($path === '') {
            $this->send404();
            return;
        }
        $basePath = $this->app->getBasePath();
        $root = $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'ui-vendor' . DIRECTORY_SEPARATOR . 'tinymce';
        $pathFile = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $assetsDir = realpath($root);
        if ($assetsDir === false) {
            $this->send404();
            return;
        }
        $filePath = $assetsDir . DIRECTORY_SEPARATOR . $pathFile;
        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->send404();
            return;
        }
        $realPath = realpath($filePath);
        if ($realPath === false || strpos($realPath, $assetsDir) !== 0) {
            $this->send404();
            return;
        }
        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css; charset=utf-8',
            'js'  => 'application/javascript; charset=utf-8',
            'map' => 'application/json',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'svg' => 'image/svg+xml',
            'json' => 'application/json',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        readfile($realPath);
    }

    private function send404(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Not Found';
    }
}
