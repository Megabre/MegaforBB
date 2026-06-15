<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Forum ana sayfa: kategori ve forum listesi, istatistikler, sidebar.
 */
class HomeController extends BaseController
{
    private const CACHE_TTL = 120;

    private function getViewerContext(): array
    {
        $user = $this->app->auth()->user();
        $userId = $user ? (int) $user->id : null;
        $isStaff = $user && $user->role && $user->role->is_staff;
        return [$userId, $isStaff];
    }

    public function index(): string
    {
        $homeType = $this->getSetting('home_page_type', $this->getSetting('portal_enabled', '0') === '1' ? 'portal' : 'forum');
        if ($homeType === 'portal') {
            return $this->portal();
        }
        if ($homeType === 'articles') {
            $this->redirect(core_url('articles'));
            return '';
        }
        if ($homeType === 'custom_url') {
            $url = trim($this->getSetting('home_page_custom_url', ''));
            $url = $url !== '' ? (strpos($url, 'http') === 0 ? $url : core_url(ltrim($url, '/'))) : core_url('');
            $this->redirect($url);
            return '';
        }
        return $this->forumIndex();
    }

    /** Portal sayfası: vitrin — sekmeli tablo (AJAX) + 3 yapılandırılabilir kart. Cache YOK – anlık DB. */
    public function portal(): string
    {
        $cache = $this->app->cache();
        $tabLimit = max(5, min(50, (int) $this->getSetting('portal_tab_limit', '15')));
        $latestTopicsCount = max(5, min(30, (int) $this->getSetting('portal_latest_topics_count', '10')));
        $latestArticlesCount = max(3, min(20, (int) $this->getSetting('portal_latest_articles_count', '5')));
        $latestCommentsCount = max(5, min(15, (int) $this->getSetting('portal_latest_comments_count', '8')));
        $portalNewestTopics = $this->getPortalNewestTopics($latestTopicsCount);
        $portalPopularUsers = $this->getPortalPopularUsers($tabLimit);
        $portalMostViewedTopics = $this->getPortalMostViewedTopics($tabLimit);
        $portalMostRepliedTopics = $this->getPortalMostRepliedTopics($tabLimit);
        $portalTopRepliedTopics = $this->getPortalTopRepliedTopics($tabLimit);
        $portalTopViewedTopics = $this->getPortalTopViewedTopics($tabLimit);
        $portalCards = $this->getPortalCardsData();
        $stats = $cache->get('forum_stats');
        if (!$stats instanceof \stdClass) {
            $stats = $this->getStats();
            $cache->set('forum_stats', $stats, self::CACHE_TTL);
        }
        $popularTopics = $this->getPopularTopics();
        $sidebarWidgets = \App\Models\SidebarWidget::getCachedList();
        $portalTabVisibility = $this->getPortalTabVisibility();
        $sonOlaylarSettings = $this->getSonOlaylarSettings();
        $portalFeatures = $this->getPortalFeatures();
        return $this->layout('portal', [
            'pageTitle' => core__('common.home'),
            'portalFeatures' => $portalFeatures,
            'portalNewestTopics' => $portalNewestTopics,
            'portalPopularUsers' => $portalPopularUsers,
            'portalMostViewedTopics' => $portalMostViewedTopics,
            'portalMostRepliedTopics' => $portalMostRepliedTopics,
            'portalTopRepliedTopics' => $portalTopRepliedTopics,
            'portalTopViewedTopics' => $portalTopViewedTopics,
            'portalCards' => $portalCards,
            'portalTabVisibility' => $portalTabVisibility,
            'sonOlaylarSettings' => $sonOlaylarSettings,
            'portalTabLimit' => $tabLimit,
            'portalTabMax' => max($tabLimit, min(50, (int) $this->getSetting('portal_tab_max', '50'))),
            'portalLatestTopicsCount' => $latestTopicsCount,
            'portalLatestArticlesCount' => $latestArticlesCount,
            'portalLatestCommentsCount' => $latestCommentsCount,
            'stats' => $stats,
            'recentTopics' => $portalNewestTopics,
            'popularTopics' => $popularTopics,
            'sidebarWidgets' => $sidebarWidgets,
        ], false);
    }

