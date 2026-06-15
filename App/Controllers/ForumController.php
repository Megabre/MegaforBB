<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\TopicRead;
use App\Services\TopicPrefixScopeService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Forum (forum under category) page: topic list.
 */
class ForumController extends BaseController
{
    protected \App\Services\SecurityService $security;

    public function __construct(\Forecor\Core\Application $app, \App\Services\SecurityService $security)
    {
        parent::__construct($app);
        $this->security = $security;
    }

    /** Alt forum: /forum/ust-slug/alt-slug — üst foruma göre tek anlamlı eşleme. */
    public function showSubforum(string $parentSlug, string $childSlug): string
    {
        $parent = Forum::where('slug', $parentSlug)->whereNull('parent_id')->first();
        if (!$parent) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $forum = Forum::with(['parent', 'subforums' => fn ($q) => $q->with(['lastPostUser', 'lastPost.topic'])])
            ->where('slug', $childSlug)->where('parent_id', $parent->id)
            ->select(['id', 'name', 'slug', 'description', 'topic_count', 'post_count', 'category_id', 'parent_id'])->first();
        if (!$forum) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        return $this->renderForumDisplay($forum);
    }

    public function show(string $slug): string
    {
        $forum = Forum::with(['parent', 'subforums' => fn ($q) => $q->with(['lastPostUser', 'lastPost.topic'])])
            ->where('slug', $slug)->whereNull('parent_id')
            ->select(['id', 'name', 'slug', 'description', 'topic_count', 'post_count', 'category_id', 'parent_id'])->first();
        if (!$forum) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        return $this->renderForumDisplay($forum);
    }

