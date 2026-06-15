<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Tema asset dosyalarını (CSS, JS) sunar.
 * Yalnızca templates/frontend/{theme}/assets/ kullanılır (Twig tema sistemi).
 */
class ThemeAssetController
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
        $theme = \App\Models\Setting::getCached('active_frontend_theme', 'default');
        $basePath = $this->app->getBasePath();
        $pathFile = str_replace('/', DIRECTORY_SEPARATOR, $path);
        $templatesFrontend = $basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'frontend';

        $filePath = $templatesFrontend . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $pathFile;
        if (!is_file($filePath) || !is_readable($filePath)) {
            $fallbackPath = $templatesFrontend . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $pathFile;
            if (is_file($fallbackPath) && is_readable($fallbackPath)) {
                $filePath = $fallbackPath;
            } else {
                $this->send404();
                return;
            }
        }
        $assetsDir = realpath(dirname($filePath));
        if ($assetsDir === false) {
            $assetsDir = dirname($filePath);
        }
        $realPath = realpath($filePath);
        if ($realPath === false || strpos($realPath, $assetsDir) !== 0) {
            $this->send404();
            return;
        }
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimes = [
            'css' => 'text/css; charset=utf-8',
            'js'  => 'application/javascript; charset=utf-8',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=0, must-revalidate');
        readfile($filePath);
    }

    private function send404(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Not Found';
    }
}
