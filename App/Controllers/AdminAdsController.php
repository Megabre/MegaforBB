<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Ad;

/**
 * Admin: Ad slot management. Enable/disable, add, edit, delete.
 */
class AdminAdsController extends AdminController
{
    private const POSITION_KEYS = [
        'header_below_menu', 'footer_above_home', 'category_between', 'sidebar_top',
        'topic_below_breadcrumb', 'topic_between_posts', 'topic_above_footer',
    ];

    private function getPositionLabels(): array
    {
        $labels = [];
        foreach (self::POSITION_KEYS as $key) {
            $labels[$key] = lang('admin.ads.position_' . $key);
        }
        return $labels;
    }

    public function index(): string
    {
        $ads = Ad::orderBy('position_key')->orderBy('sort_order')->orderBy('id')->get(['id', 'position_key', 'name', 'html_content', 'enabled', 'sort_order'])->all();
        return $this->view('ads/index', [
            'pageTitle' => lang('admin.ads.title'),
            'ads' => $ads,
            'positionLabels' => $this->getPositionLabels(),
        ]);
    }

    public function create(): string
    {
        return $this->view('ads/form', [
            'pageTitle' => lang('admin.ads.add_title'),
            'ad' => null,
            'positionLabels' => $this->getPositionLabels(),
        ]);
    }

    public function store(): void
    {
        if (!core_csrf_valid('admin_ads_store', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
            return;
        }
        $position_key = (string) ($_POST['position_key'] ?? '');
        if (!in_array($position_key, self::POSITION_KEYS, true)) {
            $position_key = self::POSITION_KEYS[0];
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $html_content = (string) ($_POST['html_content'] ?? '');
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? 1 : 0;
        $sort_order = (int) ($_POST['sort_order'] ?? 0);
        Ad::create(['position_key' => $position_key, 'name' => $name, 'html_content' => $html_content, 'enabled' => $enabled, 'sort_order' => $sort_order]);
        Ad::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
    }

    public function edit(int $id): string
    {
        $ad = Ad::find($id);
        if (!$ad) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
            return '';
        }
        return $this->view('ads/form', [
            'pageTitle' => lang('admin.ads.edit_title'),
            'ad' => $ad,
            'positionLabels' => $this->getPositionLabels(),
        ]);
    }

    public function update(int $id): void
    {
        if (!core_csrf_valid('admin_ads_update', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
            return;
        }
        $ad = Ad::find($id);
        if (!$ad) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
            return;
        }
        $position_key = (string) ($_POST['position_key'] ?? '');
        if (!in_array($position_key, self::POSITION_KEYS, true)) {
            $position_key = self::POSITION_KEYS[0];
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $html_content = (string) ($_POST['html_content'] ?? '');
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? 1 : 0;
        $sort_order = (int) ($_POST['sort_order'] ?? 0);
        $ad->update(['position_key' => $position_key, 'name' => $name, 'html_content' => $html_content, 'enabled' => $enabled, 'sort_order' => $sort_order]);
        Ad::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
    }

    public function delete(int $id): void
    {
        if (!core_csrf_valid('admin_ads_delete', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
            return;
        }
        Ad::destroy($id);
        Ad::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/ads'));
    }

    protected function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }
}
