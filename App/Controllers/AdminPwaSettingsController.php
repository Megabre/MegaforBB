<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Admin: PWA (Progressive Web App) ayarları — XenForo tarzı başlık, meta, ikonlar, push bildirimleri.
 */
class AdminPwaSettingsController extends AdminController
{
    private const GROUP_PWA = 'pwa';
    private const CSRF_TOKEN = 'admin_pwa_settings';

    public function index(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $settings = $this->pwaSettings();
        $settings['https_status'] = $this->isHttps();
        $settings['gmp_loaded'] = extension_loaded('gmp');
        $settings['push_available'] = $settings['https_status'] && $settings['gmp_loaded'];

        $flashPwaOk = $this->app->session()->getFlashBag()->get('pwa_ok');
        $flashPwaOk = is_array($flashPwaOk) ? ($flashPwaOk[0] ?? null) : $flashPwaOk;
        return $this->view('pwa_settings/index', [
            'pageTitle' => lang('admin.pwa.title'),
            'settings' => $settings,
            'adminPath' => $adminPath,
            'flashPwaOk' => $flashPwaOk,
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pwa-settings'));
            return;
        }

        $this->setSetting('pwa_forum_title', trim((string) ($_POST['pwa_forum_title'] ?? '')), self::GROUP_PWA);
        $this->setSetting('pwa_forum_short_title', mb_substr(trim((string) ($_POST['pwa_forum_short_title'] ?? '')), 0, 12), self::GROUP_PWA);
        $this->setSetting('pwa_meta_description', trim((string) ($_POST['pwa_meta_description'] ?? '')), self::GROUP_PWA);
        $pushAvailable = $this->isHttps() && extension_loaded('gmp');
        $pushEnabled = $pushAvailable && isset($_POST['pwa_push_enabled']) && $_POST['pwa_push_enabled'] === '1';
        $this->setSetting('pwa_push_enabled', $pushEnabled ? '1' : '0', self::GROUP_PWA);
        $this->setSetting('pwa_locale', trim((string) ($_POST['pwa_locale'] ?? 'tr')), self::GROUP_PWA);
        $this->setSetting('pwa_direction', in_array($_POST['pwa_direction'] ?? '', ['ltr', 'rtl'], true) ? $_POST['pwa_direction'] : 'ltr', self::GROUP_PWA);
        $this->setSetting('pwa_theme_color', $this->normalizeHexColor((string) ($_POST['pwa_theme_color'] ?? '#1a252f')), self::GROUP_PWA);
        $this->setSetting('pwa_background_color', $this->normalizeHexColor((string) ($_POST['pwa_background_color'] ?? '#ececec')), self::GROUP_PWA);
        $this->setSetting('pwa_icon_192', trim((string) ($_POST['pwa_icon_192'] ?? '')), self::GROUP_PWA);
        $this->setSetting('pwa_icon_512', trim((string) ($_POST['pwa_icon_512'] ?? '')), self::GROUP_PWA);
        $this->setSetting('pwa_icons_maskable', isset($_POST['pwa_icons_maskable']) && $_POST['pwa_icons_maskable'] === '1' ? '1' : '0', self::GROUP_PWA);
        $this->setSetting('pwa_enabled', isset($_POST['pwa_enabled']) && $_POST['pwa_enabled'] === '1' ? '1' : '0', self::GROUP_PWA);

        $this->app->session()->getFlashBag()->add('pwa_ok', lang('admin.pwa.saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pwa-settings'));
    }

    private function pwaSettings(): array
    {
        $siteName = $this->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB');
        $metaDesc = $this->getSetting('seo_description', '');
        return [
            'pwa_enabled' => $this->getSetting('pwa_enabled', '0') === '1',
            'pwa_forum_title' => $this->getSetting('pwa_forum_title', '') ?: $siteName,
            'pwa_forum_short_title' => $this->getSetting('pwa_forum_short_title', '') ?: mb_substr($siteName, 0, 12),
            'pwa_meta_description' => $this->getSetting('pwa_meta_description', '') ?: $metaDesc,
            'pwa_push_enabled' => $this->getSetting('pwa_push_enabled', '0') === '1',
            'pwa_locale' => $this->getSetting('pwa_locale', core_config('app.locale', 'tr')),
            'pwa_direction' => $this->getSetting('pwa_direction', 'ltr'),
            'pwa_theme_color' => $this->getSetting('pwa_theme_color', '#1a252f'),
            'pwa_background_color' => $this->getSetting('pwa_background_color', '#ececec'),
            'pwa_icon_192' => $this->getSetting('pwa_icon_192', ''),
            'pwa_icon_512' => $this->getSetting('pwa_icon_512', ''),
            'pwa_icons_maskable' => $this->getSetting('pwa_icons_maskable', '0') === '1',
        ];
    }

    private function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        return false;
    }

    private function normalizeHexColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value)) {
            return $value;
        }
        if (preg_match('/^([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $value)) {
            return '#' . $value;
        }
        return '#1a252f';
    }
}