    /** Forum ana sayfa: Son olaylar kartı + kategori ve forum listesi. Kategori/forum listesi ve stats cache'lenir. */
    public function forumIndex(): string
    {
        $cache = $this->app->cache();
        $stats = $cache->get('forum_stats');
        if (!$stats instanceof \stdClass) {
            $stats = $this->getStats();
            $cache->set('forum_stats', $stats, self::CACHE_TTL);
        }
        $categories = $cache->get('home_categories');
        if (!is_array($categories)) {
            $categories = $this->getCategoriesWithForums();
            $cache->set('home_categories', $categories, self::CACHE_TTL);
        }
        $tabLimit = max(5, min(50, (int) $this->getSetting('portal_tab_limit', '15')));
        $portalNewestTopics = $this->getPortalNewestTopics($tabLimit);
        $portalPopularUsers = $this->getPortalPopularUsers($tabLimit);
        $portalMostViewedTopics = $this->getPortalMostViewedTopics($tabLimit);
        $portalMostRepliedTopics = $this->getPortalMostRepliedTopics($tabLimit);
        $portalTabVisibility = $this->getPortalTabVisibility();
        $sonOlaylarSettings = $this->getSonOlaylarSettings();
        $popularTopics = $this->getPopularTopics();
        $sidebarWidgets = \App\Models\SidebarWidget::getCachedList();
        return $this->layout('index', [
            'stats'                   => $stats,
            'categories'              => $categories,
            'newContentModalForum'    => null,
            'recentTopics'            => $portalNewestTopics,
            'popularTopics'           => $popularTopics,
            'portalNewestTopics'      => $portalNewestTopics,
            'portalPopularUsers'      => $portalPopularUsers,
            'portalMostViewedTopics'  => $portalMostViewedTopics,
            'portalMostRepliedTopics' => $portalMostRepliedTopics,
            'portalTopRepliedTopics'  => $this->getPortalTopRepliedTopics($tabLimit),
            'portalTopViewedTopics'   => $this->getPortalTopViewedTopics($tabLimit),
            'portalTabVisibility'    => $portalTabVisibility,
            'sonOlaylarSettings'      => $sonOlaylarSettings,
            'portalTabLimit'          => $tabLimit,
            'portalTabMax'            => max($tabLimit, min(50, (int) $this->getSetting('portal_tab_max', '50'))),
            'sidebarWidgets'          => $sidebarWidgets,
            'withSidebar'             => true,
        ], true);
    }

