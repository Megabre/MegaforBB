<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\SmileyHelper;
use App\Models\Notification;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * AJAX/API: rozet sayıları, kullanıcı arama, bildirim listesi (JSON).
 */
class ApiController extends BaseController
{
    public function badges(): string
    {
        $user = $this->app->auth()->user();
        $messagesEnabled = $this->getSetting('messages_enabled', '1') === '1';
        $notificationsEnabled = $this->getSetting('notifications_enabled', '1') === '1';
        $notifications = 0;
        $messages = 0;
        if ($user) {
            $blocked = $this->layoutService()->getBlockedUserIds((int)$user->id);
            if ($notificationsEnabled) {
                $notifications = $this->layoutService()->countUnreadNotifications((int)$user->id, $blocked);
            }
            if ($messagesEnabled) {
                $messages = $this->layoutService()->countUnreadConversations((int)$user->id, $blocked);
            }
        }
        $this->json(['notifications' => $notifications, 'messages' => $messages]);
        return '';
    }

    /** Ajax ile Header User-Nav (Profil ikonları, dinamik menüler) bloğunu çeker. */
    public function userNav(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->json(['html' => '']);
            return '';
        }

        $html = $this->app->twig('frontend')->render('partials/user_nav.html.twig', [
            'currentUser' => $user,
            'notificationsEnabled' => $this->getSetting('notifications_enabled', '1') === '1',
            'messagesEnabled' => $this->getSetting('messages_enabled', '1') === '1'
        ]);