    private function renderForumDisplay(Forum $forum): string
    {
        $user = $this->app->auth()->user();
        if ($user && !$user->hasPermission('forum.view', $forum)) {
            http_response_code(403);
            return $this->layout('403', ['pageTitle' => lang('error.forbidden'), 'message' => lang('error.no_forum_access') ?? 'Bu foruma erişim yetkiniz yok.'], false);
        }
        $userId = $user ? (int)$user->id : 0;
        $userRid = $user ? (int)($user->role_id ?? 0) : 0;
        $stats = $this->layoutService()->getStats();
        $period = isset($_GET['period']) ? trim((string) $_GET['period']) : '';
        $sort = isset($_GET['sort']) ? trim((string) $_GET['sort']) : 'newest';
        if (!in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }
        $forumIdsForTopics = [(int) $forum->id];
        if ($forum->subforums && $forum->subforums->isNotEmpty()) {
            foreach ($forum->subforums as $sub) {
                $forumIdsForTopics[] = (int) $sub->id;
            }
        }
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));
        $topicsData = $this->getTopics($forumIdsForTopics, $userId, $period, $sort, $currentPage);
        $topics = $topicsData['topics'];
        $pagination = [
            'page' => $topicsData['page'],
            'per_page' => $topicsData['per_page'],
            'total' => $topicsData['total'],
            'total_pages' => $topicsData['total_pages'],
        ];
        $topicsIncludeParentFallback = false;
        $currentForumId = (int) $forum->id;
        if ($forum->parent_id && $topics->isEmpty()) {
            $idsWithParent = array_unique(array_merge($forumIdsForTopics, [(int) $forum->parent_id]));
            $topicsWithParentData = $this->getTopics($idsWithParent, $userId, $period, $sort, $currentPage);
            $topicsWithParent = $topicsWithParentData['topics'];
            $topics = $topicsWithParent->filter(fn ($t) => (int) ($t->forum_id ?? 0) === $currentForumId)->values();
            if ($topics->isEmpty() && $topicsWithParent->isNotEmpty()) {
                $topics = $topicsWithParent;
                $topicsIncludeParentFallback = true;
            }
            $pagination = [
                'page' => $topicsWithParentData['page'],
                'per_page' => $topicsWithParentData['per_page'],
                'total' => $topicsWithParentData['total'],
                'total_pages' => $topicsWithParentData['total_pages'],
            ];
        }
        $parentSlugForSubs = $forum->slug ?? '';
        $subforumsForView = $forum->subforums ? $forum->subforums->map(fn ($s) => (object)[
            'id' => $s->id, 'name' => $s->name, 'slug' => $s->slug, 'description' => $s->description,
            'topic_count' => $s->topic_count, 'post_count' => $s->post_count,
            'parent_slug' => $parentSlugForSubs,
            'last_post_at' => $s->last_post_at?->format('Y-m-d H:i:s'), 'last_post_username' => $s->lastPostUser?->username ?? null,
            'last_post_topic_title' => $s->lastPost?->topic?->title ?? null, 'last_post_topic_slug' => $s->lastPost?->topic?->slug ?? null,
        ])->all() : [];
        $parentForum = $forum->parent ? (object)['id' => $forum->parent->id, 'name' => $forum->parent->name, 'slug' => $forum->parent->slug] : null;

        $topics = $topics->map(function ($t) {
            if (($t->status ?? '') === Topic::STATUS_SCHEDULED && $t->scheduled_publish_at) {
                $t->scheduled_remaining_text = $this->formatScheduledRemaining($t->scheduled_publish_at);
            }
            return $t;
        });

        $viewerTopicIds = [];
        if ($userId > 0) {
            $topicIds = $topics->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (!empty($topicIds)) {
                $viewerTopicIds = \App\Models\TopicPrivateViewer::where('user_id', $userId)
                    ->whereIn('topic_id', $topicIds)
                    ->pluck('topic_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }
        $topics = $topics->filter(function ($t) use ($userId, $userRid, $viewerTopicIds) {
            if (!$t->is_private) {
                return true;
            }
            return $userId === (int)($t->user_id ?? 0) || $userRid === 1 || $userRid === 2
                || in_array((int)$t->id, $viewerTopicIds, true);
        });
        if ($user) {
            $blocked = $this->layoutService()->getBlockedUserIds((int)$user->id);
            if (!empty($blocked)) {
                $topics = $topics->filter(fn ($t) => !in_array((int)($t->user_id ?? 0), $blocked, true))->values();
            }
            $topicIds = $topics->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach ($topics as $t) {
                $t->viewer_has_replied = false;
            }
            if (!empty($topicIds)) {
                try {
                    $readMap = TopicRead::where('user_id', (int) $user->id)
                        ->whereIn('topic_id', $topicIds)
                        ->get()
                        ->keyBy('topic_id')
                        ->map(fn ($r) => $r->last_read_at instanceof \DateTimeInterface ? $r->last_read_at->format('Y-m-d H:i:s') : $r->last_read_at)
                        ->all();
                    foreach ($topics as $t) {
                        $tid = (int) $t->id;
                        if (!isset($readMap[$tid])) {
                            $t->is_unread = true;
                        } else {
                            $lastPost = $t->last_post_at ?? $t->created_at;
                            $t->is_unread = strtotime((string) $lastPost) > strtotime((string) $readMap[$tid]);
                        }
                    }
                } catch (\Throwable $e) {
                    // topic_reads table may not exist yet
                }
                try {
                    $repliedIds = Post::query()
                        ->where('user_id', (int) $user->id)
                        ->whereIn('topic_id', $topicIds)
                        ->where('is_first_post', 0)
                        ->distinct()
                        ->pluck('topic_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                    $repliedSet = array_fill_keys($repliedIds, true);
                    foreach ($topics as $t) {
                        if (isset($repliedSet[(int) $t->id])) {
                            $t->viewer_has_replied = true;
                        }
                    }
                } catch (\Throwable $e) {
                }
            }
        }
        $forumUrls = $this->getForumUrls($forum);
        $paginationBase = $forumUrls['display'];
        $paginationQuery = [];
        if ($period !== '') {
            $paginationQuery['period'] = $period;
        }
        if ($sort !== 'newest') {
            $paginationQuery['sort'] = $sort;
        }
        if (!empty($paginationQuery)) {
            $paginationBase .= '?' . http_build_query($paginationQuery) . '&';
        } else {
            $paginationBase .= '?';
        }
        return $this->layout('forum_display', [
            'forum' => $forum,
            'subforums' => $subforumsForView,
            'parent_forum' => $parentForum,
            'forum_url' => $forumUrls['display'],
            'forum_new_topic_url' => $forumUrls['new_topic'],
            'stats' => $stats,
            'topics' => $topics->all(),
            'topics_include_parent_fallback' => $topicsIncludeParentFallback,
            'pageTitle' => $forum->name,
            'newContentModalForum' => $forum,
            'topic_period' => $period,
            'topic_sort' => $sort,
            'pagination' => $pagination,
            'pagination_base' => $paginationBase,
        ], false);
    }

    /**
     * @param int|int[] $forumIdOrIds Tek forum id veya forum id listesi (üst forum + alt forumlar)
     */
    protected function getTopics($forumIdOrIds, ?int $currentUserId = null, string $period = '', string $sort = 'newest', int $page = 1): array
    {
        $perPage = (int) $this->getSetting('topics_per_page', '20');
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $ids = is_array($forumIdOrIds) ? $forumIdOrIds : [(int) $forumIdOrIds];
        $ids = array_filter(array_map('intval', $ids));

        $query = Topic::visibleToUser($currentUserId)->with(['user', 'lastPostUser', 'prefix'])
            ->whereIn('forum_id', $ids)
            ->where(function ($q) {
                $q->whereIn('type', $this->getTopicListTypes())->orWhereNull('type');
            })
            ->whereNull('deleted_at');

        $allowedPeriods = ['24h' => 86400, '3d' => 259200, '7d' => 604800, '1m' => 2592000];
        if ($period !== '' && isset($allowedPeriods[$period])) {
            $since = date('Y-m-d H:i:s', time() - $allowedPeriods[$period]);
            $query->where('created_at', '>=', $since);
        }

        $orderDir = ($sort === 'oldest') ? 'asc' : 'desc';
        $total = (int) (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $topics = $query->orderBy('is_sticky', 'desc')
            ->orderBy('created_at', $orderDir)
            ->orderBy('id', $orderDir)
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return [
            'topics' => $topics,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
        ];
    }

    /** Planlanan yayın için kalan süreyi metne çevirir (örn. "2 gün 5 saat", "45 dakika"). */
    protected function formatScheduledRemaining($scheduledAt): string
    {
        $ts = $scheduledAt instanceof \DateTimeInterface ? $scheduledAt->getTimestamp() : strtotime((string) $scheduledAt);
        $diff = $ts - time();
        if ($diff <= 0) {
            return core__('topic_create.scheduled_any_moment') ?: 'çok yakında';
        }
        $days = (int) floor($diff / 86400);
        $hours = (int) floor(($diff % 86400) / 3600);
        $minutes = (int) floor(($diff % 3600) / 60);
        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ' . (core__('topic_create.scheduled_days') ?: 'gün');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ' . (core__('topic_create.scheduled_hours') ?: 'saat');
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes . ' ' . (core__('topic_create.scheduled_minutes') ?: 'dk');
        }
        return implode(' ', array_slice($parts, 0, 3));
    }

    public function markAllRead(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('mark_all_read', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('forum'));
            return '';
        }
        try {
            $currentUserId = $user ? (int) $user->id : null;
            $isStaff = $user && $user->role && $user->role->is_staff;
            $topicIds = Topic::visibleToUserWithPrivacy($currentUserId, $isStaff)
                ->whereNull('deleted_at')
                ->pluck('id');
            // updateOrCreate/upsert-style logic with plain Eloquent
            // performanslı olmayabilir ancak Laravel query builder kullanılabilir veya
            // DB/Capsule could be added; prefer Capsule for reads:
            if ($topicIds->isNotEmpty()) {
                $now = \now();
                $data = $topicIds->map(fn ($id) => [
                    'user_id' => $user->id,
                    'topic_id' => $id,
                    'last_read_at' => $now
                ])->toArray();

                \Illuminate\Database\Capsule\Manager::table('topic_reads')->upsert(
                    $data,
                    ['user_id', 'topic_id'],
                    ['last_read_at']
                );
            }
        } catch (\Throwable $e) {
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? core_url('forum');
        $this->redirect($referer);
        return '';
    }

    public function createTopic(string $slug): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('auth_error', core__('auth.login_required'));
            $this->redirect(core_url('login'));
            return '';
        }

        $forum = Forum::where('slug', $slug)->whereNull('parent_id')->select(['id', 'name', 'slug', 'category_id', 'parent_id'])->first();

        $parentSlug = null;
        $childSlug = null;
        if ($forum) {
            $parentSlug = null;
            $childSlug = null;
        } else {
            $forum = Forum::where('slug', $slug)->select(['id', 'name', 'slug', 'category_id', 'parent_id'])->first();
            if ($forum && $forum->parent_id) {
                $parent = Forum::where('id', $forum->parent_id)->first();
                if ($parent) {
                    $parentSlug = $parent->slug;
                    $childSlug = $forum->slug;
                }
            }
        }

        if (!$forum) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if (!$user->hasPermission('forum.create_thread', $forum)) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('error.no_permission') ?? 'Yeni konu açma yetkiniz yok.');
            $redirectUrl = $parentSlug ? core_url('forum/' . $parentSlug . '/' . $childSlug) : core_url('forum/' . $forum->slug);
            $this->redirect($redirectUrl);
            return '';
        }

        return $this->renderCreateTopicForm($forum, $parentSlug, $childSlug);
    }

    public function createTopicSubforum(string $parentSlug, string $childSlug): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('auth_error', core__('auth.login_required'));
            $this->redirect(core_url('login'));
            return '';
        }
        $parent = Forum::where('slug', $parentSlug)->whereNull('parent_id')->first();
        if (!$parent) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $forum = Forum::where('slug', $childSlug)->where('parent_id', $parent->id)->first();
        if (!$forum) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if (!$user->hasPermission('forum.create_thread', $forum)) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('error.no_permission') ?? 'Yeni konu açma yetkiniz yok.');
            $this->redirect(core_url('forum/' . $parentSlug . '/' . $childSlug));
            return '';
        }
        return $this->renderCreateTopicForm($forum, $parentSlug, $childSlug);
    }

    private function renderCreateTopicForm(Forum $forum, ?string $parentSlug, ?string $childSlug): string
    {
        $prefixes = TopicPrefixScopeService::prefixesForForum($forum);
        $error = $this->app->session()->getFlashBag()->get('topic_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $type = isset($_GET['type']) ? trim((string) $_GET['type']) : null;
        $allowedTypes = $this->app->hooks()->applyFilters('topic_create_allowed_types', ['topic', 'article', 'poll', 'question'], $forum);
        $preselectedType = $type && in_array($type, $allowedTypes, true) ? $type : null;
        $forumUrl = $parentSlug !== null ? core_url('forum/' . $parentSlug . '/' . $childSlug) : core_url('forum/' . $forum->slug);
        $forumNewTopicUrl = $parentSlug !== null ? core_url('forum/' . $parentSlug . '/' . $childSlug . '/new-topic') : core_url('forum/' . $forum->slug . '/new-topic');
        return $this->layout('topics/create', [
            'forum' => $forum,
            'forum_url' => $forumUrl,
            'forum_new_topic_url' => $forumNewTopicUrl,
            'prefixes' => $prefixes,
            'pageTitle' => core__('forum.new_topic') . ': ' . $forum->name,
            'error' => $error,
            'preselectedType' => $preselectedType,
            'allowedTypes' => $allowedTypes,
        ], false);
    }

    public function storeTopic(string $slug): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $forum = Forum::where('slug', $slug)->whereNull('parent_id')->first();
        if (!$forum) {
            $forum = Forum::where('slug', $slug)->first();
        }
        if (!$forum) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        return $this->storeTopicWithForum($forum, $user);
    }

    public function storeTopicSubforum(string $parentSlug, string $childSlug): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $parent = Forum::where('slug', $parentSlug)->whereNull('parent_id')->first();
        if (!$parent) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $forum = Forum::where('slug', $childSlug)->where('parent_id', $parent->id)->first();
        if (!$forum) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        return $this->storeTopicWithForum($forum, $user);
    }

    /** Forum için görüntüleme ve new-topic URL'leri (üst forum / alt forum farkı). */
    private function getForumUrls(Forum $forum): array
    {
        if ($forum->parent_id) {
            $parent = Forum::where('id', $forum->parent_id)->first();
            if ($parent) {
                $base = 'forum/' . $parent->slug . '/' . $forum->slug;
                return ['display' => core_url($base), 'new_topic' => core_url($base . '/new-topic')];
            }
        }
        $base = 'forum/' . $forum->slug;
        return ['display' => core_url($base), 'new_topic' => core_url($base . '/new-topic')];
    }

    private function storeTopicWithForum(Forum $forum, $user): string
    {
        $urls = $this->getForumUrls($forum);
        if (!core_csrf_valid('new_topic', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect($urls['new_topic']);
            return '';
        }
        $sec = $this->app->security();
        $ip = \App\Services\SecurityService::clientIp();
        $r = $sec->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_NEW_TOPIC, (int) $user->id, $ip);
        if (!$r['allowed']) {
            $this->app->session()->getFlashBag()->add('topic_error', $r['message']);
            $this->redirect($urls['new_topic']);
            return '';
        }
        if (!$user->hasPermission('forum.create_thread', $forum)) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('error.no_permission') ?? 'Yeni konu açma yetkiniz yok.');
            $this->redirect($urls['display']);
            return '';
        }
        $maxTitleLen = (int) $this->getSetting('max_topic_title_length', '200');
        $maxPostLen = (int) $this->getSetting('max_post_length', '0');

        $request = new \App\Http\Requests\Topic\StoreTopicRequest($maxTitleLen, $maxPostLen);

        if (!$request->validate()) {
            $this->app->session()->getFlashBag()->add('topic_error', $request->firstError());
            $this->redirect($urls['new_topic']);
            return '';
        }

        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));

        $prefixId = (int)($_POST['prefix_id'] ?? 0);
        $isPrivate = isset($_POST['is_private']) && $_POST['is_private'] === '1' ? 1 : 0;
        $scheduledAt = null;
        $scheduledRaw = trim((string)($_POST['scheduled_publish_at'] ?? ''));
        if ($scheduledRaw !== '') {
            $ts = strtotime($scheduledRaw);
            if ($ts !== false && $ts > time()) {
                $scheduledAt = date('Y-m-d H:i:s', $ts);
            }
        }
        $isScheduled = $scheduledAt !== null;
        $topicTypeInput = isset($_POST['topic_type']) ? trim((string) $_POST['topic_type']) : '';
        $allowedTypes = $this->app->hooks()->applyFilters('topic_create_allowed_types', ['topic', 'article', 'poll', 'question'], $forum);
        $topicType = $topicTypeInput && in_array($topicTypeInput, $allowedTypes, true) ? $topicTypeInput : 'topic';

        $censorship = $this->app->censorship();
        if ($censorship->isCensorshipEnabled()) {
            if ($censorship->applyToTopicTitles()) {
                $titleCheck = $censorship->checkContent($title);
                if (!$titleCheck['allowed']) {
                    $this->app->session()->getFlashBag()->add('topic_error', lang('censorship.content_blocked'));
                    $this->redirect($urls['new_topic']);
                    return '';
                }
                $title = $titleCheck['filtered_text'];
            }
            if ($censorship->applyToPosts()) {
                $bodyCheck = $censorship->checkContent($body);
                if (!$bodyCheck['allowed']) {
                    $this->app->session()->getFlashBag()->add('topic_error', lang('censorship.content_blocked'));
                    $this->redirect($urls['new_topic']);
                    return '';
                }
                $body = $bodyCheck['filtered_text'];
            }
        }
        $role = $user->role;
        if ($role && $role->daily_topic_limit > 0) {
            $todayStart = \now()->startOfDay()->format('Y-m-d H:i:s');
            $topicsToday = \App\Models\Topic::where('user_id', $user->id)->where('created_at', '>=', $todayStart)->count();
            if ($topicsToday >= $role->daily_topic_limit) {
                $this->app->session()->getFlashBag()->add('topic_error', lang('quota.daily_topic_exceeded'));
                $this->redirect($urls['new_topic']);
                return '';
            }
        }
        $minTimeTopics = (int) $this->getSetting('min_time_between_topics', '0');
        $minTimePosts = (int) $this->getSetting('min_time_between_posts', '0');
        $minTime = $minTimeTopics > 0 ? $minTimeTopics : $minTimePosts;
        if ($minTime > 0) {
            $lastTopic = Topic::where('user_id', $user->id)->orderBy('id', 'desc')->first();
            if ($lastTopic && ($lastTopic->created_at->timestamp + $minTime) > time()) {
                $wait = ($lastTopic->created_at->timestamp + $minTime) - time();
                $this->app->session()->getFlashBag()->add('topic_error', lang('forum.wait_seconds_new_topic', ['seconds' => $wait]));
                $this->redirect($urls['new_topic']);
                return '';
            }
        }

        try {
            $this->app->hooks()->doAction('before_topic_create', $forum, $user);

            $isQuestion = ($topicType === 'question') || (isset($_POST['is_question']) && $_POST['is_question'] === '1');
            $pId = $prefixId > 0 ? $prefixId : 0;
            if ($pId > 0 && !TopicPrefixScopeService::isPrefixAllowedForForum($forum, $pId)) {
                $pId = 0;
            }

            $attachmentIds = isset($_POST['attachment_ids']) && is_array($_POST['attachment_ids']) ? array_map('intval', array_filter($_POST['attachment_ids'])) : [];

            $pollData = [];
            $pollQuestion = trim((string)($_POST['poll_question'] ?? ''));
            $pollOptionsRaw = isset($_POST['poll_options']) && is_array($_POST['poll_options']) ? $_POST['poll_options'] : [];
            if ($pollQuestion !== '' && !empty($pollOptionsRaw)) {
                $pollOptionsFiltered = array_values(array_filter(array_map('trim', $pollOptionsRaw)));
                $maxPollOpts = max(2, (int) $this->getSetting('max_poll_options', '10'));
                $pollOptionsFiltered = array_slice($pollOptionsFiltered, 0, $maxPollOpts);
                if (count($pollOptionsFiltered) >= 2) {
                    $pollMaxVotes = (int)($_POST['poll_max_votes'] ?? 1);
                    $pollMaxVotes = $pollMaxVotes < 1 ? 1 : min($pollMaxVotes, count($pollOptionsFiltered));
                    $pollAllowChange = isset($_POST['poll_allow_change_vote']) ? 1 : 0;
                    $pollClosesAt = null;
                    $closesAtRaw = trim((string)($_POST['poll_closes_at'] ?? ''));
                    if ($closesAtRaw !== '') {
                        $ts = strtotime($closesAtRaw);
                        if ($ts !== false) {
                            $pollClosesAt = date('Y-m-d H:i:s', $ts);
                        }
                    }
                    $pollData = [
                        'question' => $pollQuestion,
                        'options'  => $pollOptionsFiltered,
                        'max_votes' => $pollMaxVotes,
                        'allow_change_vote' => (bool) $pollAllowChange,
                        'closes_at' => $pollClosesAt
                    ];
                }
            }

            $tagIds = isset($_POST['tag_ids']) && is_array($_POST['tag_ids']) ? array_map('intval', array_filter($_POST['tag_ids'])) : [];
            $tagIds = array_unique(array_slice($tagIds, 0, 5));

            $viewerIds = [];
            if ($isPrivate) {
                $viewerIds = isset($_POST['private_viewer_user_ids']) && is_array($_POST['private_viewer_user_ids'])
                    ? array_map('intval', array_filter($_POST['private_viewer_user_ids'])) : [];
                $viewerIds = array_unique(array_slice($viewerIds, 0, 20));
                $viewerIds = array_diff($viewerIds, [(int) $user->id]);
            }

            $topicService = core_make(\App\Services\TopicService::class, null, $this->app);
            $topic = $topicService->createTopic(
                $forum,
                $user,
                $title,
                $body,
                $topicType,
                $isQuestion,
                (bool) $isPrivate,
                $isScheduled,
                $scheduledAt,
                $pId,
                $attachmentIds,
                $tagIds,
                $viewerIds,
                $pollData
            );

            $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_NEW_TOPIC, (int) $user->id, \App\Services\SecurityService::clientIp());

            $topicId = (int) $topic->id;
            $dbType = $topicType === 'article' ? 'article' : ($topicType === 'auction' ? 'auction' : ($isQuestion ? 'question' : 'topic'));

            if ($isScheduled) {
                $this->app->session()->getFlashBag()->add('topic_ok', core__('topic_create.scheduled_success'));
                $this->redirect($urls['display']);
                return '';
            }
            if ($dbType === 'article') {
                $this->redirect(core_url(function_exists('article_url_path_by_id') ? article_url_path_by_id($topicId) : 'article/' . $topicId));
            } else {
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            }
            return '';
        } catch (\Throwable $e) {
            if (class_exists(\Illuminate\Database\Capsule\Manager::class)) {
                try {
                    \Illuminate\Database\Capsule\Manager::connection()->rollBack();
                } catch (\Throwable $rb) {
                }
            }
            error_log('Topic creation error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('topic_error', $this->dbErrorMessage($e));
            $this->redirect($urls['new_topic']);
            return '';
        }
    }
}
