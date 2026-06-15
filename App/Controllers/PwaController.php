<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * PWA manifest.json ve ilgili genel istekler (layout kullanılmaz).
 */
class PwaController extends BaseController
{
    /**
     * Web App Manifest (application/manifest+json) — ayarlardan dinamik üretilir.
     */
    public function manifest(): string
    {
        if ($this->getSetting('pwa_enabled', '0') !== '1') {
            header('Content-Type: application/json; charset=utf-8');
            return json_encode(['error' => 'PWA disabled'], JSON_UNESCAPED_UNICODE);
        }

        $baseUrl = rtrim((string) core_config('app.url', ''), '/');
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $basePath = function_exists('app_url_base_path') ? app_url_base_path() : '';
            $baseUrl .= $basePath;
        }
        $baseUrl = rtrim($baseUrl, '/');

        $name = $this->getSetting('pwa_forum_title', '') ?: $this->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB');
        $shortName = $this->getSetting('pwa_forum_short_title', '') ?: mb_substr($name, 0, 12);
        $description = $this->getSetting('pwa_meta_description', '') ?: $this->getSetting('seo_description', '');
        $themeColor = $this->getSetting('pwa_theme_color', '#1a252f');
        $backgroundColor = $this->getSetting('pwa_background_color', '#ececec');
        $dir = $this->getSetting('pwa_direction', 'ltr');
        $lang = $this->getSetting('pwa_locale', core_config('app.locale', 'tr'));
        $icon192 = trim($this->getSetting('pwa_icon_192', ''));
        $icon512 = trim($this->getSetting('pwa_icon_512', ''));
        $maskable = $this->getSetting('pwa_icons_maskable', '0') === '1';

        $icons = [];
        if ($icon192 !== '') {
            $url = strpos($icon192, 'http') === 0 ? $icon192 : $baseUrl . (strpos($icon192, '/') === 0 ? '' : '/') . $icon192;
            $icons[] = [
                'src' => $url,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => $maskable ? 'any maskable' : 'any',
            ];
        }
        if ($icon512 !== '') {
            $url = strpos($icon512, 'http') === 0 ? $icon512 : $baseUrl . (strpos($icon512, '/') === 0 ? '' : '/') . $icon512;
            $icons[] = [
                'src' => $url,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => $maskable ? 'any maskable' : 'any',
            ];
        }
        if (empty($icons)) {
            $icons[] = [
                'src' => $baseUrl . '/theme-assets/images/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ];
            $icons[] = [
                'src' => $baseUrl . '/theme-assets/images/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ];
        }

        $manifest = [
            'name' => $name,
            'short_name' => $shortName,
            'description' => $description,
            'start_url' => $baseUrl . '/',
            'scope' => $baseUrl . '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'theme_color' => $themeColor,
            'background_color' => $backgroundColor,
            'dir' => $dir,
            'lang' => $lang,
            'icons' => $icons,
        ];

        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        return json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
