<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Forum;

/**
 * Admin: Portal and article settings (portal on/off, forum selection, article comments, article forum).
 */
class AdminPortalSettingsController extends AdminController
{
    private const GROUP_PORTAL = 'portal';
    private const CSRF_TOKEN = 'admin_portal_settings';

    public function index(): string
    {
        $categories = Category::with('forums')->orderBy('sort_order')->get();
        $allForums = Forum::orderBy('name')->get();
        // Makale forumu seçimi için sadece makale kategorisindeki forumlar (forum listesinde görünmeyenler)
        $articleCategoryIds = Category::articleCategories()->pluck('id')->all();
        $articleForums = $articleCategoryIds !== []
            ? Forum::whereIn('category_id', $articleCategoryIds)->orderBy('name')->get()
            : collect();
        $portalForumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $portalForumIds = json_decode($portalForumIdsJson, true);
        if (!is_array($portalForumIds)) {
            $portalForumIds = [];
        }
        $settings = [
            'portal_forum_ids' => $portalForumIds,
            'portal_tab_limit' => (int) $this->getSetting('portal_tab_limit', '10'),
            'portal_tab_max' => (int) $this->getSetting('portal_tab_max', '20'),
            'portal_latest_topics_count' => (int) $this->getSetting('portal_latest_topics_count', '10'),
            'portal_latest_articles_count' => (int) $this->getSetting('portal_latest_articles_count', '5'),
            'portal_latest_comments_count' => (int) $this->getSetting('portal_latest_comments_count', '8'),
            'members_list_enabled' => $this->getSetting('members_list_enabled', '1') === '1',
            'portal_card_1' => $this->parsePortalCard($this->getSetting('portal_card_1', '{}'), 1),
            'portal_card_2' => $this->parsePortalCard($this->getSetting('portal_card_2', '{}'), 2),
            'portal_card_3' => $this->parsePortalCard($this->getSetting('portal_card_3', '{}'), 3),
            'article_comments_enabled' => $this->getSetting('article_comments_enabled', '1') === '1',
            'article_forum_id' => (int) $this->getSetting('article_forum_id', '0'),
            'articles_view_mode' => $this->getSetting('articles_view_mode', 'list'),
        ];
        $adminPath = env('ADMIN_PATH', 'admin');
        $flashOk = $this->app->session()->getFlashBag()->get('portal_ok');
        return $this->view('portal_settings/index', [
            'pageTitle' => lang('admin.portal.title'),
            'settings' => $settings,
            'categories' => $categories,
            'allForums' => $allForums,
            'articleForums' => $articleForums,
            'adminPath' => $adminPath,
            'flashPortalOk' => $flashOk[0] ?? null,
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/portal-settings'));
            return;
        }
        $forumIds = isset($_POST['portal_forum_ids']) && is_array($_POST['portal_forum_ids'])
            ? array_map('intval', array_filter($_POST['portal_forum_ids'], static fn ($v) => $v !== '' && $v !== '0'))
            : [];
        $this->setSetting('portal_forum_ids', json_encode(array_values(array_unique($forumIds)), JSON_UNESCAPED_UNICODE), self::GROUP_PORTAL);

        $tabLimit = isset($_POST['portal_tab_limit']) ? max(5, min(50, (int) $_POST['portal_tab_limit'])) : 15;
        $this->setSetting('portal_tab_limit', (string) $tabLimit, self::GROUP_PORTAL);

        $tabMax = isset($_POST['portal_tab_max']) ? max(10, min(50, (int) $_POST['portal_tab_max'])) : 50;
        $this->setSetting('portal_tab_max', (string) $tabMax, self::GROUP_PORTAL);

        $topicsCount = isset($_POST['portal_latest_topics_count']) ? max(5, min(30, (int) $_POST['portal_latest_topics_count'])) : 10;
        $this->setSetting('portal_latest_topics_count', (string) $topicsCount, self::GROUP_PORTAL);

        $articlesCount = isset($_POST['portal_latest_articles_count']) ? max(3, min(20, (int) $_POST['portal_latest_articles_count'])) : 5;
        $this->setSetting('portal_latest_articles_count', (string) $articlesCount, self::GROUP_PORTAL);

        $commentsCount = isset($_POST['portal_latest_comments_count']) ? max(5, min(15, (int) $_POST['portal_latest_comments_count'])) : 8;
        $this->setSetting('portal_latest_comments_count', (string) $commentsCount, self::GROUP_PORTAL);

        $membersListEnabled = isset($_POST['members_list_enabled']) && $_POST['members_list_enabled'] === '1' ? '1' : '0';
        $this->setSetting('members_list_enabled', $membersListEnabled, self::GROUP_PORTAL);

        foreach ([1, 2, 3] as $slot) {
            $key = "portal_card_{$slot}";
            $card = [
                'type' => $_POST["{$key}_type"] ?? ($slot === 1 ? 'latest' : ($slot === 2 ? 'category' : 'popular')),
                'title' => trim((string)($_POST["{$key}_title"] ?? '')),
                'description' => trim((string)($_POST["{$key}_description"] ?? '')),
                'layout' => ($_POST["{$key}_layout"] ?? 'grid') === 'list' ? 'list' : 'grid',
                'per_slide' => max(2, min(6, (int)($_POST["{$key}_per_slide"] ?? 4))),
                'total' => max(4, min(24, (int)($_POST["{$key}_total"] ?? 12))),
                'category_id' => (int)($_POST["{$key}_category_id"] ?? 0),
                'color' => trim((string)($_POST["{$key}_color"] ?? '#1c8b42')),
                'border_color' => self::normalizeOptionalColor((string)($_POST["{$key}_border_color"] ?? '')),
                'enabled' => !empty($_POST["{$key}_enabled"]),
            ];
            $this->setSetting($key, json_encode($card, JSON_UNESCAPED_UNICODE), self::GROUP_PORTAL);
        }

        $articleComments = isset($_POST['article_comments_enabled']) && $_POST['article_comments_enabled'] === '1' ? '1' : '0';
        $this->setSetting('article_comments_enabled', $articleComments, self::GROUP_PORTAL);

        $articleForumId = isset($_POST['article_forum_id']) ? max(0, (int) $_POST['article_forum_id']) : 0;
        $this->setSetting('article_forum_id', (string) $articleForumId, self::GROUP_PORTAL);

        $viewMode = isset($_POST['articles_view_mode']) && $_POST['articles_view_mode'] === 'grid' ? 'grid' : 'list';
        $this->setSetting('articles_view_mode', $viewMode, self::GROUP_PORTAL);

        $this->app->session()->getFlashBag()->add('portal_ok', lang('admin.portal.saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/portal-settings'));
    }



    /** Normalize empty or white color to empty string so no border is used. */
    private static function normalizeOptionalColor(string $value): string
    {
        $value = trim($value);
        $lower = strtolower($value);
        if ($value === '' || $lower === '#fff' || $lower === '#ffffff' || $lower === 'fff' || $lower === 'ffffff') {
            return '';
        }
        return $value;
    }

    private function parsePortalCard(string $json, int $slot): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            $data = [];
        }
        return [
            'type' => $data['type'] ?? ($slot === 1 ? 'latest' : ($slot === 2 ? 'category' : 'popular')),
            'title' => $data['title'] ?? ($slot === 1 ? lang('admin.portal.card_default_latest') : ($slot === 2 ? lang('admin.portal.card_default_category') : lang('admin.portal.card_default_popular'))),
            'description' => $data['description'] ?? '',
            'layout' => ($data['layout'] ?? 'grid') === 'list' ? 'list' : 'grid',
            'per_slide' => max(2, min(6, (int)($data['per_slide'] ?? 4))),
            'total' => max(4, min(24, (int)($data['total'] ?? 12))),
            'category_id' => (int)($data['category_id'] ?? 0),
            'color' => $data['color'] ?? '#1c8b42',
            'border_color' => self::normalizeOptionalColor((string)($data['border_color'] ?? '')),
            'enabled' => (bool)($data['enabled'] ?? true),
        ];
    }
}