    /** En son açılan konular (yeni konular, forum konuları) */
    protected function getPortalNewestTopics(int $limit): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $types = $this->getTopicListTypes();
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with(['user', 'lastPostUser', 'forum'])
            ->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)->whereNull('deleted_at');
        if (is_array($forumIds) && !empty($forumIds)) {
            $query->whereIn('forum_id', $forumIds);
        }
        $topics = $query->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get();
        return $topics->map(fn ($t) => (object)[
            'id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
            'username' => $t->user?->username, 'author_avatar_path' => $t->user?->avatar_path,
            'last_post_username' => $t->lastPostUser?->username, 'last_post_avatar_path' => $t->lastPostUser?->avatar_path, 'last_post_at' => $t->last_post_at?->format('Y-m-d H:i:s'),
            'forum_name' => $t->forum?->name,
        ])->all();
    }

    /** En popüler kullanıcılar (en çok mesaj yazanlar) */
    protected function getPortalPopularUsers(int $limit): array
    {
        $types = $this->getTopicListTypes();
        $users = User::withCount(['posts' => fn ($q) => $q->whereNull('deleted_at')])
            ->withCount(['topics' => fn ($q) => $q->whereNull('deleted_at')->where(fn ($q2) => $q2->whereIn('type', $types)->orWhereNull('type'))])
            ->where('is_banned', 0)->having('posts_count', '>', 0)
            ->orderByDesc('posts_count')->orderBy('id')->limit($limit)->get();
        return $users->map(fn ($u) => (object)[
            'id' => $u->id, 'username' => $u->username, 'avatar_path' => $u->avatar_path, 'created_at' => $u->created_at?->format('Y-m-d H:i:s'),
            'reputation_positive' => (int)($u->reputation_positive ?? 0), 'reputation_negative' => (int)($u->reputation_negative ?? 0),
            'reputation_net' => (int)($u->reputation_positive ?? 0) - (int)($u->reputation_negative ?? 0),
            'location' => $u->location ?? null,
            'post_count' => $u->posts_count, 'topic_count' => $u->topics_count ?? 0,
        ])->all();
    }

    protected function getPortalTabVisibility(): array
    {
        $json = $this->getSetting('portal_tab_visibility', '{}');
        $v = is_string($json) ? json_decode($json, true) : [];
        if (!is_array($v)) {
            $v = [];
        }
        $defaults = ['newest_topics' => 1,'most_replied' => 1,'most_viewed' => 1,'popular_users' => 1,'top_replied' => 1,'top_viewed' => 1];
        foreach ($defaults as $k => $d) {
            if (!isset($v[$k])) {
                $v[$k] = $d;
            }
        }
        return $v;
    }

    protected function getSonOlaylarSettings(): array
    {
        $json = $this->getSetting('son_olaylar_settings', '{}');
        $v = is_string($json) ? json_decode($json, true) : [];
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

    /** Most viewed topics (recently viewed) */
    protected function getPortalMostViewedTopics(int $limit): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $types = $this->getTopicListTypes();
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with(['user', 'lastPostUser', 'forum'])
            ->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)->whereNull('deleted_at');
        if (is_array($forumIds) && !empty($forumIds)) {
            $query->whereIn('forum_id', $forumIds);
        }
        $topics = $query->orderByRaw('(view_count + reply_count) DESC')->orderByDesc('id')->limit($limit)->get();
        return $topics->map(fn ($t) => (object)[
            'id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
            'username' => $t->user?->username, 'author_avatar_path' => $t->user?->avatar_path,
            'last_post_username' => $t->lastPostUser?->username, 'last_post_avatar_path' => $t->lastPostUser?->avatar_path, 'last_post_at' => $t->last_post_at?->format('Y-m-d H:i:s'),
            'forum_name' => $t->forum?->name,
        ])->all();
    }

    /** Most recently replied topics */
    protected function getPortalMostRepliedTopics(int $limit): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $types = $this->getTopicListTypes();
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with(['user', 'lastPostUser', 'forum'])
            ->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)->whereNull('deleted_at')
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('posts')->whereColumn('posts.topic_id', 'topics.id')->where('posts.is_first_post', 0)->whereNull('posts.deleted_at'));
        if (is_array($forumIds) && !empty($forumIds)) {
            $query->whereIn('forum_id', $forumIds);
        }
        $topics = $query->orderByDesc('last_post_at')->orderByDesc('id')->limit($limit)->get();
        return $topics->map(fn ($t) => (object)[
            'id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'last_post_at' => $t->last_post_at?->format('Y-m-d H:i:s'),
            'username' => $t->user?->username, 'author_avatar_path' => $t->user?->avatar_path,
            'last_post_username' => $t->lastPostUser?->username, 'last_post_avatar_path' => $t->lastPostUser?->avatar_path,
            'forum_name' => $t->forum?->name,
        ])->all();
    }

    /** Top replied topics */
    protected function getPortalTopRepliedTopics(int $limit): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $types = $this->getTopicListTypes();
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with(['user', 'lastPostUser', 'forum'])
            ->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)->whereNull('deleted_at');
        if (is_array($forumIds) && !empty($forumIds)) {
            $query->whereIn('forum_id', $forumIds);
        }
        $topics = $query->orderByDesc('reply_count')->orderByDesc('id')->limit($limit)->get();
        return $topics->map(fn ($t) => (object)[
            'id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'last_post_at' => $t->last_post_at?->format('Y-m-d H:i:s'),
            'username' => $t->user?->username, 'author_avatar_path' => $t->user?->avatar_path,
            'last_post_username' => $t->lastPostUser?->username, 'last_post_avatar_path' => $t->lastPostUser?->avatar_path,
            'forum_name' => $t->forum?->name,
        ])->all();
    }

    /** Top viewed topics */
    protected function getPortalTopViewedTopics(int $limit): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $types = $this->getTopicListTypes();
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with(['user', 'lastPostUser', 'forum'])
            ->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)->whereNull('deleted_at');
        if (is_array($forumIds) && !empty($forumIds)) {
            $query->whereIn('forum_id', $forumIds);
        }
        $topics = $query->orderByDesc('view_count')->orderByDesc('id')->limit($limit)->get();
        return $topics->map(fn ($t) => (object)[
            'id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'last_post_at' => $t->last_post_at?->format('Y-m-d H:i:s'),
            'username' => $t->user?->username, 'author_avatar_path' => $t->user?->avatar_path,
            'last_post_username' => $t->lastPostUser?->username, 'last_post_avatar_path' => $t->lastPostUser?->avatar_path,
            'forum_name' => $t->forum?->name,
        ])->all();
    }

    /** Portal cards data (3 cards: latest, category, popular) */
    protected function getPortalCardsData(): array
    {
        $cards = [];
        foreach ([1, 2, 3] as $slot) {
            $card = $this->getPortalCardConfig($slot);
            if ($card['enabled']) {
                $cards[] = array_merge($card, ['items' => $this->getPortalCardItems($card)]);
            }
        }
        return $cards;
    }

    /** Portal sayfası özellik kartları (Admin > Hero > Portal Özellik Kartları) – veritabanından güvenli okuma */
    protected function getPortalFeatures(): array
    {
        $defaults = [
            1 => ['icon' => 'fa-solid fa-gem', 'title' => 'Pırlanta Kalite', 'desc' => 'Modern mimari, güvenli altyapı ve sınırsız özelleştirme ile forum yazılımının zirvesi.', 'color' => 'indigo'],
            2 => ['icon' => 'fa-solid fa-bolt', 'title' => 'Hızlı & Akıcı', 'desc' => 'Laravel ve Symfony gücüyle optimize edilmiş, her ölçekte kusursuz performans.', 'color' => 'emerald'],
            3 => ['icon' => 'fa-solid fa-palette', 'title' => 'Özelleştirilebilir', 'desc' => 'Tema, eklenti ve modül desteği ile hayalinizdeki topluluğu kurun.', 'color' => 'blue'],
            4 => ['icon' => 'fa-solid fa-shield-halved', 'title' => 'Güvenli & Kararlı', 'desc' => 'Güncel güvenlik standartları ve düzenli güncellemelerle güvende kalın.', 'color' => 'amber'],
        ];
        $out = [];
        foreach ([1, 2, 3, 4] as $i) {
            $out[] = [
                'icon' => (string) $this->getSetting("hero_f{$i}_icon", $defaults[$i]['icon']),
                'title' => (string) $this->getSetting("hero_f{$i}_title", $defaults[$i]['title']),
                'desc' => (string) $this->getSetting("hero_f{$i}_desc", $defaults[$i]['desc']),
                'color' => $defaults[$i]['color'],
            ];
        }
        return $out;
    }

    protected function getPortalCardConfig(int $slot): array
    {
        $json = $this->getSetting("portal_card_{$slot}", '{}');
        $c = is_string($json) ? json_decode($json, true) : [];
        if (!is_array($c)) {
            $c = [];
        }
        return [
            'type' => $c['type'] ?? ($slot === 1 ? 'latest' : ($slot === 2 ? 'category' : 'popular')),
            'title' => $c['title'] ?? ($slot === 1 ? lang('admin.portal.card_default_latest') : ($slot === 2 ? lang('admin.portal.card_default_category') : lang('admin.portal.card_default_popular'))),
            'description' => $c['description'] ?? '',
            'layout' => $c['layout'] ?? 'grid',
            'per_slide' => max(2, min(6, (int)($c['per_slide'] ?? 4))),
            'total' => max(4, min(24, (int)($c['total'] ?? 12))),
            'category_id' => (int)($c['category_id'] ?? 0),
            'color' => $c['color'] ?? '#1c8b42',
            'border_color' => $c['border_color'] ?? '',
            'enabled' => (bool)($c['enabled'] ?? true),
        ];
    }

    protected function getPortalCardItems(array $config): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $total = (int)($config['total'] ?? 12);
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $forumFilter = is_array($forumIds) && !empty($forumIds);

        if (($config['type'] ?? '') === 'latest') {
            $types = $this->getTopicListTypes();
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $topicQuery = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with('user')->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)->whereNull('deleted_at');
            if ($forumFilter) {
                $topicQuery->whereIn('forum_id', $forumIds);
            }
            $topics = $topicQuery->orderByRaw('COALESCE(last_post_at, created_at) DESC')->limit($total)->get();
            $articleQuery = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with('user')->whereRaw("COALESCE(type, 'topic') = 'article'")->whereNull('deleted_at')
                ->orderByRaw('COALESCE(last_post_at, created_at) DESC')->limit((int)($total / 2))->get();
            $firstBodies = Post::whereIn('topic_id', $topics->pluck('id')->merge($articleQuery->pluck('id')))->where('is_first_post', 1)->whereNull('deleted_at')->pluck('body_html', 'topic_id');
            $items = [];
            foreach ($topics as $t) {
                $items[] = (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'item_type' => 'topic', 'username' => $t->user?->username, 'first_body_html' => $firstBodies[$t->id] ?? null];
            }
            foreach ($articleQuery as $t) {
                $items[] = (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'item_type' => 'article', 'username' => $t->user?->username, 'first_body_html' => $firstBodies[$t->id] ?? null];
            }
            usort($items, fn ($a, $b) => strtotime($b->created_at ?? '0') <=> strtotime($a->created_at ?? '0'));
            return array_slice($items, 0, $total);
        }

        if (($config['type'] ?? '') === 'category' && ((int)($config['category_id'] ?? 0)) > 0) {
            $catId = (int)$config['category_id'];
            $topics = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with('user')->whereHas('forum', fn ($q) => $q->where('category_id', $catId))
                ->whereNull('deleted_at')->orderByRaw('COALESCE(last_post_at, created_at) DESC')->limit($total)->get();
            $firstBodies = Post::whereIn('topic_id', $topics->pluck('id'))->where('is_first_post', 1)->whereNull('deleted_at')->pluck('body_html', 'topic_id');
            return $topics->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'item_type' => $t->type ?? 'topic', 'username' => $t->user?->username, 'first_body_html' => $firstBodies[$t->id] ?? null])->all();
        }

        if (($config['type'] ?? '') === 'popular') {
            $types = $this->getTopicListTypes();
            $placeholders = implode(',', array_fill(0, count($types), '?'));
            $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)->with('user')->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)->whereNull('deleted_at');
            if ($forumFilter) {
                $query->whereIn('forum_id', $forumIds);
            }
            $topics = $query->orderByDesc('view_count')->orderByDesc('reply_count')->limit($total)->get();
            $firstBodies = Post::whereIn('topic_id', $topics->pluck('id'))->where('is_first_post', 1)->whereNull('deleted_at')->pluck('body_html', 'topic_id');
            return $topics->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'item_type' => $t->type ?? 'topic', 'username' => $t->user?->username, 'first_body_html' => $firstBodies[$t->id] ?? null])->all();
        }

        return [];
    }

    protected function getPortalRecentTopics(int $limit): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $types = $this->getTopicListTypes();
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->with(['user', 'lastPostUser'])
            ->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)
            ->whereNull('deleted_at');
        if (is_array($forumIds) && !empty($forumIds)) {
            $query->whereIn('forum_id', $forumIds);
        }
        $topics = $query->orderByRaw('COALESCE(last_post_at, created_at) DESC')->orderByDesc('id')->limit($limit)->get();
        return $topics->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'username' => $t->user?->username, 'last_post_at' => $t->last_post_at?->format('Y-m-d H:i:s'), 'last_post_username' => $t->lastPostUser?->username, 'last_post_avatar_path' => $t->lastPostUser?->avatar_path])->all();
    }

    protected function getPortalRecentArticles(int $limit): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $topics = Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->with('user')
            ->whereRaw("COALESCE(type, 'topic') = 'article'")
            ->whereNull('deleted_at')
            ->orderByRaw('COALESCE(last_post_at, created_at) DESC')->orderByDesc('id')->limit($limit)->get();
        return $topics->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'username' => $t->user?->username])->all();
    }

    protected function getCategoriesWithForums(): array
    {
        $categories = Category::forForumList()->orderBy('sort_order')->orderBy('id')->get();
        $out = [];
        foreach ($categories as $row) {
            $row->forums = $this->getForumsByCategory((int)$row->id);
            $out[] = $row;
        }
        return $out;
    }

    protected function getForumsByCategory(int $categoryId): array
    {
        $forums = Forum::with(['lastPostUser', 'lastPost.topic'])
            ->where('category_id', $categoryId)->whereNull('parent_id')
            ->orderBy('sort_order')->orderBy('id')->get();
        if ($forums->isEmpty()) {
            return [];
        }
        $forumIds = $forums->pluck('id')->all();
        $subforums = Forum::with(['lastPostUser', 'lastPost.topic'])->where('category_id', $categoryId)->whereNotNull('parent_id')->whereIn('parent_id', $forumIds)
            ->orderBy('sort_order')->orderBy('id')->get();
        $subforumByParent = [];
        $allForumIdsForTopics = $forumIds;
        $parentSlugById = [];
        foreach ($forums as $f) {
            $parentSlugById[(int) $f->id] = $f->slug ?? '';
        }
        foreach ($subforums as $sub) {
            $pid = (int) $sub->parent_id;
            if (!isset($subforumByParent[$pid])) {
                $subforumByParent[$pid] = [];
            }
            $subforumByParent[$pid][] = (object)[
                'id' => $sub->id, 'name' => $sub->name, 'slug' => $sub->slug, 'description' => $sub->description,
                'topic_count' => $sub->topic_count, 'post_count' => $sub->post_count,
                'parent_slug' => $parentSlugById[$pid] ?? '',
                'last_post_at' => $sub->last_post_at?->format('Y-m-d H:i:s'), 'last_post_username' => $sub->lastPostUser?->username ?? null,
                'last_post_topic_title' => $sub->lastPost?->topic?->title ?? null, 'last_post_topic_slug' => $sub->lastPost?->topic?->slug ?? null,
            ];
            $allForumIdsForTopics[] = $sub->id;
        }
        $subforumIdToParent = [];
        foreach ($subforums as $sub) {
            $subforumIdToParent[(int) $sub->id] = (int) $sub->parent_id;
        }
        $types = $this->getTopicListTypes();
        [$userId, $isStaff] = $this->getViewerContext();
        $recentTopics = Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->with('user')
            ->whereIn('forum_id', $allForumIdsForTopics)
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereIn('type', $types)->orWhereNull('type'))
            ->orderByRaw('COALESCE(last_post_at, created_at) DESC')->limit(150)->get();
        $topicsByForum = [];
        foreach ($recentTopics as $topic) {
            $fid = (int) $topic->forum_id;
            $targetFid = isset($subforumIdToParent[$fid]) ? $subforumIdToParent[$fid] : $fid;
            if (!isset($topicsByForum[$targetFid])) {
                $topicsByForum[$targetFid] = [];
            }
            if (count($topicsByForum[$targetFid]) < 6) {
                $topicsByForum[$targetFid][] = (object)['id' => $topic->id, 'forum_id' => $topic->forum_id, 'title' => $topic->title, 'slug' => $topic->slug, 'reply_count' => $topic->reply_count, 'created_at' => $topic->created_at?->format('Y-m-d H:i:s'), 'username' => $topic->user?->username, 'author_avatar_path' => $topic->user?->avatar_path, 'last_post_at' => $topic->last_post_at?->format('Y-m-d H:i:s'), 'is_private' => (int)($topic->is_private ?? 0)];
            }
        }
        $result = [];
        foreach ($forums as $f) {
            $obj = (object)[
                'id' => $f->id, 'name' => $f->name, 'slug' => $f->slug, 'description' => $f->description, 'icon' => $f->icon ?? null, 'image_url' => $f->image_url ?? null,
                'topic_count' => $f->topic_count, 'post_count' => $f->post_count,
                'last_post_id' => $f->last_post_id, 'last_post_at' => $f->last_post_at?->format('Y-m-d H:i:s') ?? null, 'last_post_user_id' => $f->last_post_user_id,
                'last_post_username' => $f->lastPostUser?->username ?? null, 'last_post_avatar_path' => $f->lastPostUser?->avatar_path ?? null,
                'last_post_topic_id' => $f->lastPost?->topic_id ?? null, 'last_post_topic_title' => $f->lastPost?->topic?->title ?? null, 'last_post_topic_slug' => $f->lastPost?->topic?->slug ?? null,
                'latest_topics' => $topicsByForum[$f->id] ?? [],
                'subforums' => $subforumByParent[$f->id] ?? [],
            ];
            $result[] = $obj;
        }
        return $result;
    }
}
