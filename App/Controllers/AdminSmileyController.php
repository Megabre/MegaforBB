<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Setting;
use App\Models\Smiley;

/**
 * Admin: Smiley / emoji yönetimi — Unicode ve GIF smiley'ler, ayarlar.
 */
class AdminSmileyController extends AdminController
{
    private const CSRF_TOKEN = 'admin_smiley';
    private const GROUP = 'smiley';
    private const UPLOAD_DIR = 'smileys';

    public function index(): string
    {
        $smileys = Smiley::orderBy('sort_order')->orderBy('id')->get();
        $enabled = (string) Setting::getValue('smiley_enabled', '1') === '1';
        $useGif = (string) Setting::getValue('smiley_use_gif', '0') === '1';
        $maxSizeKb = (int) Setting::getValue('smiley_gif_max_size_kb', '50');
        return $this->view('smiley/index', [
            'pageTitle' => lang('admin.smiley.page_title'),
            'smileys' => $smileys,
            'smiley_enabled' => $enabled,
            'smiley_use_gif' => $useGif,
            'smiley_gif_max_size_kb' => $maxSizeKb,
            'csrfToken' => core_csrf_token(self::CSRF_TOKEN),
            'adminPath' => env('ADMIN_PATH', 'admin'),
        ]);
    }

    public function updateSettings(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys'));
            return;
        }
        $this->setSetting('smiley_enabled', isset($_POST['smiley_enabled']) && $_POST['smiley_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('smiley_use_gif', isset($_POST['smiley_use_gif']) && $_POST['smiley_use_gif'] === '1' ? '1' : '0', self::GROUP);
        $maxKb = (int) ($_POST['smiley_gif_max_size_kb'] ?? 50);
        $maxKb = max(10, min(500, $maxKb));
        $this->setSetting('smiley_gif_max_size_kb', (string) $maxKb, self::GROUP);
        $this->app->cache()->clear();
        $this->app->session()->getFlashBag()->add('smiley_ok', lang('admin.smiley.settings_saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys'));
    }

    public function create(): string
    {
        return $this->view('smiley/form', [
            'pageTitle' => lang('admin.smiley.add_title'),
            'smiley' => new Smiley(),
            'adminPath' => env('ADMIN_PATH', 'admin'),
            'csrfToken' => core_csrf_token(self::CSRF_TOKEN),
        ]);
    }

    public function store(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/create'));
            return;
        }
        $code = trim((string) ($_POST['code'] ?? ''));
        if ($code === '') {
            $this->app->session()->getFlashBag()->add('smiley_error', lang('admin.smiley.code_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/create'));
            return;
        }
        $unicodeChar = trim((string) ($_POST['unicode_char'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $imagePath = null;

        if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $maxKb = (int) Setting::getValue('smiley_gif_max_size_kb', '50');
            $maxBytes = $maxKb * 1024;
            if ($_FILES['image']['size'] > $maxBytes) {
                $this->app->session()->getFlashBag()->add('smiley_error', lang('admin.smiley.file_too_large', ['max' => $maxKb]));
                $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/create'));
                return;
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            if (!in_array($mime, ['image/gif'], true)) {
                $this->app->session()->getFlashBag()->add('smiley_error', lang('admin.smiley.file_not_gif'));
                $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/create'));
                return;
            }
            $dir = MEGAFORBB_BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . self::UPLOAD_DIR;
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = preg_replace('/[^a-z0-9_-]/i', '', $code) . '_' . time() . '.' . ($ext === 'gif' ? 'gif' : 'gif');
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (@move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
                $imagePath = self::UPLOAD_DIR . '/' . $filename;
            }
        }

        Smiley::create([
            'code' => $code,
            'unicode_char' => $unicodeChar !== '' ? $unicodeChar : null,
            'image_path' => $imagePath,
            'sort_order' => $sortOrder,
        ]);
        $this->app->session()->getFlashBag()->add('smiley_ok', lang('admin.smiley.added'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys'));
    }

    public function edit(string $id): string
    {
        $smiley = Smiley::findOrFail((int) $id);
        return $this->view('smiley/form', [
            'pageTitle' => lang('admin.smiley.edit_title'),
            'smiley' => $smiley,
            'adminPath' => env('ADMIN_PATH', 'admin'),
            'csrfToken' => core_csrf_token(self::CSRF_TOKEN),
        ]);
    }

    public function update(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/edit/' . $id));
            return;
        }
        $smiley = Smiley::findOrFail((int) $id);
        $code = trim((string) ($_POST['code'] ?? ''));
        if ($code === '') {
            $this->app->session()->getFlashBag()->add('smiley_error', lang('admin.smiley.code_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/edit/' . $id));
            return;
        }
        $unicodeChar = trim((string) ($_POST['unicode_char'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $imagePath = $smiley->image_path;

        if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $maxKb = (int) Setting::getValue('smiley_gif_max_size_kb', '50');
            $maxBytes = $maxKb * 1024;
            if ($_FILES['image']['size'] > $maxBytes) {
                $this->app->session()->getFlashBag()->add('smiley_error', lang('admin.smiley.file_too_large', ['max' => $maxKb]));
                $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/edit/' . $id));
                return;
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            if (!in_array($mime, ['image/gif'], true)) {
                $this->app->session()->getFlashBag()->add('smiley_error', lang('admin.smiley.file_not_gif'));
                $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys/edit/' . $id));
                return;
            }
            $dir = MEGAFORBB_BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . self::UPLOAD_DIR;
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = preg_replace('/[^a-z0-9_-]/i', '', $code) . '_' . time() . '.' . ($ext === 'gif' ? 'gif' : 'gif');
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (@move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
                if ($smiley->image_path && strpos($smiley->image_path, self::UPLOAD_DIR . '/') === 0) {
                    $oldPath = MEGAFORBB_BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $smiley->image_path);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $imagePath = self::UPLOAD_DIR . '/' . $filename;
            }
        }

        $smiley->code = $code;
        $smiley->unicode_char = $unicodeChar !== '' ? $unicodeChar : null;
        $smiley->image_path = $imagePath;
        $smiley->sort_order = $sortOrder;
        $smiley->save();
        $this->app->session()->getFlashBag()->add('smiley_ok', lang('admin.smiley.updated'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys'));
    }

    public function delete(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys'));
            return;
        }
        $smiley = Smiley::find((int) $id);
        if ($smiley) {
            if ($smiley->image_path && strpos($smiley->image_path, self::UPLOAD_DIR . '/') === 0) {
                $oldPath = MEGAFORBB_BASE_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $smiley->image_path);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $smiley->delete();
        }
        $this->app->session()->getFlashBag()->add('smiley_ok', lang('admin.smiley.deleted'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/smileys'));
    }
}
