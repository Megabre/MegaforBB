<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Forum;

/**
 * Admin: Recent events card settings.
 */
class AdminSonOlaylarController extends AdminController
{
    private const GROUP_PORTAL = 'portal';
    private const CSRF_TOKEN = 'admin_son_olaylar';

    public function index(): string
    {
        $categories = Category::with('forums')->orderBy('sort_order')->get();
        $allForums = Forum::orderBy('name')->get();
        $portalForumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $portalForumIds = json_decode($portalForumIdsJson, true);
        if (!is_array($portalForumIds)) {
            $portalForumIds = [];
        }
        $settings = [
            'portal_forum_ids' => $portalForumIds,
            'portal_tab_limit' => (int) $this->getSetting('portal_tab_limit', '10'),
            'portal_tab_max' => (int) $this->getSetting('portal_tab_max', '20'),
            'portal_tab_visibility' => $this->parsePortalTabVisibility($this->getSetting('portal_tab_visibility', '{}')),
            'son_olaylar_settings' => $this->parseSonOlaylarSettings($this->getSetting('son_olaylar_settings', '{}')),
        ];

        $adminPath = env('ADMIN_PATH', 'admin');
        $flashOk = $this->app->session()->getFlashBag()->get('son_olaylar_ok');
        $validTabs = ['newest_topics', 'most_replied', 'most_viewed', 'top_replied', 'top_viewed', 'popular_users'];
        $tabDefaults = ['newest_topics' => lang('portal.tab_newest_topics'), 'most_replied' => lang('portal.tab_most_replied'), 'most_viewed' => lang('portal.tab_most_viewed'), 'top_replied' => lang('portal.tab_top_replied'), 'top_viewed' => lang('portal.tab_top_viewed'), 'popular_users' => lang('portal.tab_popular_users')];
        $savedTabOrder = $settings['son_olaylar_settings']['tab_order'] ?? [];
        $savedTabOrder = is_array($savedTabOrder) ? array_values(array_filter($savedTabOrder, fn ($x) => in_array($x, $validTabs, true))) : [];
        $tabOrder = array_merge($savedTabOrder, array_diff($validTabs, $savedTabOrder));

        $validCols = ['title', 'replies_views', 'last_reply', 'category'];
        $colDefaults = ['title' => lang('portal.col_topic_title'), 'replies_views' => lang('portal.col_replies_views'), 'last_reply' => lang('portal.col_last_action'), 'category' => lang('portal.col_category')];
        $colCheckNames = ['replies_views' => 'son_olaylar_show_replies_views', 'last_reply' => 'son_olaylar_show_last_reply', 'category' => 'son_olaylar_show_category'];
        $savedColOrder = $settings['son_olaylar_settings']['column_order'] ?? [];
        $savedColOrder = is_array($savedColOrder) ? array_values(array_filter($savedColOrder, fn ($x) => in_array($x, $validCols, true))) : [];
        $colOrder = !empty($savedColOrder) ? $savedColOrder : $validCols;
        $colOrder = array_merge($colOrder, array_diff($validCols, $colOrder));

        return $this->view('son_olaylar/index', [
            'pageTitle' => lang('admin.son_olaylar.title'),
            'settings' => $settings,
            'categories' => $categories,
            'allForums' => $allForums,
            'adminPath' => $adminPath,
            'flashOk' => $flashOk[0] ?? null,
            'tabOrder' => $tabOrder,
            'colOrder' => $colOrder,
            'tabDefaults' => $tabDefaults,
            'colDefaults' => $colDefaults,
            'colCheckNames' => $colCheckNames,
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/son-olaylar'));
            return;
        }

        // Forum IDs
        $forumIds = isset($_POST['portal_forum_ids']) && is_array($_POST['portal_forum_ids'])
            ? array_map('intval', array_filter($_POST['portal_forum_ids'], static fn ($v) => $v !== '' && $v !== '0'))
            : [];
        $this->setSetting('portal_forum_ids', json_encode(array_values(array_unique($forumIds)), JSON_UNESCAPED_UNICODE), self::GROUP_PORTAL);

        // Limit & Max
        $tabLimit = isset($_POST['portal_tab_limit']) ? max(5, min(50, (int) $_POST['portal_tab_limit'])) : 15;
        $this->setSetting('portal_tab_limit', (string) $tabLimit, self::GROUP_PORTAL);

        $tabMax = isset($_POST['portal_tab_max']) ? max(10, min(50, (int) $_POST['portal_tab_max'])) : 50;
        $this->setSetting('portal_tab_max', (string) $tabMax, self::GROUP_PORTAL);

        // Tab Visibility
        $tabVisibility = [
            'newest_topics' => !empty($_POST['portal_tab_newest_topics']),
            'most_replied' => !empty($_POST['portal_tab_most_replied']),
            'most_viewed' => !empty($_POST['portal_tab_most_viewed']),
            'popular_users' => !empty($_POST['portal_tab_popular_users']),
            'top_replied' => !empty($_POST['portal_tab_top_replied']),
            'top_viewed' => !empty($_POST['portal_tab_top_viewed']),
        ];
        $this->setSetting('portal_tab_visibility', json_encode($tabVisibility, JSON_UNESCAPED_UNICODE), self::GROUP_PORTAL);

        // Tab Order
        $tabOrder = ['newest_topics','most_replied','most_viewed','top_replied','top_viewed','popular_users'];
        if (!empty($_POST['son_olaylar_tab_order'])) {
            $decoded = json_decode((string)$_POST['son_olaylar_tab_order'], true);
            if (is_array($decoded)) {
                $valid = ['newest_topics','most_replied','most_viewed','top_replied','top_viewed','popular_users'];
                $tabOrder = array_values(array_filter($decoded, fn ($x) => in_array($x, $valid, true)));
                if (empty($tabOrder)) {
                    $tabOrder = $valid;
                }
            }
        }

        // Column Order
        $colOrder = ['title','replies_views','last_reply','category'];
        if (!empty($_POST['son_olaylar_column_order'])) {
            $decoded = json_decode((string)$_POST['son_olaylar_column_order'], true);
            if (is_array($decoded)) {
                $valid = ['title','replies_views','last_reply','category'];
                $colOrder = array_values(array_filter($decoded, fn ($x) => in_array($x, $valid, true)));
                if (empty($colOrder)) {
                    $colOrder = $valid;
                }
            }
        }

        // Settings JSON
        $sonOlaylar = [
            'tab_order' => $tabOrder,
            'column_order' => $colOrder,
            'enabled' => !isset($_POST['son_olaylar_enabled']) || $_POST['son_olaylar_enabled'] === '1',
            'show_replies_views' => !empty($_POST['son_olaylar_show_replies_views']) ? '1' : '0',
            'show_last_reply' => !empty($_POST['son_olaylar_show_last_reply']) ? '1' : '0',
            'show_category' => !empty($_POST['son_olaylar_show_category']) ? '1' : '0',
            'show_topic_icon' => !empty($_POST['son_olaylar_show_topic_icon']) ? '1' : '0',
            'tab_label_newest_topics' => trim((string)($_POST['son_olaylar_tab_label_newest_topics'] ?? '')),
            'tab_label_most_replied' => trim((string)($_POST['son_olaylar_tab_label_most_replied'] ?? '')),
            'tab_label_most_viewed' => trim((string)($_POST['son_olaylar_tab_label_most_viewed'] ?? '')),
            'tab_label_top_viewed' => trim((string)($_POST['son_olaylar_tab_label_top_viewed'] ?? '')),
            'tab_label_top_replied' => trim((string)($_POST['son_olaylar_tab_label_top_replied'] ?? '')),
            'tab_label_popular_users' => trim((string)($_POST['son_olaylar_tab_label_popular_users'] ?? '')),
            'comment_snippet_limit' => max(40, min(150, (int)($_POST['son_olaylar_comment_snippet_limit'] ?? 80))),
            'topic_title_limit' => max(40, min(120, (int)($_POST['son_olaylar_topic_title_limit'] ?? 80))),
        ];
        $this->setSetting('son_olaylar_settings', json_encode($sonOlaylar, JSON_UNESCAPED_UNICODE), self::GROUP_PORTAL);

        $this->app->session()->getFlashBag()->add('son_olaylar_ok', lang('admin.son_olaylar.saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/son-olaylar'));
    }

    private function parsePortalTabVisibility(string $json): array
    {
        $v = json_decode($json, true);
        if (!is_array($v)) {
            $v = [];
        }
        $defaults = ['newest_topics' => true,'most_replied' => true,'most_viewed' => true,'popular_users' => true,'top_viewed' => true,'top_replied' => true];
        foreach ($defaults as $k => $d) {
            if (!isset($v[$k])) {
                $v[$k] = $d;
            }
        }
        return $v;
    }

    private function parseSonOlaylarSettings(string $json): array
    {
        $v = json_decode($json, true);
        if (!is_array($v)) {
            $v = [];
        }
        $validTabs = ['newest_topics','most_replied','most_viewed','top_replied','top_viewed','popular_users'];
        $validCols = ['title','replies_views','last_reply','category'];

        $tabOrder = $v['tab_order'] ?? $validTabs;
        if (!is_array($tabOrder)) {
            $tabOrder = $validTabs;
        }
        $tabOrder = array_values(array_filter($tabOrder, fn ($x) => in_array($x, $validTabs, true)));
        if (empty($tabOrder)) {
            $tabOrder = $validTabs;
        }

        $colOrder = $v['column_order'] ?? $validCols;
        if (!is_array($colOrder)) {
            $colOrder = $validCols;
        }
        $colOrder = array_values(array_filter($colOrder, fn ($x) => in_array($x, $validCols, true)));
        if (empty($colOrder)) {
            $colOrder = $validCols;
        }

        return [
            'tab_order' => $tabOrder,
            'column_order' => $colOrder,
            'enabled' => !isset($v['enabled']) || $v['enabled'] === true || $v['enabled'] === 1 || $v['enabled'] === '1',
            'show_replies_views' => ($v['show_replies_views'] ?? '1') === '1',
            'show_last_reply' => ($v['show_last_reply'] ?? '1') === '1',
            'show_category' => ($v['show_category'] ?? '1') === '1',
            'show_topic_icon' => ($v['show_topic_icon'] ?? '1') === '1',
            'tab_label_newest_topics' => $v['tab_label_newest_topics'] ?? lang('portal.tab_newest_topics'),
            'tab_label_most_replied' => $v['tab_label_most_replied'] ?? lang('portal.tab_most_replied'),
            'tab_label_most_viewed' => $v['tab_label_most_viewed'] ?? lang('portal.tab_most_viewed'),
            'tab_label_top_replied' => $v['tab_label_top_replied'] ?? lang('portal.tab_top_replied'),
            'tab_label_top_viewed' => $v['tab_label_top_viewed'] ?? lang('portal.tab_top_viewed'),
            'tab_label_popular_users' => $v['tab_label_popular_users'] ?? lang('portal.tab_popular_users'),
            'comment_snippet_limit' => max(40, min(150, (int)($v['comment_snippet_limit'] ?? 80))),
            'topic_title_limit' => max(40, min(120, (int)($v['topic_title_limit'] ?? 80))),
        ];
    }
}
