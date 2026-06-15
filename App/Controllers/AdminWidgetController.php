<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SidebarWidget;

/**
 * Admin: Widget / Sidebar management. Sort, add/edit/delete HTML and built-in widgets.
 */
class AdminWidgetController extends AdminController
{
    private const TYPE_KEYS = ['html', 'online_users', 'forum_stats', 'recent_topics', 'popular_topics', 'tag_cloud'];

    private function getTypeLabels(): array
    {
        $labels = [];
        foreach (self::TYPE_KEYS as $key) {
            $labels[$key] = lang('admin.widgets.type_' . $key);
        }
        return $labels;
    }

    /** Türüne göre content değeri: html = textarea, tag_cloud = etiket adedi (sayı). */
    private function widgetContentForType(string $type, ?SidebarWidget $widget): ?string
    {
        if ($type === 'html') {
            return trim((string) ($_POST['content'] ?? ''));
        }
        if ($type === 'tag_cloud') {
            $n = (int) ($_POST['tag_limit'] ?? $widget->content ?? 10);
            return (string) max(1, min(50, $n));
        }
        return null;
    }

    public function index(): string
    {
        $widgets = SidebarWidget::orderBy('sort_order')->orderBy('id')->get(['id', 'type', 'title', 'content', 'sort_order', 'enabled'])->all();
        return $this->view('widgets/index', [
            'pageTitle' => lang('admin.widgets.title'),
            'widgets' => $widgets,
            'typeLabels' => $this->getTypeLabels(),
        ]);
    }

    public function create(): string
    {
        return $this->view('widgets/form', [
            'pageTitle' => lang('admin.widgets.add_title'),
            'widget' => null,
            'typeLabels' => $this->getTypeLabels(),
        ]);
    }

    public function store(): void
    {
        if (!core_csrf_valid('admin_widget_store', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
            return;
        }
        $type = (string) ($_POST['type'] ?? 'html');
        if (!in_array($type, self::TYPE_KEYS, true)) {
            $type = 'html';
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = $this->widgetContentForType($type, null);
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? 1 : 0;
        $maxOrder = (int) SidebarWidget::max('sort_order');
        SidebarWidget::create([
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'sort_order' => $maxOrder + 10,
            'enabled' => $enabled,
        ]);
        SidebarWidget::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
    }

    public function edit(int $id): string
    {
        $widget = SidebarWidget::find($id);
        if (!$widget) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
            return '';
        }
        return $this->view('widgets/form', [
            'pageTitle' => lang('admin.widgets.edit_title'),
            'widget' => $widget,
            'typeLabels' => $this->getTypeLabels(),
        ]);
    }

    public function update(int $id): void
    {
        if (!core_csrf_valid('admin_widget_update', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
            return;
        }
        $widget = SidebarWidget::find($id);
        if (!$widget) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
            return;
        }
        $type = (string) ($_POST['type'] ?? 'html');
        if (!in_array($type, self::TYPE_KEYS, true)) {
            $type = 'html';
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = $this->widgetContentForType($type, $widget);
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? 1 : 0;
        $widget->update(['type' => $type, 'title' => $title, 'content' => $content, 'enabled' => $enabled]);
        SidebarWidget::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
    }

    public function delete(int $id): void
    {
        if (!core_csrf_valid('admin_widget_delete', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
            return;
        }
        SidebarWidget::destroy($id);
        SidebarWidget::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/widgets'));
    }

    public function reorder(): void
    {
        if (!core_csrf_valid('admin_widget_reorder', (string) ($_POST['_token'] ?? ''))) {
            $this->json(['ok' => false], 403);
            return;
        }
        $order = isset($_POST['order']) && is_array($_POST['order']) ? $_POST['order'] : [];
        foreach ($order as $i => $id) {
            $id = (int) $id;
            if ($id > 0) {
                SidebarWidget::where('id', $id)->update(['sort_order' => $i * 10]);
            }
        }
        SidebarWidget::clearCache();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    protected function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
