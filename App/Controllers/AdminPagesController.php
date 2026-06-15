<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Page;

/**
 * Admin: Statik sayfalar (Kurallar, Gizlilik vb.) — oluştur, düzenle, sil.
 */
class AdminPagesController extends AdminController
{
    public function index(): string
    {
        $pages = Page::orderBy('title')->get(['id', 'slug', 'title', 'is_active', 'created_at', 'updated_at'])->all();
        return $this->view('pages/index', [
            'pageTitle' => lang('admin.pages.title'),
            'pages' => $pages,
        ]);
    }

    public function create(): string
    {
        return $this->view('pages/form', [
            'pageTitle' => lang('admin.pages.add_title'),
            'page' => null,
        ]);
    }

    public function store(): void
    {
        if (!core_csrf_valid('admin_page_store', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
            return;
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = $this->normalizeSlug((string) ($_POST['slug'] ?? ''), $title);
        $body = (string) ($_POST['body'] ?? '');
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;

        if ($title === '') {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages/create'));
            return;
        }
        if ($slug === '') {
            $slug = $this->slugFromTitle($title);
        }

        if (Page::where('slug', $slug)->exists()) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages/create'));
            return;
        }

        Page::create(['slug' => $slug, 'title' => $title, 'body' => $body, 'is_active' => (bool) $isActive]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
    }

    public function edit(int $id): string
    {
        $id = (int) $id;
        $page = Page::find($id, ['id', 'slug', 'title', 'body', 'is_active']);
        if (!$page) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
            return '';
        }
        return $this->view('pages/form', [
            'pageTitle' => lang('admin.pages.edit_title'),
            'page' => $page,
        ]);
    }

    public function update(int $id): void
    {
        $id = (int) $id;
        if (!core_csrf_valid('admin_page_update', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
            return;
        }
        $page = Page::find($id);
        if (!$page) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = $this->normalizeSlug((string) ($_POST['slug'] ?? ''), $title);
        $body = (string) ($_POST['body'] ?? '');
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';

        if ($title === '') {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages/edit/' . $id));
            return;
        }
        if ($slug === '') {
            $slug = $this->slugFromTitle($title);
        }

        if (Page::where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages/edit/' . $id));
            return;
        }

        $page->update(['slug' => $slug, 'title' => $title, 'body' => $body, 'is_active' => $isActive]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
    }

    public function delete(int $id): void
    {
        $id = (int) $id;
        if (!core_csrf_valid('admin_page_delete', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
            return;
        }
        Page::where('id', $id)->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/pages'));
    }

    private function normalizeSlug(string $slug, string $titleFallback): string
    {
        $slug = trim($slug);
        if ($slug !== '') {
            return $this->slugFromTitle($slug);
        }
        return $this->slugFromTitle($titleFallback);
    }

    private function slugFromTitle(string $s): string
    {
        $tr = ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c', 'İ' => 'i', 'I' => 'i'];
        $s = mb_strtolower(strtr($s, $tr), 'UTF-8');
        $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
        $s = preg_replace('/[\s-]+/', '-', $s);
        return trim($s, '-');
    }
}
