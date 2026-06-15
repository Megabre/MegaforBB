<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Toast UI Editor self-hosted dosyalarını public/ui-vendor/toastui-editor/ altından sunar.
 * Geriye dönük uyumluluk için Content/js/toastui-editor de desteklenir.
 */
class ToastUIAssetController
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
        $realPath = $this->resolveAssetPath($basePath, $path);
        if ($realPath === null) {
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
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        readfile($realPath);
    }

    private function resolveAssetPath(string $basePath, string $path): ?string
    {
        $pathFile = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $roots = [
            $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'ui-vendor' . DIRECTORY_SEPARATOR . 'toastui-editor',
            $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'toastui-editor',
        ];

        foreach ($roots as $root) {
            $assetsDir = realpath($root);
            if ($assetsDir === false) {
                continue;
            }
            $filePath = $assetsDir . DIRECTORY_SEPARATOR . $pathFile;
            if (!is_file($filePath) || !is_readable($filePath)) {
                continue;
            }
            $realPath = realpath($filePath);
            if ($realPath !== false && strpos($realPath, $assetsDir) === 0) {
                return $realPath;
            }
        }

        return null;
    }

    private function send404(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Not Found';
    }
}
