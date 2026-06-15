<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DocPage;
use App\Models\DocSection;

/**
 * Admin: Documentation (Docusaurus-style) — tree, CRUD, drag-drop reorder. Core feature.
 */
class AdminDocumentationController extends AdminController
{
    private const GROUP_PORTAL = 'portal';
    private const CSRF_DOC = 'admin_documentation';

    public function index(): string
    {
        $enabled = $this->getSetting('documentation_enabled', '0') === '1';
        $docTitle = $this->getSetting('documentation_title', 'Documentation');
        $sectionTree = $this->buildSectionTree();
        $adminPath = env('ADMIN_PATH', 'admin');
        $flash = $this->app->session()->getFlashBag()->get('doc_ok');

        return $this->view('documentation_settings/index', [
            'pageTitle' => lang('admin.documentation.title'),
            'documentation_enabled' => $enabled,
            'documentation_title' => $docTitle,
            'sectionTree' => $sectionTree,
            'sectionsFlat' => self::sectionsFlatForSelect(null),
            'adminPath' => $adminPath,
            'flashDocOk' => $flash[0] ?? null,
        ]);
    }

    /** Build nested tree for admin UI. */
    private function buildSectionTree(): array
    {
        $all = DocSection::with('pages')->orderBy('sort_order')->orderBy('id')->get();
        $byParent = [];
        foreach ($all as $s) {
            $pid = $s->parent_id;
            if (!isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $s;
        }
        $build = function ($parentId) use (&$build, $byParent) {
            $list = $byParent[$parentId] ?? [];
            $out = [];
            foreach ($list as $s) {
                $out[] = [
                    'section' => $s,
                    'path' => $s->getPathString(),
                    'children' => $build($s->id),
                    'pages' => $s->pages ? $s->pages->all() : [],
                ];
            }
            return $out;
        };
        return $build(null);
    }

    /** Flat list of sections with depth (for parent dropdown). Exclude id and its descendants when editing. */
    public static function sectionsFlatForSelect(?int $excludeId = null): array
    {
        $all = DocSection::orderBy('sort_order')->orderBy('id')->get();
        $byParent = [];
        foreach ($all as $s) {
            $pid = $s->parent_id;
            if (!isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $s;
        }
        $excludeIds = $excludeId ? self::collectDescendantIds(DocSection::find($excludeId)) : [];
        $out = [];
        $add = function ($parentId, int $depth) use (&$add, $byParent, $excludeIds, &$out) {
            $list = $byParent[$parentId] ?? [];
            foreach ($list as $s) {
                if (in_array($s->id, $excludeIds, true)) {
                    continue;
                }
                $out[] = (object) ['section' => $s, 'depth' => $depth];
                $add($s->id, $depth + 1);
            }
        };
        $add(null, 0);
        return $out;
    }

    private static function collectDescendantIds(?DocSection $section): array
    {
        if (!$section) {
            return [];
        }
        $ids = [$section->id];
        foreach (DocSection::where('parent_id', $section->id)->get() as $child) {
            $ids = array_merge($ids, self::collectDescendantIds($child));
        }
        return $ids;
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_DOC, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $title = trim((string) ($_POST['documentation_title'] ?? ''));
        if ($title === '') {
            $title = 'Documentation';
        }
        $this->setSetting('documentation_title', $title, self::GROUP_PORTAL);
        $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.common.saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
    }

    public function storeSection(): void
    {
        if (!core_csrf_valid(self::CSRF_DOC, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.section_title_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;
        $slug = DocSection::slugFromTitle($title);
        $base = $slug;
        $i = 0;
        while (DocSection::slugExistsForSibling($parentId, $slug, null)) {
            $slug = $base . '-' . (++$i);
        }
        $maxOrder = DocSection::where('parent_id', $parentId)->max('sort_order');
        $sortOrder = (int) $maxOrder + 10;
        DocSection::create(['parent_id' => $parentId, 'title' => $title, 'slug' => $slug, 'sort_order' => $sortOrder]);
        $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.section_created'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
    }

    public function editSection(int $id): string
    {
        $section = DocSection::find($id);
        if (!$section) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return '';
        }
        $sectionsFlat = self::sectionsFlatForSelect($id);
        return $this->view('documentation_settings/section_form', [
            'pageTitle' => lang('admin.documentation.edit_section'),
            'section' => $section,
            'sectionsFlat' => $sectionsFlat,
            'adminPath' => env('ADMIN_PATH', 'admin'),
        ]);
    }

    public function updateSection(int $id): void
    {
        if (!core_csrf_valid(self::CSRF_DOC, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $section = DocSection::find($id);
        if (!$section) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $newParentId = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int) $_POST['parent_id'] : null;
        if ($newParentId === $id || in_array($newParentId, self::collectDescendantIds($section), true)) {
            $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.cannot_move_into_self'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings/sections/edit/' . $id));
            return;
        }
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            $slug = DocSection::slugFromTitle($title);
        } else {
            $slug = DocSection::slugFromTitle($slug);
        }
        if (DocSection::slugExistsForSibling($newParentId, $slug, $id)) {
            $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.slug_exists'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings/sections/edit/' . $id));
            return;
        }
        $section->update([
            'parent_id' => $newParentId,
            'title' => $title,
            'slug' => $slug,
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? $section->sort_order)),
        ]);
        $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.section_updated'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
    }

    public function deleteSection(int $id): void
    {
        if (!core_csrf_valid(self::CSRF_DOC, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        DocSection::where('id', $id)->delete();
        $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.section_deleted'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
    }

    public function reorderSections(): void
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['order']) || !core_csrf_valid(self::CSRF_DOC, (string) ($data['_token'] ?? ''))) {
            $this->json(['ok' => false, 'message' => 'Invalid payload']);
            return;
        }
        $order = $data['order'];
        foreach ($order as $i => $item) {
            if (!isset($item['id'])) {
                continue;
            }
            $id = (int) $item['id'];
            $parentId = isset($item['parent_id']) && $item['parent_id'] !== '' && $item['parent_id'] !== null ? (int) $item['parent_id'] : null;
            $section = DocSection::find($id);
            if ($section) {
                $section->update(['parent_id' => $parentId, 'sort_order' => $i * 10]);
            }
        }
        $this->json(['ok' => true]);
    }

    public function reorderPages(): void
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['section_id']) || !isset($data['order']) || !core_csrf_valid(self::CSRF_DOC, (string) ($data['_token'] ?? ''))) {
            $this->json(['ok' => false, 'message' => 'Invalid payload']);
            return;
        }
        $sectionId = (int) $data['section_id'];
        $order = $data['order'];
        if (!is_array($order)) {
            $this->json(['ok' => false, 'message' => 'Invalid order']);
            return;
        }
        foreach ($order as $i => $pageId) {
            $page = DocPage::find((int) $pageId);
            if (!$page) {
                continue;
            }
            $sortOrder = $i * 10;
            $updates = ['sort_order' => $sortOrder];
            if ((int) $page->section_id !== $sectionId) {
                $updates['section_id'] = $sectionId;
                if (DocPage::where('section_id', $sectionId)->where('slug', $page->slug)->where('id', '!=', $page->id)->exists()) {
                    $base = $page->slug;
                    $n = 1;
                    do {
                        $updates['slug'] = $base . '-' . $n;
                        $n++;
                    } while (DocPage::where('section_id', $sectionId)->where('slug', $updates['slug'])->exists());
                }
            }
            $page->update($updates);
        }
        $this->json(['ok' => true]);
    }

    public function storePage(): void
    {
        if (!core_csrf_valid(self::CSRF_DOC, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $sectionId = (int) ($_POST['section_id'] ?? 0);
        $section = DocSection::find($sectionId);
        if (!$section) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.page_title_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $slug = DocPage::slugFromTitle($title);
        if (DocPage::where('section_id', $sectionId)->where('slug', $slug)->exists()) {
            $slug = $slug . '-' . (DocPage::where('section_id', $sectionId)->count() + 1);
        }
        $sortOrder = (int) DocPage::where('section_id', $sectionId)->max('sort_order') + 10;
        DocPage::create(['section_id' => $sectionId, 'title' => $title, 'slug' => $slug, 'content' => (string) ($_POST['content'] ?? ''), 'sort_order' => $sortOrder]);
        $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.page_created'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
    }

    public function editPage(int $id): string
    {
        $page = DocPage::with('section')->find($id);
        if (!$page) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return '';
        }
        $sectionsFlat = self::sectionsFlatForSelect(null);
        return $this->view('documentation_settings/page_form', [
            'pageTitle' => lang('admin.documentation.edit_page'),
            'docPage' => $page,
            'sectionsFlat' => $sectionsFlat,
            'adminPath' => env('ADMIN_PATH', 'admin'),
        ]);
    }

    public function updatePage(int $id): void
    {
        if (!core_csrf_valid(self::CSRF_DOC, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $page = DocPage::find($id);
        if (!$page) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings/pages/edit/' . $id));
            return;
        }
        $newSectionId = isset($_POST['section_id']) ? (int) $_POST['section_id'] : $page->section_id;
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            $slug = DocPage::slugFromTitle($title);
        } else {
            $slug = DocPage::slugFromTitle($slug);
        }
        if (DocPage::where('section_id', $newSectionId)->where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.slug_exists'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings/pages/edit/' . $id));
            return;
        }
        $page->update([
            'section_id' => DocSection::find($newSectionId) ? $newSectionId : $page->section_id,
            'title' => $title,
            'slug' => $slug,
            'content' => (string) ($_POST['content'] ?? ''),
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? $page->sort_order)),
        ]);
        $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.page_updated'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
    }

    public function deletePage(int $id): void
    {
        if (!core_csrf_valid(self::CSRF_DOC, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
            return;
        }
        DocPage::where('id', $id)->delete();
        $this->app->session()->getFlashBag()->add('doc_ok', lang('admin.documentation.page_deleted'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/documentation-settings'));
    }
}