        $this->json(['html' => $html]);
        return '';
    }

    /** Kullanıcı adı arama (mesaj alıcı autocomplete). Engellenen/engelleyen hariç. */
    public function userSearch(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->json(['users' => []], 401);
            return '';
        }
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) {
            $this->json(['users' => []]);
            return '';
        }
        $blocked = $this->layoutService()->getBlockedUserIds((int)$user->id);
        $query = User::where('is_banned', 0)->where('id', '!=', $user->id)
            ->where(function ($qry) use ($q) {
                $qry->where('username', 'like', $q . '%')->orWhere('username', 'like', '%' . $q . '%');
            });
        if (!empty($blocked)) {
            $query->whereNotIn('id', $blocked);
        }
        $rows = $query->orderBy('username')->limit(50)->get(['id', 'username', 'avatar_path']);
        $users = $rows->map(function ($r) {
            return [
                'id' => (int)$r->id,
                'username' => $r->username,
                'avatar' => $r->avatar_path ? asset_url($r->avatar_path) : ('https://ui-avatars.com/api/?name=' . urlencode($r->username ?? 'User') . '&size=40'),
            ];
        })->all();
        $this->json(['users' => $users]);
        return '';
    }

    /** Tag suggestions (AJAX when creating topic). Max 15, search by name/slug. */
    public function tagsSuggest(): string
    {
        $q = trim((string)($_GET['q'] ?? ''));
        try {
            if (mb_strlen($q) < 1) {
                $this->json(['tags' => []]);
                return '';
            }
            $term = '%' . $q . '%';
            $tags = Tag::where(function ($qry) use ($term) {
                $qry->where('name', 'like', $term)->orWhere('slug', 'like', $term);
            })->orderByDesc('use_count')->orderBy('name')->limit(15)->get(['id', 'name', 'slug']);
            $this->json(['tags' => $tags->map(fn ($r) => ['id' => (int)$r->id, 'name' => $r->name, 'slug' => $r->slug])->all()]);
        } catch (\Throwable $e) {
            $this->json(['tags' => []]);
        }
        return '';
    }

    /** Tag get-or-create (WordPress-style: create if missing, return if exists). Used when creating topic. Login required. */
    public function tagCreate(): string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => lang('api.invalid_request')], 405);
            return '';
        }

        $tagCsrf = (string) ($_POST['api_tag_csrf'] ?? '');
        if ($tagCsrf === '') {
            $tagCsrf = (string) ($_POST['_token'] ?? '');
        }
        if (!core_csrf_valid('api_tag_create', $tagCsrf)) {
            $this->json(['error' => lang('api.csrf_invalid') ?: 'Invalid CSRF token.'], 403);
            return '';
        }

        $user = $this->app->auth()->user();
        if (!$user) {
            $this->json(['error' => lang('api.login_required')], 401);
            return '';
        }
        $name = trim((string)($_POST['name'] ?? $_POST['tag'] ?? ''));
        if ($name === '') {
            $this->json(['error' => lang('api.tag_name_required')], 400);
            return '';
        }
        $name = mb_substr($name, 0, 100);
        try {
            $slug = \Forecor\Core\Str::slug($name) ?: 'tag-' . uniqid();
            $existing = Tag::where('name', $name)->orWhere('slug', $slug)->first();
            if ($existing) {
                $this->json(['id' => (int)$existing->id, 'name' => $existing->name, 'slug' => $existing->slug]);
                return '';
            }
            if (Tag::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . substr(uniqid(), -5);
            }
            $tag = Tag::create(['name' => $name, 'slug' => $slug, 'use_count' => 0]);
            $this->json(['id' => (int)$tag->id, 'name' => $name, 'slug' => $slug]);
        } catch (\Throwable $e) {
            $this->json(['error' => lang('api.tag_system_unavailable')], 500);
        }
        return '';
    }

    /** Last 5 notifications for header dropdown (read + unread). */
    public function notificationsDropdown(): string
    {
        $user = $this->app->auth()->user();
        if (!$user || $this->getSetting('notifications_enabled', '1') !== '1') {
            $this->json(['notifications' => []]);
            return '';
        }
        $blocked = $this->layoutService()->getBlockedUserIds((int)$user->id);
        $list = Notification::where('user_id', $user->id)->where('type', '!=', 'message')->orderByDesc('id')->limit(5)->get(['id', 'type', 'data', 'read_at', 'created_at', 'sender_user_id']);
        $typeLabels = [
            'mention' => lang('api.notification_mention'),
            'quote' => lang('api.notification_quote'),
            'reply' => lang('api.notification_reply'),
            'subscribed_topic_reply' => lang('api.notification_subscribed_topic_reply'),
            'reaction' => lang('api.notification_reaction'),
            'reputation' => lang('api.notification_reputation'),
            'report' => lang('api.notification_report'),
            'announcement' => lang('api.notification_announcement'),
            'private_topic_added' => lang('api.notification_private_topic_added'),
            'pm_undeliverable' => lang('api.notification_pm_undeliverable'),
        ];
        $out = [];
        foreach ($list as $n) {
            $data = is_string($n->data ?? '') ? json_decode($n->data, true) : (is_array($n->data ?? null) ? $n->data : []);
            $data = is_array($data) ? $data : [];
            $fromUid = $this->notificationSenderUserId($n, $data);
            if ($fromUid && in_array($fromUid, $blocked, true)) {
                continue;
            }
            $label = $data['message'] ?? (($n->type ?? '') === 'announcement' ? ($data['label'] ?? lang('api.notification_announcement')) : ($typeLabels[$n->type ?? ''] ?? $n->type));
            $out[] = [
                'id' => (int)$n->id,
                'type' => $n->type,
                'label' => $label,
                'from_username' => $data['from_username'] ?? '',
                'read_url' => core_url('notifications/' . $n->id . '/read'),
                'created_at' => $n->created_at instanceof \DateTimeInterface ? $n->created_at->format('Y-m-d H:i:s') : $n->created_at,
                'unread' => empty($n->read_at),
            ];
        }
        $this->json(['notifications' => $out]);
        return '';
    }

    /** Unread notifications (for toast; last 20). */
    public function notificationsUnread(): string
    {
        $user = $this->app->auth()->user();
        if (!$user || $this->getSetting('notifications_enabled', '1') !== '1') {
            $this->json(['notifications' => []]);
            return '';
        }
        $blocked = $this->layoutService()->getBlockedUserIds((int)$user->id);
        $list = Notification::where('user_id', $user->id)->whereNull('read_at')->where('type', '!=', 'message')->orderByDesc('id')->limit(20)->get(['id', 'type', 'data', 'created_at', 'sender_user_id']);
        $out = [];
        $typeLabels = [
            'mention' => lang('api.notification_mention'),
            'quote' => lang('api.notification_quote'),
            'reply' => lang('api.notification_reply'),
            'subscribed_topic_reply' => lang('api.notification_subscribed_topic_reply'),
            'reaction' => lang('api.notification_reaction'),
            'reputation' => lang('api.notification_reputation'),
            'report' => lang('api.notification_report'),
            'announcement' => lang('api.notification_announcement'),
            'private_topic_added' => lang('api.notification_private_topic_added'),
            'pm_undeliverable' => lang('api.notification_pm_undeliverable'),
        ];
        foreach ($list as $n) {
            $data = is_string($n->data ?? '') ? json_decode($n->data, true) : (is_array($n->data ?? null) ? $n->data : []);
            $data = is_array($data) ? $data : [];
            $fromUid = $this->notificationSenderUserId($n, $data);
            if ($fromUid && in_array($fromUid, $blocked, true)) {
                continue;
            }
            $url = $data['url'] ?? core_url('');
            $label = $data['message'] ?? ($typeLabels[$n->type ?? ''] ?? ($n->type === 'announcement' ? ($data['label'] ?? lang('api.notification_announcement')) : $n->type));
            $out[] = [
                'id' => (int)$n->id,
                'type' => $n->type,
                'label' => $label,
                'from_username' => $data['from_username'] ?? '',
                'url' => $url,
                'read_url' => core_url('notifications/' . $n->id . '/read'),
                'created_at' => $n->created_at instanceof \DateTimeInterface ? $n->created_at->format('Y-m-d H:i:s') : $n->created_at,
            ];
        }
        $this->json(['notifications' => $out]);
        return '';
    }

    /**
     * SSE stream: yeni bildirimler gerçek zamanlı push (paylaşımlı hosting uyumlu).
     * Bağlantı ~24 sn açık kalır, yeni kayıt gelirse event gönderilir ve bağlantı kapanır (client yeniden bağlanır).
     */
    public function sseStream(): never
    {
        $user = $this->app->auth()->user();
        if (!$user || $this->getSetting('notifications_enabled', '1') !== '1') {
            $this->json(['error' => 'Unauthorized'], 401);
            exit;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (ob_get_level()) {
            while (ob_get_level()) {
                ob_end_clean();
            }
        }
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        if (function_exists('set_time_limit')) {
            @set_time_limit(35);
        }
        $lastId = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);
        $userId = (int) $user->id;
        $blocked = $this->layoutService()->getBlockedUserIds($userId);
        $maxIterations = 12;
        $sleepSeconds = 2;
        for ($i = 0; $i < $maxIterations; $i++) {
            if (connection_aborted()) {
                exit;
            }
            $newOnes = Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->where('type', '!=', 'message')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(10)
                ->get(['id', 'type', 'data', 'created_at', 'sender_user_id']);
            $out = [];
            foreach ($newOnes as $n) {
                $data = is_string($n->data ?? '') ? json_decode($n->data, true) : (is_array($n->data ?? null) ? $n->data : []);
                $data = is_array($data) ? $data : [];
                $fromUid = $this->notificationSenderUserId($n, $data);
                if ($fromUid && in_array($fromUid, $blocked, true)) {
                    continue;
                }
                $out[] = $this->formatNotificationForSse($n);
                $lastId = max($lastId, (int) $n->id);
            }
            if ($out !== []) {
                echo 'event: notification' . "\n";
                echo 'id: ' . $lastId . "\n";
                echo 'data: ' . json_encode(['notifications' => $out], JSON_UNESCAPED_UNICODE) . "\n\n";
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();
                exit;
            }
            echo ": ping\n\n";
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            flush();
            sleep($sleepSeconds);
        }
        exit;
    }

    /** @return array<string, mixed> */
    private function formatNotificationForSse(Notification $n): array
    {
        $typeLabels = [
            'mention' => lang('api.notification_mention'),
            'quote' => lang('api.notification_quote'),
            'reply' => lang('api.notification_reply'),
            'subscribed_topic_reply' => lang('api.notification_subscribed_topic_reply'),
            'reaction' => lang('api.notification_reaction'),
            'reputation' => lang('api.notification_reputation'),
            'report' => lang('api.notification_report'),
            'announcement' => lang('api.notification_announcement'),
            'private_topic_added' => lang('api.notification_private_topic_added'),
            'pm_undeliverable' => lang('api.notification_pm_undeliverable'),
        ];
        $data = is_string($n->data ?? '') ? json_decode($n->data, true) : (is_array($n->data ?? null) ? $n->data : []);
        $data = is_array($data) ? $data : [];
        $label = $data['message'] ?? ($typeLabels[$n->type ?? ''] ?? ($n->type === 'announcement' ? ($data['label'] ?? lang('api.notification_announcement')) : (string)($n->type ?? '')));
        return [
            'id' => (int) $n->id,
            'type' => $n->type,
            'label' => $label,
            'from_username' => $data['from_username'] ?? '',
            'url' => $data['url'] ?? core_url(''),
            'read_url' => core_url('notifications/' . $n->id . '/read'),
            'created_at' => $n->created_at instanceof \DateTimeInterface ? $n->created_at->format('Y-m-d H:i:s') : (string) $n->created_at,
        ];
    }

    /** Hide dismissible announcement for user (announcement_dismissals) and guest (cookie). */
    public function announcementDismiss(): string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => lang('api.invalid_request')], 405);
            return '';
        }

        if (!core_csrf_valid('api_announcement_dismiss', (string)($_POST['_token'] ?? ''))) {
            $this->json(['ok' => false, 'error' => lang('api.csrf_invalid') ?: 'Invalid CSRF token.'], 403);
            return '';
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'error' => lang('api.invalid_announcement')]);
            return '';
        }

        try {
            $announcement = \App\Models\Announcement::where('id', $id)->where('is_active', 1)->first(['id', 'is_dismissible']);
            if (!$announcement || empty($announcement->is_dismissible)) {
                $this->json(['ok' => false, 'error' => lang('api.announcement_cannot_dismiss')]);
                return '';
            }

            // Set cookie for guests and users
            $cookieName = 'dismissed_announcements';
            $currentRaw = $_COOKIE[$cookieName] ?? '';
            $current = $currentRaw !== '' ? explode(',', $currentRaw) : [];
            if (!in_array((string)$id, $current, true)) {
                $current[] = $id;
            }
            // 1 year
            setcookie($cookieName, implode(',', $current), time() + 86400 * 365, '/');

            $user = $this->app->auth()->user();
            if ($user) {
                \App\Models\AnnouncementDismissal::firstOrCreate(
                    ['user_id' => (int) $user->id, 'announcement_id' => $id],
                    ['dismissed_at' => \now()]
                );
                \App\Models\Announcement::clearCache();
            }

            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => lang('api.operation_failed')], 500);
        }
        return '';
    }

    public function hoverUser(): string
    {
        $username = trim((string)($_GET['username'] ?? ''));
        $username = ltrim($username, '@');
        if ($username === '') {
            $this->json(['error' => lang('api.invalid_request')], 400);
            return '';
        }

        $baseQuery = User::query()
            ->with('role')
            ->withCount(['posts' => fn ($q) => $q->whereNull('deleted_at')])
            ->where('is_banned', 0);

        $user = (clone $baseQuery)
            ->where('username', $username)
            ->first();

        if (!$user) {
            $user = (clone $baseQuery)
                ->whereRaw('LOWER(username) = LOWER(?)', [$username])
                ->first();
        }

        if (!$user) {
            $this->json(['error' => lang('api.user_not_found')], 404);
            return '';
        }

        $joinedAt = '';
        if ($user->created_at instanceof \DateTimeInterface) {
            $joinedAt = $user->created_at->format('d M Y');
        } elseif (!empty($user->created_at)) {
            $joinedAt = date('d M Y', strtotime((string) $user->created_at));
        }

        $avatarUrl = $user->avatar_path ? asset_url($user->avatar_path) : ('https://ui-avatars.com/api/?name=' . urlencode($user->username ?? 'User') . '&size=80');
        $this->json([
            'id' => (int)$user->id,
            'username' => $user->username,
            'avatar_url' => $avatarUrl,
            'role_name' => $user->role ? $user->role->name : null,
            'role_color' => $user->role ? $user->role->color : null,
            'post_count' => (int)$user->posts_count,
            'joined_at' => $joinedAt,
        ]);
        return '';
    }

    public function hoverPost(): string
    {
        $topicId = (int)($_GET['topic_id'] ?? 0);
        $pos = (int)($_GET['pos'] ?? 0);
        $postId = (int)($_GET['id'] ?? 0);

        $viewer = $this->app->auth()->user();
        $viewerId = $viewer ? (int) $viewer->id : null;
        $isStaff = $viewer && $viewer->role && $viewer->role->is_staff;

        $post = null;
        if ($topicId > 0 && $pos > 0) {
            $post = \App\Models\Post::with(['user', 'topic' => fn ($q) => $q->whereNull('deleted_at')])
                ->where('topic_id', $topicId)
                ->whereNull('deleted_at')
                ->whereHas('topic', fn ($q) => $q->visibleToUserWithPrivacy($viewerId, $isStaff)->whereNull('deleted_at'))
                ->orderBy('id')->offset($pos - 1)->limit(1)->first();
        } elseif ($postId > 0) {
            $post = \App\Models\Post::with(['user', 'topic' => fn ($q) => $q->whereNull('deleted_at')])
                ->where('id', $postId)
                ->whereNull('deleted_at')
                ->whereHas('topic', fn ($q) => $q->visibleToUserWithPrivacy($viewerId, $isStaff)->whereNull('deleted_at'))
                ->first();
        }
        if (!$post || !$post->topic) {
            $this->json(['error' => $post ? lang('api.post_not_found') : lang('api.invalid_request')], $post ? 404 : 400);
            return '';
        }
        $u = $post->user;
        $avatarUrl = $u && $u->avatar_path ? asset_url($u->avatar_path) : ('https://ui-avatars.com/api/?name=' . urlencode($u->username ?? 'User') . '&size=80');
        $snippet = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($post->body_html ?? ''))), 0, 120);
        if (mb_strlen($snippet) >= 120) {
            $snippet .= '...';
        }
        $this->json([
            'id' => (int)$post->id,
            'username' => $u ? $u->username : '',
            'avatar_url' => $avatarUrl,
            'topic_title' => $post->topic->title ?? '',
            'snippet' => $snippet,
            'created_at' => $post->created_at ? $post->created_at->format('d M Y, H:i') : '',
        ]);
        return '';
    }

    /** Portal tabbed table: load more data via AJAX (offset/limit). */
    public function portalTab(): string
    {
        $tab = trim((string)($_GET['tab'] ?? ''));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 15)));
        $allowed = ['newest_topics', 'popular_users', 'most_viewed', 'most_replied', 'top_replied', 'top_viewed'];
        if (!in_array($tab, $allowed, true)) {
            $this->json(['items' => [], 'has_more' => false]);
            return '';
        }
        $items = $this->fetchPortalTabData($tab, $offset, $limit);
        $items = $this->portalTabItemsToArray($items, $tab);
        $tabLimit = max(5, min(50, (int)$this->getSetting('portal_tab_limit', '15')));
        $tabMax = max($tabLimit, min(50, (int)$this->getSetting('portal_tab_max', '50')));
        $hasMore = count($items) >= $limit && ($offset + $limit) < $tabMax;
        $this->json(['items' => $items, 'has_more' => $hasMore]);
        return '';
    }

    protected function fetchPortalTabData(string $tab, int $offset, int $limit): array
    {
        $forumIdsJson = $this->getSetting('portal_forum_ids', '[]');
        $forumIds = $forumIdsJson !== '' ? json_decode($forumIdsJson, true) : [];
        $forumFilter = is_array($forumIds) && !empty($forumIds);
        $viewer = $this->app->auth()->user();
        $viewerId = $viewer ? (int) $viewer->id : null;
        $isStaff = $viewer && $viewer->role && $viewer->role->is_staff;

        if ($tab === 'popular_users') {
            $users = User::withCount(['posts' => fn ($q) => $q->whereNull('deleted_at')])
                ->withCount(['topics' => fn ($q) => $q->whereNull('deleted_at')->where(fn ($q2) => $q2->whereIn('type', $this->getTopicListTypes())->orWhereNull('type'))])
                ->where('is_banned', 0)->having('posts_count', '>', 0)
                ->orderByDesc('posts_count')->orderBy('id')->offset($offset)->limit($limit)->get();
            $out = [];
            foreach ($users as $u) {
                $row = (object)[
                    'id' => $u->id, 'username' => $u->username, 'avatar_path' => $u->avatar_path, 'created_at' => $u->created_at?->format('Y-m-d H:i:s'),
                    'post_count' => $u->posts_count, 'topic_count' => $u->topics_count ?? 0,
                ];
                $row->reputation_positive = (int)($u->reputation_positive ?? 0);
                $row->reputation_negative = (int)($u->reputation_negative ?? 0);
                $row->reputation_net = $row->reputation_positive - $row->reputation_negative;
                $row->location = $u->location ?? null;
                $out[] = $row;
            }
            return $out;
        }

        $topicQuery = \App\Models\Topic::visibleToUserWithPrivacy($viewerId, $isStaff)->with(['user', 'lastPostUser', 'forum'])
            ->whereIn(DB::raw('COALESCE(type,\'topic\')'), $this->getTopicListTypes())->whereNull('deleted_at');
        if ($forumFilter) {
            $topicQuery->whereIn('forum_id', $forumIds);
        }
        if ($tab === 'newest_topics') {
            $topics = $topicQuery->orderByDesc('created_at')->orderByDesc('id')->offset($offset)->limit($limit)->get();
        } elseif ($tab === 'most_viewed') {
            $topics = $topicQuery->orderByRaw('(view_count + reply_count) DESC')->orderByDesc('id')->offset($offset)->limit($limit)->get();
        } elseif ($tab === 'most_replied') {
            $topics = $topicQuery->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('posts')->whereColumn('posts.topic_id', 'topics.id')->where('posts.is_first_post', 0)->whereNull('posts.deleted_at');
            })->orderByDesc('last_post_at')->orderByDesc('id')->offset($offset)->limit($limit)->get();
        } elseif ($tab === 'top_replied') {
            $topics = $topicQuery->orderByDesc('reply_count')->orderByDesc('id')->offset($offset)->limit($limit)->get();
        } elseif ($tab === 'top_viewed') {
            $topics = $topicQuery->orderByDesc('view_count')->orderByDesc('id')->offset($offset)->limit($limit)->get();
        } else {
            return [];
        }
        $out = [];
        foreach ($topics as $t) {
            $out[] = (object)[
                'id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count,
                'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'last_post_at' => $t->last_post_at?->format('Y-m-d H:i:s'),
                'username' => $t->user?->username, 'author_avatar_path' => $t->user?->avatar_path,
                'last_post_username' => $t->lastPostUser?->username, 'last_post_avatar_path' => $t->lastPostUser?->avatar_path,
                'forum_name' => $t->forum?->name,
            ];
        }
        return $out;
    }

    protected function portalTabItemsToArray(array $items, string $tab): array
    {
        $out = [];
        foreach ($items as $row) {
            $arr = (array) $row;
            if (isset($arr['avatar_path']) && $arr['avatar_path'] !== null && $arr['avatar_path'] !== '') {
                $arr['avatar_url'] = asset_url($arr['avatar_path']);
            } else {
                $arr['avatar_url'] = function_exists('avatar_fallback_url')
                    ? avatar_fallback_url($arr['username'] ?? null, 64)
                    : '';
            }
            if (isset($arr['author_avatar_path']) && $arr['author_avatar_path'] !== null && $arr['author_avatar_path'] !== '') {
                $arr['author_avatar_url'] = asset_url($arr['author_avatar_path']);
            } else {
                $arr['author_avatar_url'] = function_exists('avatar_fallback_url')
                    ? avatar_fallback_url($arr['username'] ?? null, 64)
                    : '';
            }
            $out[] = $arr;
        }
        return $out;
    }

    /** Editor için smiley listesi (Unicode + GIF). */
    public function smileys(): string
    {
        try {
            if (!SmileyHelper::isEnabled()) {
                $this->json(['unicode' => [], 'gifs' => []]);
                return '';
            }
            $this->json([
                'unicode' => SmileyHelper::listUnicode(),
                'gifs' => SmileyHelper::listGifs(),
            ]);
        } catch (\Throwable $e) {
            $this->json(['unicode' => [], 'gifs' => []]);
        }
        return '';
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function topicViewerPing(): string
    {
        $topicId = (int) ($_POST['topic_id'] ?? 0);
        $token   = trim((string) ($_POST['token'] ?? ''));
        if ($topicId <= 0 || $token === '' || strlen($token) > 64) {
            $this->json(['ok' => false]);
            return '';
        }
        $token = (string) preg_replace('/[^a-zA-Z0-9_-]/', '', $token);
        if ($token === '') {
            $this->json(['ok' => false]);
            return '';
        }
        $user   = $this->app->auth()->user();
        $userId = $user ? (int) $user->id : null;
        $now    = date('Y-m-d H:i:s');
        $stale  = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        try {
            DB::table('topic_active_viewers')
                ->where('topic_id', $topicId)
                ->where('last_seen_at', '<', $stale)
                ->delete();
            $exists = DB::table('topic_active_viewers')
                ->where('topic_id', $topicId)
                ->where('session_token', $token)
                ->exists();
            if ($exists) {
                DB::table('topic_active_viewers')
                    ->where('topic_id', $topicId)
                    ->where('session_token', $token)
                    ->update(['user_id' => $userId, 'last_seen_at' => $now]);
            } else {
                DB::table('topic_active_viewers')->insert([
                    'topic_id'      => $topicId,
                    'user_id'       => $userId,
                    'session_token' => $token,
                    'last_seen_at'  => $now,
                ]);
            }
        } catch (\Throwable) {}
        $this->json(['ok' => true]);
        return '';
    }

    public function topicViewers(): string
    {
        $topicId = (int) ($_GET['topic_id'] ?? 0);
        if ($topicId <= 0) {
            $this->json(['members' => [], 'member_count' => 0, 'guest_count' => 0, 'total' => 0]);
            return '';
        }
        $threshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
        try {
            $rows = DB::table('topic_active_viewers')
                ->where('topic_id', $topicId)
                ->where('last_seen_at', '>=', $threshold)
                ->get(['user_id', 'session_token']);
            $memberIds  = [];
            $guestCount = 0;
            foreach ($rows as $row) {
                if ($row->user_id !== null) {
                    $memberIds[] = (int) $row->user_id;
                } else {
                    $guestCount++;
                }
            }
            $memberIds = array_values(array_unique($memberIds));
            $members   = [];
            if (!empty($memberIds)) {
                $users = DB::table('users')
                    ->whereIn('id', $memberIds)
                    ->select('id', 'username', 'avatar_path')
                    ->get();
                foreach ($users as $u) {
                    $members[] = [
                        'id'          => (int) $u->id,
                        'username'    => $u->username,
                        'url'         => core_url('member/' . rawurlencode((string) ($u->username ?? ''))),
                        'avatar_url'  => $u->avatar_path
                            ? asset_url((string) $u->avatar_path)
                            : ('https://ui-avatars.com/api/?name=' . urlencode((string) ($u->username ?? 'U')) . '&size=64'),
                    ];
                }
            }
            $this->json([
                'members'      => $members,
                'member_count' => count($members),
                'guest_count'  => $guestCount,
                'total'        => count($members) + $guestCount,
            ]);
        } catch (\Throwable) {
            $this->json(['members' => [], 'member_count' => 0, 'guest_count' => 0, 'total' => 0]);
        }
        return '';
    }

    /** @param array<string, mixed> $data */
    private function notificationSenderUserId(Notification $n, array $data): int
    {
        $sid = (int) ($n->getAttribute('sender_user_id') ?? 0);
        if ($sid > 0) {
            return $sid;
        }

        return (int) ($data['from_user_id'] ?? 0);
    }
}
