<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Forum;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Post;
use App\Models\PostEdit;
use App\Models\PostLike;
use App\Models\PostReport;
use App\Models\PostVote;
use App\Models\RewardLevel;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\TopicSubscription;
use App\Models\User;
use App\Models\UserCustomField;
use App\Models\UserFieldDefinition;
use App\Models\UserPreference;
use App\Services\Alerts\UserAlertService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Konu detay: mesaj listesi.
 */
class TopicController extends BaseController
{
    /** Mesaj başına saklanacak maksimum düzenleme geçmişi sayısı (en son N kayıt tutulur). */
    private const POST_EDIT_HISTORY_LIMIT = 3;

    /** URL identifier (id, slug veya url_key) ile topic id çözümler. */
    private function resolveTopicIdentifier(string $identifier): ?int
    {
        return resolve_topic_id($identifier);
    }

    private function editorContentFromPost(?string $body, ?string $bodyHtml): string
    {
        $content = trim((string) ($body ?? ''));
        if ($content === '' && $bodyHtml !== null && $bodyHtml !== '') {
            $content = trim((string) $bodyHtml);
        }
        if ($content === '') {
            return '';
        }
        $content = str_replace("\0", '', $content);
        if (preg_match('/&lt;(?:p|div|span|strong|em|a|br|img|iframe|blockquote|pre|code|ul|ol|li|h\d)\b/i', $content)) {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match('/&lt;(?:p|div|span|strong|em|a|br|img|iframe|blockquote|pre|code|ul|ol|li|h\d)\b/i', $content)) {
                $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        return $content;
    }

    public function show(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $topic = Topic::with(['forum', 'prefix'])->where('id', $topicId)->first();
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = $this->app->auth()->user();
        $currentUserId = $user ? (int)$user->id : null;
        $isScheduledOwn = ($topic->status ?? '') === Topic::STATUS_SCHEDULED && $currentUserId && (int)$topic->user_id === $currentUserId;
        if (($topic->status ?? '') === Topic::STATUS_CANCELLED) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if (($topic->status ?? '') === Topic::STATUS_SCHEDULED && !$isScheduledOwn) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if ($this->getSetting('sef_topic_url_mode', 'id') === 'random') {
            (new \App\Services\TopicUrlService())->ensureUrlKey($topic);
        }
        $topic = clone $topic;

        // For compatibility with templates expecting stdClass
        $topicObj = (object)$topic->toArray();
        $topicObj->forum_name = $topic->forum->name ?? '';
        $topicObj->forum_slug = $topic->forum->slug ?? '';
        $topicObj->prefix_name = $topic->prefix->name ?? null;
        $topicObj->prefix_css_class = $topic->prefix->css_class ?? null;
        $topicObj->prefix_icon_class = $topic->prefix->icon_class ?? null;
        $topicObj->prefix_badge_bg = $topic->prefix->badge_bg ?? null;
        $topicObj->prefix_badge_text = $topic->prefix->badge_text ?? null;

        if (!isset($topicObj->accepted_post_id)) {
            $topicObj->accepted_post_id = null;
        }


        if ((int)($topicObj->is_private ?? 0) === 1) {
            $rid = $user ? (int)($user->role_id ?? 0) : 0;
            $isOwnerOrStaff = $currentUserId === (int)$topicObj->user_id || $rid === 1 || $rid === 2;
            $isAllowedViewer = $currentUserId > 0 && \App\Models\TopicPrivateViewer::where('topic_id', $topicId)->where('user_id', $currentUserId)->exists();
            if (!$isOwnerOrStaff && !$isAllowedViewer) {
                return $this->layout('topic/private_topic', [
                    'pageTitle' => lang('topic.private_page_title'),
                    'topic_title' => $topic->title,
                    'forum_slug' => $topic->forum->slug ?? '',
                    'forum_name' => $topic->forum->name ?? '',
                ], false);
            }
        }

        if ($user && !$user->hasPermission('forum.view', $topic->forum)) {
            http_response_code(403);
            return $this->layout('403', ['pageTitle' => lang('error.forbidden'), 'message' => lang('error.no_forum_access')], false);
        }

        $blocked = $currentUserId ? $this->layoutService()->getBlockedUserIds($currentUserId) : [];
        if (in_array((int)$topicObj->user_id, $blocked, true)) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        Topic::where('id', $topicId)->increment('view_count');
        $topicObj->view_count = (int)($topicObj->view_count ?? 0) + 1;

        if ($currentUserId) {
            try {
                \App\Models\TopicRead::updateOrInsert(
                    ['user_id' => $currentUserId, 'topic_id' => $topicObj->id],
                    ['last_read_at' => \now()]
                );
            } catch (\Throwable $e) {
            }
        }

        $this->recordTopicViewer($topicId, $currentUserId);

        $perPage = max(1, (int) $this->getSetting('posts_per_page', '15'));
        $pageNum = max(1, (int) ($_GET['page'] ?? 1));

        $totalPosts = $this->getPostCount((int)$topicObj->id, $blocked);
        $totalPages = max(1, (int) ceil($totalPosts / $perPage));
        if ($pageNum > $totalPages) {
            $pageNum = $totalPages;
        }

        $posts = $this->getPosts(
            (int)$topicObj->id,
            $currentUserId,
            $topicObj->type ?? 'topic',
            isset($topicObj->accepted_post_id) ? (int)$topicObj->accepted_post_id : null,
            $pageNum,
            $perPage,
            $blocked
        );
        $stats = $this->layoutService()->getStats();
        $reputationEnabled = $this->getSetting('reputation_enabled', '1') === '1';

        $poll = \App\Models\Poll::where('topic_id', $topicObj->id)->with('options')->first();
        $pollOptions = [];
        $userVotes = [];
        $totalVotes = 0;
        if ($poll) {
            $pollOptions = $poll->options->all();
            $totalVotes = (int) $poll->options->sum('vote_count');
            if ($currentUserId) {
                $userVotes = $poll->votes()->where('user_id', $currentUserId)->pluck('option_id')->map(fn ($id) => (int) $id)->values()->all();
            }
        }

        $topicTags = [];
        try {
            $topicTags = Topic::find($topicObj->id)?->tags()->orderBy('name')->get(['tags.id', 'tags.name', 'tags.slug'])->toArray() ?? [];
        } catch (\Throwable $e) {
        }

        $topicObj->tags = $topicTags;

        $isSubscribed = false;
        if ($currentUserId) {
            try {
                $isSubscribed = TopicSubscription::where('topic_id', $topicObj->id)->where('user_id', $currentUserId)->exists();
            } catch (\Throwable $e) {
            }
        }

        $canBump = false;
        if ($user && $user->role && $user->role->bump_per_day > 0) {
            $today = \now()->format('Y-m-d');
            $bumpedToday = \Illuminate\Database\Capsule\Manager::table('topic_bumps')
                ->where('user_id', $user->id)
                ->where('bumped_at', $today)
                ->count();
            $canBump = $bumpedToday < $user->role->bump_per_day;
        }

        $viewData = [
            'topic' => $topicObj,
            'topicTags' => $topicTags,
            'isSubscribed' => $isSubscribed,
            'posts' => $posts,
            'stats' => $stats,
            'pageTitle' => $topic->title,
            'reputationEnabled' => $reputationEnabled,
            'poll' => $poll,
            'pollOptions' => $pollOptions,
            'userVotes' => $userVotes,
            'totalVotes' => $totalVotes,
            'currentPage' => $pageNum,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'postsPerPage' => $perPage,
            'show_signatures_to_guests' => $this->getSetting('show_signatures_to_guests', '1') === '1',
            'is_question' => isset($topic->type) && $topic->type === 'question',
            'accepted_post_id' => isset($topic->accepted_post_id) ? (int)$topic->accepted_post_id : null,
            'can_bump' => $canBump,
            'can_reply' => $user && !($topic->is_locked ?? false) && $user->hasPermission('forum.create_post', $topic->forum),
            'is_scheduled_own' => $isScheduledOwn,
            'scheduled_publish_at' => $isScheduledOwn ? $topic->scheduled_publish_at : null,
            'scheduled_notice_text' => $isScheduledOwn && $topic->scheduled_publish_at
                ? lang('topic.scheduled_notice', ['date' => $topic->scheduled_publish_at->format('d.m.Y H:i')])
                : '',
            'enable_inline_quotes' => $this->getSetting('enable_inline_quotes', '1') === '1',
            'topic_post_scrubber_enabled' => $this->getSetting('topic_post_scrubber_enabled', '1') === '1',
            'topicReaders'         => $this->getTopicReaders($topicId),
            'topicReactors'        => $this->getTopicReactors($topicId),
            'topicViewerStats'     => $this->getTopicViewerStats($topicId),
            'topicViewerClientToken' => $this->clientTopicViewerToken($currentUserId),
        ];
        $viewData = $this->app->hooks()->applyFilters('topic.view_data', $viewData, $topicObj);
        if (isset($viewData['posts']) && is_array($viewData['posts'])) {
            foreach ($viewData['posts'] as $i => $postItem) {
                $viewData['posts'][$i] = $this->app->hooks()->applyFilters('post.display_data', $postItem, $topicObj);
            }
        }
        return $this->layout('showthread', $viewData, false);
    }

    /**
     * API /api/topic/viewers ile aynı belirteç: üye u{id}, misafir g{hash} — çift sayımı önler.
     */
    private function clientTopicViewerToken(?int $userId): string
    {
        if ($userId !== null) {
            return 'u' . $userId;
        }
        $ip = \App\Services\SecurityService::clientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return 'g' . substr(md5($ip . $ua), 0, 32);
    }

    /** Son 5 dakikada konuyu okuyan tekil üyeler (max 16), ApiController ile aynı tablo. */
    private function getTopicReaders(int $topicId): array
    {
        try {
            $this->ensureTopicActiveViewersTableExists();
            $threshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            $orderedIds = DB::table('topic_active_viewers')
                ->where('topic_id', $topicId)
                ->where('last_seen_at', '>=', $threshold)
                ->whereNotNull('user_id')
                ->selectRaw('user_id, MAX(last_seen_at) as mx')
                ->groupBy('user_id')
                ->orderByDesc('mx')
                ->limit(16)
                ->pluck('user_id')
                ->all();
            if ($orderedIds === []) {
                return [];
            }
            $users = DB::table('users')
                ->whereIn('id', $orderedIds)
                ->select('id', 'username', 'avatar_path')
                ->get()
                ->keyBy('id');
            $out = [];
            foreach ($orderedIds as $uid) {
                $row = $users->get($uid);
                if ($row !== null) {
                    $out[] = $row;
                }
            }

            return $out;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Viewer istatistikleri: üye sayısı, misafir sayısı, toplam + üye listesi (link için).
     * topic_active_viewers — ApiController::topicViewers ile aynı kaynak (5 dk).
     *
     * @return array{members: int, guests: int, total: int, member_names: array}
     */
    private function getTopicViewerStats(int $topicId): array
    {
        try {
            $this->ensureTopicActiveViewersTableExists();
            $threshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            $rows = DB::table('topic_active_viewers')
                ->where('topic_id', $topicId)
                ->where('last_seen_at', '>=', $threshold)
                ->get(['user_id', 'session_token']);
            $memberIds = [];
            $guestCount = 0;
            foreach ($rows as $row) {
                if ($row->user_id !== null) {
                    $memberIds[] = (int) $row->user_id;
                } else {
                    ++$guestCount;
                }
            }
            $memberIds = array_values(array_unique($memberIds));
            $memberRows = [];
            if ($memberIds !== []) {
                $users = DB::table('users')
                    ->whereIn('id', $memberIds)
                    ->select('id', 'username')
                    ->get()
                    ->toArray();
                $memberRows = $users;
            }

            return [
                'members'      => count($memberRows),
                'guests'       => $guestCount,
                'total'        => count($memberRows) + $guestCount,
                'member_names' => $memberRows,
            ];
        } catch (\Throwable) {
            return ['members' => 0, 'guests' => 0, 'total' => 0, 'member_names' => []];
        }
    }

    private function ensureTopicActiveViewersTableExists(): void
    {
        $schema = DB::schema();
        if ($schema->hasTable('topic_active_viewers')) {
            return;
        }
        $schema->create('topic_active_viewers', function ($table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('topic_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('session_token', 64);
            $table->dateTime('last_seen_at');
            $table->unique(['topic_id', 'session_token'], 'uq_topic_active_viewer');
            $table->index(['topic_id', 'last_seen_at'], 'idx_tav_ts');
        });
    }

    /**
     * Görüntülemeyi topic_active_viewers tablosuna kaydet (ApiController::topicViewerPing ile aynı tablo).
     */
    private function recordTopicViewer(int $topicId, ?int $userId): void
    {
        $token = $this->clientTopicViewerToken($userId);
        $now = date('Y-m-d H:i:s');
        try {
            $this->ensureTopicActiveViewersTableExists();
            DB::table('topic_active_viewers')->updateOrInsert(
                ['topic_id' => $topicId, 'session_token' => $token],
                ['user_id' => $userId, 'last_seen_at' => $now]
            );
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), "doesn't exist") || str_contains($e->getMessage(), 'topic_active_viewers')) {
                try {
                    $this->ensureTopicActiveViewersTableExists();
                    DB::table('topic_active_viewers')->updateOrInsert(
                        ['topic_id' => $topicId, 'session_token' => $token],
                        ['user_id' => $userId, 'last_seen_at' => $now]
                    );
                } catch (\Throwable) {
                }
            }
        }
    }

    /** Konudaki herhangi bir mesajı beğenen tekil kullanıcılar (max 21). */
    private function getTopicReactors(int $topicId): array
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('post_likes')
                ->join('posts', 'posts.id', '=', 'post_likes.post_id')
                ->join('users', 'users.id', '=', 'post_likes.user_id')
                ->where('posts.topic_id', $topicId)
                ->whereNull('posts.deleted_at')
                ->select('users.id', 'users.username', 'users.avatar_path')
                ->distinct()
                ->orderByDesc('post_likes.created_at')
                ->limit(21)
                ->get()
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /** Toplam mesaj sayısı (silinmemiş, engellenen kullanıcılar hariç). */
    protected function getPostCount(int $topicId, array $blocked = []): int
    {
        $query = Post::query()
            ->where('topic_id', $topicId)
            ->whereNull('posts.deleted_at');
        if (!empty($blocked)) {
            $query->whereNotIn('user_id', $blocked);
        }
        return $query->count();
    }

    /** Sayfa başına mesajları veritabanı LIMIT/OFFSET ile yükler; büyük konularda bellek ve timeout riski olmaz. */
    protected function getPosts(
        int $topicId,
        ?int $currentUserId,
        string $topicType = 'topic',
        ?int $acceptedPostId = null,
        int $pageNum = 1,
        int $perPage = 15,
        array $blocked = []
    ): array {
        $query = Post::query()
            ->with([
                'user' => function ($q) {
                    $q->withCount(['posts as user_post_count' => fn ($q2) => $q2->whereNull('deleted_at')])
                      ->withSum(['posts as user_like_count' => fn ($q2) => $q2->whereNull('deleted_at')], 'like_count');
                },
                'user.role',
                'attachments',
                'replyTo.user',
                'replies.user',
            ])
            ->where('topic_id', $topicId)
            ->whereNull('posts.deleted_at');

        if (!empty($blocked)) {
            $query->whereNotIn('user_id', $blocked);
        }

        if ($topicType === 'question' && $acceptedPostId !== null) {
            $query->orderByRaw('is_first_post DESC, (id = ?) DESC, net_votes DESC, id ASC', [$acceptedPostId]);
        } else {
            $query->orderBy('id');
        }

        $offset = ($pageNum - 1) * $perPage;
        $collection = $query->offset($offset)->limit($perPage)->get();
        $postIds = $collection->pluck('id')->all();

        $likedIds = [];
        $voteMap = [];
        if ($currentUserId !== null && count($postIds) > 0) {
            $likedIds = PostLike::where('user_id', $currentUserId)->whereIn('post_id', $postIds)->pluck('post_id')->map(fn ($id) => (int) $id)->all();
            $votes = PostVote::where('user_id', $currentUserId)->whereIn('post_id', $postIds)->get(['post_id', 'value']);
            foreach ($votes as $v) {
                $voteMap[(int) $v->post_id] = (int) $v->value;
            }
        }

        // Online status: single bulk query (15 min threshold)
        $onlineThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $authorIds = $collection->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $onlineUserIds = [];
        if (!empty($authorIds)) {
            $onlineUserIds = User::whereIn('id', $authorIds)
                ->where('last_activity_at', '>=', $onlineThreshold)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $today = date('m-d');
        $posts = [];
        $hideFromGuests = $this->getSetting('hide_content_from_guests', '0') === '1';
        $isGuest = $currentUserId === null;
        // Makale detay sayfasında ilk post (makale gövdesi) misafirlere de gösterilsin
        $hideThisPost = $isGuest && $hideFromGuests;

        foreach ($collection as $post) {
            $u = $post->user;
            $userPostCount = (int) ($u->user_post_count ?? 0);
            $userLikeCount = (int) ($u->user_like_count ?? $u->posts_sum_like_count ?? 0);
            $repPos = (int) ($u->reputation_positive ?? 0);
            $repNeg = (int) ($u->reputation_negative ?? 0);
            $rewardLevel = RewardLevel::forUser($userPostCount, $repPos - $repNeg, $userLikeCount);

            $isFirstPost = (bool) ($post->is_first_post ?? false);
            $applyGuestHide = $hideThisPost && !($topicType === 'article' && $isFirstPost);

            $obj = (object) [
                'id' => $post->id,
                'reply_to_id' => $post->reply_to_id ? (int) $post->reply_to_id : null,
                'replied_post' => $post->replyTo ? (object) [
                    'id' => $post->replyTo->id,
                    'user_id' => $post->replyTo->user_id,
                    'username' => $post->replyTo->user->username ?? '',
                    'body_html' => $post->replyTo->body_html,
                    'is_first_post' => (bool) $post->replyTo->is_first_post,
                ] : null,
                'inbound_replies' => $post->replies ? $post->replies->map(function ($r) {
                    return (object) [
                        'id' => $r->id,
                        'user_id' => $r->user_id,
                        'username' => $r->user->username ?? '',
                        'body_html' => $r->body_html,
                        'created_at' => is_string($r->created_at) ? $r->created_at : ($r->created_at ? $r->created_at->format('Y-m-d H:i:s') : null),
                    ];
                })->all() : [],
                'body' => $post->body,
                'body_html' => core_hide_guest_content($post->body_html ?? '', $applyGuestHide),
                'like_count' => (int) ($post->like_count ?? 0),
                'is_first_post' => (bool) ($post->is_first_post ?? false),
                'created_at' => $this->formatDateTime($post->created_at),
                'edited_at' => $this->formatDateTime($post->edited_at),
                'edit_count' => (int) ($post->edit_count ?? 0),
                'user_id' => $u->id ?? null,
                'username' => $u->username ?? '',
                'avatar_path' => $u->avatar_path ?? null,
                'location' => $u->location ?? null,
                'is_verified' => (bool) ($u->is_verified ?? false),
                'is_banned' => (bool) ($u->is_banned ?? false),
                'user_joined' => $this->formatDateTime($u->created_at),
                'role_name' => $u->role->name ?? null,
                'role_color' => $u->role->color ?? null,
                'custom_title' => $u->custom_title ?? null,
                'user_post_count' => $userPostCount,
                'user_like_count' => $userLikeCount,
                'reputation_positive' => $repPos,
                'reputation_negative' => $repNeg,
                'points' => (int) ($u->points ?? 0),
                'reward_points' => (int) ($u->reward_points ?? 0),
                'warning_points' => (int) ($u->warning_points ?? 0),
                'is_online' => in_array((int) ($u->id ?? 0), $onlineUserIds, true),
                'user_signature' => $u->signature ?? '',
                'reward_level' => $rewardLevel ? (object) ['id' => $rewardLevel->id, 'name' => $rewardLevel->name, 'badge_label' => $rewardLevel->badge_label, 'badge_icon' => $rewardLevel->badge_icon, 'badge_css' => $rewardLevel->badge_css] : null,
                'is_birthday_today' => $u->is_birthday_today ?? false,
                'first_name' => $u->first_name ?? null,
                'last_name' => $u->last_name ?? null,
                'show_name' => $u->show_name ?? null,
                'net_votes' => (int) ($post->net_votes ?? 0),
                'vote_by_me' => $voteMap[(int) $post->id] ?? 0,
                'liked_by_me' => in_array((int) $post->id, $likedIds, true),
                'custom_fields' => [],
                'attachments' => $post->attachments->all(),
            ];
            $posts[] = $obj;
        }

        $this->attachPostbitCustomFields($posts);

        return $posts;
    }

    /** Postbit'te gösterilecek özel alan değerlerini her posta ekle (Eloquent/DB). */
    protected function attachPostbitCustomFields(array $posts): void
    {
        try {
            $defs = UserFieldDefinition::where('show_in_postbit', 1)->orderBy('sort_order')->get(['field_key', 'name']);
            if ($defs->isEmpty()) {
                return;
            }
            $keys = $defs->pluck('field_key')->all();
            $userIds = array_values(array_unique(array_map(fn ($p) => (int) $p->user_id, $posts)));
            if (empty($userIds)) {
                return;
            }
            $rows = UserCustomField::whereIn('user_id', $userIds)->whereIn('field_key', $keys)->get(['user_id', 'field_key', 'field_value']);
            $byUser = [];
            foreach ($rows as $r) {
                $byUser[(int) $r->user_id][$r->field_key] = $r->field_value;
            }
            $defsByName = $defs->keyBy('field_key')->map(fn ($d) => $d->name)->all();
            foreach ($posts as $p) {
                $uid = (int) $p->user_id;
                $p->custom_fields = [];
                if (isset($byUser[$uid])) {
                    foreach ($byUser[$uid] as $k => $v) {
                        if ($v !== null && $v !== '') {
                            $p->custom_fields[] = (object) ['key' => $k, 'label' => $defsByName[$k] ?? $k, 'value' => $v];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** @username → user profile link (Eloquent; no PDO). */
    protected function processMentions(string $html): string
    {
        $html = preg_replace('/<a\s[^>]*class="[^"]*mention[^"]*"[^>]*>@([^<]+)<\/a>/iu', '@$1', $html);
        return preg_replace_callback('/(^|[>\s])@([a-zA-Z0-9_\x80-\xFF]+)/u', function ($m) {
            $prefix = $m[1];
            $username = $m[2];
            $exists = User::where('username', $username)->exists();
            if ($exists) {
                $link = '<a href="' . core_e(core_url('member/' . rawurlencode($username))) . '" class="mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors" data-mention-username="' . core_e($username) . '">@' . core_e($username) . '</a>';
                return $prefix . $link;
            }
            return $m[0];
        }, $html);
    }

    /** Extracts @username mentions from text and returns user ids (Eloquent; no PDO). */
    protected function extractMentionedUserIds(string $text): array
    {
        $ids = [];
        if (preg_match_all('/(^|[>\s])@([a-zA-Z0-9_\x80-\xFF]+)/u', $text, $m, PREG_SET_ORDER)) {
            $usernames = array_unique(array_map(fn ($x) => $x[2], $m));
            $found = User::whereIn('username', $usernames)->pluck('id')->map(fn ($id) => (int)$id)->all();
            return array_values(array_unique($found));
        }
        return $ids;
    }

    /** Tarih alanını Y-m-d H:i:s string'e çevirir (Carbon, string veya null kabul eder). */
    protected function formatDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_object($value) && method_exists($value, 'format')) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_string($value)) {
            $ts = strtotime($value);
            return $ts !== false ? date('Y-m-d H:i:s', $ts) : $value;
        }
        return null;
    }

    public function storeReply(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }

        if (!core_csrf_valid('reply_topic', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('reply_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $topic = Topic::with('forum')->find($topicId);

        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if (!$user->hasPermission('forum.create_post', $topic->forum)) {
            $this->app->session()->getFlashBag()->add('reply_error', lang('error.no_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $blocked = $this->layoutService()->getBlockedUserIds((int)$user->id);
        if (in_array((int)($topic->user_id ?? 0), $blocked, true)) {
            $this->app->session()->getFlashBag()->add('reply_error', lang('topic.cannot_reply'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        if (isset($topic->is_locked) && $topic->is_locked) {
            $this->app->session()->getFlashBag()->add('reply_error', lang('topic.locked'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $ip = \App\Services\SecurityService::clientIp();
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_REPLY, (int) $user->id, $ip);
        if (!$r['allowed']) {
            $this->app->session()->getFlashBag()->add('reply_error', $r['message']);
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        // Anti-bump: aynı konuda ardışık iki mesaj (son mesaj bu kullanıcıysa ve süre dolmadıysa engelle)
        if ($this->getSetting('antibump_enabled', '1') === '1') {
            $antibumpSeconds = max(0, (int) $this->getSetting('antibump_seconds', '60'));
            $timelimitMinutes = $antibumpSeconds <= 0 ? 60 : (int) max(1, ceil($antibumpSeconds / 60));
            $topicService = core_make(\App\Services\TopicService::class);
            if ($topicService->isDoublePost((int) $user->id, (int) $topicId, $timelimitMinutes)) {
                $this->app->session()->getFlashBag()->add('reply_error', lang('topic.antibump_blocked', ['minutes' => $timelimitMinutes]));
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                return '';
            }
        }

        $maxPostLen = (int) $this->getSetting('max_post_length', '0');

        $request = new \App\Http\Requests\Topic\StoreReplyRequest($maxPostLen);
        if (!$request->validate()) {
            $this->app->session()->getFlashBag()->add('reply_error', $request->firstError());
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $body = trim((string)$request->input('body', ''));
        $censorship = $this->app->censorship();
        if ($censorship->isCensorshipEnabled() && $censorship->applyToPosts()) {
            $bodyCheck = $censorship->checkContent($body);
            if (!$bodyCheck['allowed']) {
                $this->app->session()->getFlashBag()->add('reply_error', lang('censorship.content_blocked'));
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                return '';
            }
            $body = $bodyCheck['filtered_text'];
        }
        $minTimePosts = (int) $this->getSetting('min_time_between_posts', '0');
        if ($minTimePosts > 0) {
            $lastPost = Post::where('topic_id', $topic->id)->orderBy('id', 'desc')->first();
            if ($lastPost && (int)$lastPost->user_id === (int)$user->id) {
                $waitUntil = strtotime($lastPost->created_at->toDateTimeString()) + $minTimePosts;
                if (time() < $waitUntil) {
                    $wait = $waitUntil - time();
                    $this->app->session()->getFlashBag()->add('reply_error', lang('topic.wait_seconds_posts', ['min' => $minTimePosts, 'wait' => $wait]));
                    $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                    return '';
                }
            }
        }

        $bodyHtml = core_body_to_html($body);
        $bodyHtml = $this->processMentions($bodyHtml);
        $bodyHtml = core_process_post_refs($bodyHtml, (int)$topic->id);

        $replyToId = isset($_POST['reply_to_id']) && (int) $_POST['reply_to_id'] > 0 ? (int) $_POST['reply_to_id'] : null;
        if ($replyToId) {
            $replyExists = Post::where('topic_id', $topic->id)->where('id', $replyToId)->exists();
            if (!$replyExists) {
                $replyToId = null;
            }
        }

        $this->app->hooks()->doAction('before_post_create', $topic, $user);

        try {
            $topicId = (int) $topic->id;

            $topicService = core_make(\App\Services\TopicService::class, null, $this->app);
            $post = $topicService->replyTopic($topic, $user, $body, $bodyHtml, $replyToId);

            $attachmentIds = isset($_POST['attachment_ids']) && is_array($_POST['attachment_ids']) ? array_map('intval', array_filter($_POST['attachment_ids'])) : [];
            if (!empty($attachmentIds) && $post) {
                DB::table('attachments')
                    ->whereIn('id', $attachmentIds)
                    ->where('user_id', (int) $user->id)
                    ->where(function ($q) {
                        $q->whereNull('post_id')->orWhere('post_id', 0);
                    })
                    ->update(['post_id' => $post->id]);
            }

            $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_REPLY, (int)$user->id, \App\Services\SecurityService::clientIp());
            try {
                \App\Models\TopicRead::updateOrInsert(
                    ['user_id' => (int) $user->id, 'topic_id' => $topicId],
                    ['last_read_at' => \now()]
                );
            } catch (\Throwable $e) {
            }
            $this->redirect(topic_url($topic));
            return '';
        } catch (\RuntimeException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->app->session()->getFlashBag()->add('reply_error', $e->getMessage());
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $msg = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            error_log('Reply creation error: ' . $msg);
            error_log('Reply creation trace: ' . $e->getTraceAsString());
            $logDir = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            if (is_dir($logDir) || @mkdir($logDir, 0755, true)) {
                @file_put_contents(
                    $logDir . DIRECTORY_SEPARATOR . 'reply_errors.log',
                    date('c') . ' ' . $msg . "\n" . $e->getTraceAsString() . "\n\n",
                    FILE_APPEND | LOCK_EX
                );
            }
            $this->app->session()->getFlashBag()->add('reply_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
    }

    /** Konuya abone ol. */
    public function subscribe(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('topic_subscribe', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $exists = Topic::where('id', $topicId)->exists();
        if (!$exists) {
            $this->redirect(core_url(''));
            return '';
        }
        try {
            TopicSubscription::firstOrCreate(
                ['user_id' => $user->id, 'topic_id' => $topicId],
                ['created_at' => \now()]
            );
        } catch (\Throwable $e) {
        }
        $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.subscribed'));
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /** Konu aboneliğinden çık. */
    public function unsubscribe(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('topic_subscribe', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        try {
            TopicSubscription::where('topic_id', $topicId)->where('user_id', $user->id)->delete();
        } catch (\Throwable $e) {
        }
        $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.unsubscribed'));
        $redirect = trim((string)($_POST['redirect'] ?? $_GET['redirect'] ?? ''));
        if ($redirect !== '' && str_starts_with($redirect, '/')) {
            $this->redirect(core_url(ltrim($redirect, '/')));
        } else {
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        }
        return '';
    }

    public function edit(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('auth_error', core__('auth.login_required'));
            $this->redirect(core_url('login'));
            return '';
        }

        $topic = Topic::with('forum')->find($topicId);

        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        $rid = (int)($user->role_id ?? 0);
        if ($topic->user_id != $user->id && $rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_edit_permission'));
            $this->redirect(core_url('topic/' . topic_url_path($topic)));
            return '';
        }

        $firstPost = Post::where('topic_id', $topicId)->where('is_first_post', 1)->first();

        if (!$firstPost) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.first_post_not_found'));
            $this->redirect(core_url('topic/' . topic_url_path($topic)));
            return '';
        }

        $topicTags = $topic->tags()->orderBy('name')->get(['tags.id', 'tags.name', 'tags.slug'])->toArray();
        $topicPrivateViewers = $topic->privateViewers()->orderBy('username')->get(['users.id', 'users.username'])->map(fn ($u) => ['id' => (int)$u->id, 'username' => $u->username])->values()->all();

        $forum = $topic->forum;
        $allowedTypes = $forum ? $this->app->hooks()->applyFilters('topic_create_allowed_types', ['topic', 'article', 'poll', 'question'], $forum) : ['topic', 'article', 'poll', 'question'];

        $forum = $topic->forum;
        $prefixes = $forum
            ? \App\Services\TopicPrefixScopeService::prefixesForForum($forum)->all()
            : [];
        $error = $this->app->session()->getFlashBag()->get('topic_edit_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $postBody = $this->editorContentFromPost($firstPost->body ?? null, $firstPost->body_html ?? null);
        $postBodyBase64 = $postBody !== '' ? base64_encode($postBody) : '';

        $poll = Poll::where('topic_id', $topic->id)->with('options')->first();
        $poll_question = $poll ? $poll->question : '';
        $poll_options = $poll ? $poll->options->pluck('option_text')->all() : [];
        $poll_max_votes = $poll ? (int) $poll->max_votes : 1;
        $poll_allow_change_vote = $poll ? (bool) $poll->allow_change_vote : false;
        $poll_closes_at = $poll && $poll->closes_at ? $poll->closes_at->format('Y-m-d\TH:i') : '';

        return $this->layout('topics/edit', [
            'topic' => $topic,
            'post' => $firstPost,
            'prefixes' => $prefixes,
            'allowedTypes' => $allowedTypes,
            'topicTags' => $topicTags ?? [],
            'topicPrivateViewers' => $topicPrivateViewers ?? [],
            'pageTitle' => lang('topic.edit_page_title', ['title' => $topic->title]),
            'error' => $error,
            'post_body_base64' => $postBodyBase64,
            'poll_question' => $poll_question,
            'poll_options' => $poll_options,
            'poll_max_votes' => $poll_max_votes,
            'poll_allow_change_vote' => $poll_allow_change_vote,
            'poll_closes_at' => $poll_closes_at,
        ], false);
    }

    public function update(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }

        if (!core_csrf_valid('edit_topic', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_edit_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/edit'));
            return '';
        }
        $ip = \App\Services\SecurityService::clientIp();
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_EDIT_TOPIC, (int) $user->id, $ip);
        if (!$r['allowed']) {
            $this->app->session()->getFlashBag()->add('topic_edit_error', $r['message']);
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/edit'));
            return '';
        }
        $topic = Topic::find($topicId);

        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        $rid = (int)($user->role_id ?? 0);
        if ($topic->user_id != $user->id && $rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_edit_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $prefixId = (int)($_POST['prefix_id'] ?? 0);
        $isQuestion = isset($_POST['is_question']) && $_POST['is_question'] === '1';
        $forum = $topic->forum;
        $allowedQuestion = $forum && \in_array('question', $this->app->hooks()->applyFilters('topic_create_allowed_types', ['topic', 'article', 'poll', 'question'], $forum), true);
        $newType = ($allowedQuestion && $isQuestion) ? 'question' : 'topic';

        $cleanBody = trim(strip_tags(str_replace(['&nbsp;', '&#160;', '&zwj;'], '', $body)));
        $hasEmbedOnly = ($cleanBody === '' && (str_contains($body, '<iframe') || str_contains($body, 'mfbb-media-embed') || str_contains($body, 'class="mfbb-media-embed"')));
        if ($title === '' || ($cleanBody === '' && !$hasEmbedOnly)) {
            $this->app->session()->getFlashBag()->add('topic_edit_error', core__('forum.title_body_required'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/edit'));
            return '';
        }

        $censorship = $this->app->censorship();
        if ($censorship->isCensorshipEnabled()) {
            if ($censorship->applyToTopicTitles()) {
                $titleCheck = $censorship->checkContent($title);
                if (!$titleCheck['allowed']) {
                    $this->app->session()->getFlashBag()->add('topic_edit_error', lang('censorship.content_blocked'));
                    $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/edit'));
                    return '';
                }
                $title = $titleCheck['filtered_text'];
            }
            if ($censorship->applyToPosts()) {
                $bodyCheck = $censorship->checkContent($body);
                if (!$bodyCheck['allowed']) {
                    $this->app->session()->getFlashBag()->add('topic_edit_error', lang('censorship.content_blocked'));
                    $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/edit'));
                    return '';
                }
                $body = $bodyCheck['filtered_text'];
            }
        }

        try {
            DB::connection()->beginTransaction();

            $pId = $prefixId > 0 ? $prefixId : null;
            if ($pId !== null && $forum && !\App\Services\TopicPrefixScopeService::isPrefixAllowedForForum($forum, $pId)) {
                $pId = null;
            }
            $topic->title = $title;
            $topic->prefix_id = $pId;
            if ($allowedQuestion) {
                $topic->type = $newType;
            }
            $topic->is_private = isset($_POST['is_private']) && $_POST['is_private'] === '1' ? 1 : 0;

            $wasScheduledPublishNow = false;
            $wasPublishedRescheduled = false;
            $scheduledRaw = trim((string)($_POST['scheduled_publish_at'] ?? ''));

            if (($topic->status ?? '') === Topic::STATUS_SCHEDULED) {
                if ($scheduledRaw === '') {
                    // Boş = planı kaldır, hemen yayınla
                    $topic->status = Topic::STATUS_PUBLISHED;
                    $topic->scheduled_publish_at = null;
                    $wasScheduledPublishNow = true;
                } else {
                    $ts = strtotime($scheduledRaw);
                    if ($ts !== false && $ts > time()) {
                        $topic->scheduled_publish_at = date('Y-m-d H:i:s', $ts);
                    }
                }
            } else {
                // Yayındaki konu: ileri tarih seçilmişse plana al (listedan kaldır, cron'da tekrar yayınlanır)
                if ($scheduledRaw !== '') {
                    $ts = strtotime($scheduledRaw);
                    if ($ts !== false && $ts > time()) {
                        $topic->status = Topic::STATUS_SCHEDULED;
                        $topic->scheduled_publish_at = date('Y-m-d H:i:s', $ts);
                        $wasPublishedRescheduled = true;
                    }
                }
            }

            $topic->save();

            if ($wasPublishedRescheduled) {
                $forum = $topic->forum;
                if ($forum) {
                    $forum->decrement('topic_count');
                    $forum->decrement('post_count');
                }
                DB::table('forum_stats')->where('id', 1)->update([
                    'total_topics' => DB::raw('GREATEST(0, CAST(total_topics AS SIGNED) - 1)'),
                    'total_posts' => DB::raw('GREATEST(0, CAST(total_posts AS SIGNED) - 1)'),
                ]);
                $this->app->cache()->delete('forum_stats');
                $this->app->cache()->delete('home_categories');
            }

            if ($wasScheduledPublishNow) {
                $forum = $topic->forum;
                if ($forum) {
                    $forum->increment('topic_count');
                    $forum->increment('post_count');
                    $forum->forceFill([
                        'last_post_id' => $topic->last_post_id,
                        'last_post_user_id' => $topic->last_post_user_id,
                        'last_post_at' => $topic->last_post_at ?? \now(),
                    ])->save();
                }
                DB::table('forum_stats')->where('id', 1)->update([
                    'total_topics' => DB::raw('total_topics + 1'),
                    'total_posts' => DB::raw('total_posts + 1'),
                ]);
                $this->app->cache()->delete('forum_stats');
                $this->app->cache()->delete('home_categories');
            }

            $bodyHtml = core_body_to_html($body);
            $bodyHtml = core_process_mentions($bodyHtml);
            $bodyHtml = core_process_post_refs($bodyHtml, (int)$topicId);

            Post::where('topic_id', $topicId)->where('is_first_post', 1)
                ->update(['body' => $body, 'body_html' => $bodyHtml, 'updated_at' => \now()]);

            // Tags: update via sync
            $oldTagIds = $topic->tags()->pluck('tags.id')->toArray();
            $topic->tags()->detach();
            if (!empty($oldTagIds)) {
                Tag::whereIn('id', $oldTagIds)->decrement('use_count');
            }

            $tagIds = isset($_POST['tag_ids']) && is_array($_POST['tag_ids']) ? array_map('intval', array_filter($_POST['tag_ids'])) : [];
            $tagIds = array_unique(array_slice($tagIds, 0, 5));
            if (!empty($tagIds)) {
                $validIds = Tag::whereIn('id', $tagIds)->pluck('id')->toArray();
                if (!empty($validIds)) {
                    $topic->tags()->attach($validIds);
                    Tag::whereIn('id', $validIds)->increment('use_count');
                }
            }

            // Anket: ekle / güncelle / sil
            $pollQuestion = trim((string)($_POST['poll_question'] ?? ''));
            $pollOptionsRaw = isset($_POST['poll_options']) && is_array($_POST['poll_options']) ? $_POST['poll_options'] : [];
            $pollOptionsFiltered = array_values(array_filter(array_map('trim', $pollOptionsRaw)));
            $maxPollOpts = max(2, (int) $this->getSetting('max_poll_options', '10'));
            $pollOptionsFiltered = array_slice($pollOptionsFiltered, 0, $maxPollOpts);
            $existingPoll = Poll::where('topic_id', $topicId)->first();

            if ($pollQuestion !== '' && count($pollOptionsFiltered) >= 2) {
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
                if ($existingPoll) {
                    $existingPoll->update([
                        'question' => mb_substr($pollQuestion, 0, 500),
                        'max_votes' => $pollMaxVotes,
                        'allow_change_vote' => (bool) $pollAllowChange,
                        'closes_at' => $pollClosesAt,
                    ]);
                    DB::table('poll_votes')->where('poll_id', $existingPoll->id)->delete();
                    PollOption::where('poll_id', $existingPoll->id)->delete();
                    foreach ($pollOptionsFiltered as $i => $text) {
                        PollOption::create([
                            'poll_id' => $existingPoll->id,
                            'option_text' => mb_substr($text, 0, 500),
                            'vote_count' => 0,
                            'sort_order' => $i,
                        ]);
                    }
                } else {
                    $poll = Poll::create([
                        'topic_id' => $topicId,
                        'question' => mb_substr($pollQuestion, 0, 500),
                        'max_votes' => $pollMaxVotes,
                        'allow_change_vote' => (bool) $pollAllowChange,
                        'closes_at' => $pollClosesAt,
                        'created_at' => \now(),
                    ]);
                    foreach ($pollOptionsFiltered as $i => $text) {
                        PollOption::create([
                            'poll_id' => $poll->id,
                            'option_text' => mb_substr($text, 0, 500),
                            'vote_count' => 0,
                            'sort_order' => $i,
                        ]);
                    }
                }
            } elseif ($existingPoll) {
                PollOption::where('poll_id', $existingPoll->id)->delete();
                DB::table('poll_votes')->where('poll_id', $existingPoll->id)->delete();
                $existingPoll->delete();
            }

            $oldViewerIds = DB::table('topic_private_viewers')->where('topic_id', $topicId)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
            DB::table('topic_private_viewers')->where('topic_id', $topicId)->delete();
            $viewerIds = isset($_POST['private_viewer_user_ids']) && is_array($_POST['private_viewer_user_ids'])
                ? array_map('intval', array_filter($_POST['private_viewer_user_ids'])) : [];
            $viewerIds = array_unique(array_slice($viewerIds, 0, 20));
            $viewerIds = array_diff($viewerIds, [(int) $user->id]);
            if (!empty($viewerIds)) {
                $validViewers = User::whereIn('id', $viewerIds)->where('is_banned', 0)->pluck('id')->all();
                foreach ($validViewers as $vid) {
                    DB::table('topic_private_viewers')->insert([
                        'topic_id' => $topicId,
                        'user_id' => $vid,
                        'created_at' => \now(),
                    ]);
                }
                $newlyAddedIds = array_diff($validViewers, $oldViewerIds);
                if (!empty($newlyAddedIds)) {
                    $topicUrl = function_exists('topic_url_path_by_id') ? core_url('topic/' . topic_url_path_by_id($topicId)) : core_url('topic/' . $topicId);
                    $topicTitle = $topic->title ?? '';
                    $fromUsername = $user->username ?? '';
                    foreach ($newlyAddedIds as $vid) {
                        (new UserAlertService())->insert($vid, 'private_topic_added', [
                            'url' => $topicUrl,
                            'from_user_id' => (int) $user->id,
                            'from_username' => $fromUsername,
                            'topic_id' => $topicId,
                            'topic_title' => $topicTitle,
                        ]);
                    }
                }
            }

            DB::connection()->commit();
            $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_EDIT_TOPIC, (int) $user->id, $ip);

            try {
                $topicForEvent = Topic::find((int)$topicId);
                if ($topicForEvent) {
                    $this->app->event()->dispatch(new \App\Events\TopicEdited($topicForEvent, $user, $body, $title), \App\Events\TopicEdited::NAME);
                }
            } catch (\Throwable $e) {
            }

            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        } catch (\Throwable $e) {
            DB::connection()->rollBack();
            error_log('Topic edit error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('topic_edit_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/edit'));
            return '';
        }
    }

    /**
     * Soru/çözüm konularında mesaja yukarı (1) veya aşağı (-1) oy verir. Düz form POST, yönlendirme.
     */
    public function votePost(string $id): string
    {
        $user = $this->app->auth()->user();
        $isAjax = $this->isAjaxRequest();
        if (!$user) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => lang('topic.login_required') ?: 'Giriş yapmalısınız.'], 401);
                return '';
            }
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('post_vote', (string)($_POST['_token'] ?? ''))) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => core__('common.invalid_csrf')], 403);
                return '';
            }
            $this->redirect(core_url(''));
            return '';
        }
        $value = (int)($_POST['value'] ?? 0);
        if ($value !== 1 && $value !== -1) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => 'Geçersiz parametre.'], 400);
                return '';
            }
            $this->redirect(core_url(''));
            return '';
        }
        $postId = resolve_post_id($id);
        if ($postId === null) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => 'Mesaj bulunamadı.'], 404);
                return '';
            }
            $this->redirect(core_url(''));
            return '';
        }
        $post = Post::with('topic')->find($postId);
        if (!$post) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => 'Mesaj bulunamadı.'], 404);
                return '';
            }
            $this->redirect(core_url(''));
            return '';
        }
        $topic = $post->topic;
        if (!$topic || ($topic->type ?? '') !== 'question') {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => 'Bu konu soru-cevap türünde değil.'], 400);
                return '';
            }
            $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
            return '';
        }
        if ((int)$post->user_id === (int)$user->id) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => 'Kendi mesajınıza oy veremezsiniz.'], 400);
                return '';
            }
            $this->app->session()->getFlashBag()->add('topic_error', 'Kendi mesajınıza oy veremezsiniz.');
            $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
            return '';
        }
        try {
            $prev = PostVote::where('post_id', $postId)->where('user_id', $user->id)->first();
            $prevValue = $prev ? (int)$prev->value : 0;

            // If clicking the opposite vote, or the same vote, reset to 0 (cancel the vote entirely)
            if ($prevValue !== 0 && $prevValue !== $value) {
                // E.g., user had +1, now clicked -1. We cancel the +1.
                // Or user had -1, now clicked +1. We cancel the -1.
                $newNet = (int)($post->net_votes ?? 0) - $prevValue;
                if ($prev) {
                    $prev->delete();
                }
                $value = 0; // Means vote removed
            } elseif ($prevValue === $value) {
                // If they click the exact same button they already voted with, also cancel it
                $newNet = (int)($post->net_votes ?? 0) - $value;
                if ($prev) {
                    $prev->delete();
                }
                $value = 0; // Means vote removed
            } else {
                // Fresh vote
                $newNet = (int)($post->net_votes ?? 0) - $prevValue + $value;

                if ($prev) {
                    $prev->value = $value;
                    $prev->save();
                } else {
                    PostVote::insert([
                        'post_id' => $postId,
                        'user_id' => $user->id,
                        'value' => $value,
                        'created_at' => \now()
                    ]);
                }
            }

            $post->net_votes = $newNet;
            $post->save();

            if ($isAjax) {
                $this->json(['ok' => true, 'net_votes' => $newNet, 'voted' => $value]);
                return '';
            }

            $url = core_url('topic/' . topic_url_path_by_id($post->topic_id)) . '#post-' . $postId;
            $this->redirect($url);
            return '';
        } catch (\Throwable $e) {
            error_log('Post vote error: ' . $e->getMessage());
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => 'Sistem hatası oluştu.'], 500);
                return '';
            }
            $this->app->session()->getFlashBag()->add('topic_error', 'Sistem hatası oluştu.');
            $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
            return '';
        }
    }

    /**
     * Question/solution: topic author marks a reply as accepted solution.
     */
    public function acceptSolution(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('accept_solution', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId < 1) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.invalid_message'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $topic = Topic::find($topicId);

        if (!$topic || ($topic->type ?? '') !== 'question') {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.not_question_type'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if ((int)$topic->user_id !== (int)$user->id) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.only_author_accept_solution'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $post = Post::where('id', $postId)->where('topic_id', $topicId)->first();
        if (!$post) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.post_not_in_topic'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (!empty($post->is_first_post)) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.first_post_is_question'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        try {
            $topic->accepted_post_id = $postId;
            $topic->is_solved = 1;
            $topic->save();
            $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.solution_accepted'));
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.save_error'));
        }
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /**
     * Soru/çözüm: konu sahibi kabul edilen çözümü kaldırır.
     */
    public function removeSolution(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('remove_solution', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $topic = Topic::find($topicId);
        if (!$topic || ($topic->type ?? '') !== 'question') {
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if ((int)$topic->user_id !== (int)$user->id) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.only_author_accept_solution'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        try {
            $topic->accepted_post_id = null;
            $topic->is_solved = 0;
            $topic->save();
            $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.solution_removed'));
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.save_error'));
        }
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /**
     * Mesaj (cevap) düzenleme formu. Yetki: mesaj sahibi veya admin (role_id=1) / moderatör (role_id=2).
     */
    public function editPost(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('auth_error', core__('auth.login_required'));
            $this->redirect(core_url('login'));
            return '';
        }
        $postId = resolve_post_id($id);
        if ($postId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $post = Post::find($postId);

        if (!$post) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if (!$this->canEditPost($user, $post)) {
            $this->app->session()->getFlashBag()->add('post_error', lang('topic.no_edit_post_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
            return '';
        }

        $topic = Topic::with('forum')->find($post->topic_id);

        // Templates expect stdClass object properties
        if ($topic) {
            $topic = (object)$topic->toArray();
            $topic->forum_name = $topic->forum['name'] ?? '';
            $topic->forum_slug = $topic->forum['slug'] ?? '';
        }
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        $error = $this->app->session()->getFlashBag()->get('post_edit_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;

        $postBody = $this->editorContentFromPost($post->body ?? null, $post->body_html ?? null);
        $postBodyBase64 = $postBody !== '' ? base64_encode($postBody) : '';

        return $this->layout('posts/edit', [
            'topic' => $topic,
            'post' => $post,
            'pageTitle' => lang('topic.edit_post_page_title'),
            'error' => $error,
            'post_body_base64' => $postBodyBase64,
        ], false);
    }

    /**
     * Mesaj düzenleme kaydet.
     */
    public function updatePost(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }

        $postId = resolve_post_id($id);
        if ($postId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if (!core_csrf_valid('edit_post', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('post_edit_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('post/' . post_url_path_by_id($postId) . '/edit'));
            return '';
        }

        $ip = \App\Services\SecurityService::clientIp();
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_EDIT_POST, (int) $user->id, $ip);
        if (!$r['allowed']) {
            $this->app->session()->getFlashBag()->add('post_edit_error', $r['message']);
            $this->redirect(core_url('post/' . post_url_path_by_id($postId) . '/edit'));
            return '';
        }

        $post = Post::find($postId);

        if (!$post) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if (!$this->canEditPost($user, $post)) {
            $this->app->session()->getFlashBag()->add('post_error', lang('topic.no_edit_post_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
            return '';
        }

        $body = trim((string)($_POST['body'] ?? ''));
        $cleanBody = trim(strip_tags(str_replace(['&nbsp;', '&#160;', '&zwj;'], '', $body)));
        $hasEmbedOnly = ($cleanBody === '' && (str_contains($body, '<iframe') || str_contains($body, 'mfbb-media-embed') || str_contains($body, 'class="mfbb-media-embed"')));
        if ($cleanBody === '' && !$hasEmbedOnly) {
            $this->app->session()->getFlashBag()->add('post_edit_error', lang('topic.body_required'));
            $this->redirect(core_url('post/' . post_url_path_by_id($postId) . '/edit'));
            return '';
        }

        $censorship = $this->app->censorship();
        if ($censorship->isCensorshipEnabled() && $censorship->applyToPosts()) {
            $bodyCheck = $censorship->checkContent($body);
            if (!$bodyCheck['allowed']) {
                $this->app->session()->getFlashBag()->add('post_edit_error', lang('censorship.content_blocked'));
                $this->redirect(core_url('post/' . post_url_path_by_id($postId) . '/edit'));
                return '';
            }
            $body = $bodyCheck['filtered_text'];
        }

        $currentUserId = (int)$user->id;

        try {
            $oldPost = clone $post;
            $editReason = trim((string)($_POST['edit_reason'] ?? ''));
            PostEdit::create([
                'post_id' => (int) $postId,
                'user_id' => $currentUserId,
                'old_body' => $oldPost->body,
                'edit_reason' => $editReason ?: null,
                'created_at' => \now(),
            ]);

            // Mesaj başına yalnızca son N düzenleme geçmişi tutulur (veritabanı şişmesini önler)
            $keepIds = PostEdit::where('post_id', $postId)->orderByDesc('id')->limit(self::POST_EDIT_HISTORY_LIMIT)->pluck('id')->toArray();
            if (!empty($keepIds)) {
                PostEdit::where('post_id', $postId)->whereNotIn('id', $keepIds)->delete();
            }

            $bodyHtml = core_body_to_html($body);
            $bodyHtml = core_process_mentions($bodyHtml);
            $bodyHtml = core_process_post_refs($bodyHtml, (int)$post->topic_id);

            $post->body = $body;
            $post->body_html = $bodyHtml;
            $post->edited_at = \now();
            $post->edited_by = $currentUserId;
            $post->edit_count += 1;
            $post->updated_at = \now();
            $post->save();

            $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_EDIT_POST, (int) $user->id, $ip);
            $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
            return '';
        } catch (\Throwable $e) {
            error_log('Post edit error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('post_edit_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('post/' . post_url_path_by_id($postId) . '/edit'));
            return '';
        }
    }

    /**
     * Mesaj düzenleme geçmişi.
     */
    public function editHistory(string $id): string
    {
        $postId = resolve_post_id($id);
        if ($postId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $post = Post::with('topic')->find($postId);

        if (!$post) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        // Objeye dönüştürüp beklenen title'ı ekleyelim (eski template yapısı topic_title bekliyor olabilir)
        $postObj = (object)$post->toArray();
        $postObj->topic_title = $post->topic->title ?? '';

        $edits = PostEdit::where('post_id', $postId)
            ->with('user:id,username')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($e) => (object) array_merge($e->toArray(), ['username' => $e->user->username ?? null]))
            ->toArray();
        return $this->layout('edit_history', [
            'pageTitle' => lang('topic.edit_history_title'),
            'post' => $postObj,
            'edits' => $edits,
        ], false);
    }

    /**
     * Mesaj beğeni aç/kapat. AJAX isteğinde JSON döner (sayfa yenilenmez).
     */
    public function togglePostLike(string $id): string
    {
        $user = $this->app->auth()->user();
        $isAjax = $this->isAjaxRequest();

        if (!$user) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => lang('topic.login_required')], 401);
                return '';
            }
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('post_like', (string)($_POST['_token'] ?? ''))) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => core__('common.invalid_csrf')], 403);
                return '';
            }
            $this->app->session()->getFlashBag()->add('like_error', core__('common.invalid_csrf'));
            $this->redirect(core_url(''));
            return '';
        }

        $ip = \App\Services\SecurityService::clientIp();
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_LIKE, (int) $user->id, $ip);
        if (!$r['allowed']) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => $r['message']], 429);
                return '';
            }
            $this->app->session()->getFlashBag()->add('like_error', $r['message']);
            $this->redirect(core_url(''));
            return '';
        }

        $postId = resolve_post_id($id);
        if ($postId === null) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => lang('error.not_found')], 404);
                return '';
            }
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $post = Post::find($postId);
        if (!$post) {
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => lang('error.not_found')], 404);
                return '';
            }
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if ((int)$post->user_id === (int)$user->id) {
            $msg = lang('topic.cannot_like_own');
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => $msg], 400);
                return '';
            }
            $this->app->session()->getFlashBag()->add('like_error', $msg);
            $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
            return '';
        }

        $alreadyLiked = PostLike::where('post_id', $postId)->where('user_id', $user->id)->exists();

        $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_LIKE, (int) $user->id, $ip);

        $newCount = (int)$post->like_count;
        try {
            if ($alreadyLiked) {
                PostLike::where('post_id', $postId)->where('user_id', $user->id)->delete();
                $post->decrement('like_count');
                $newCount = max(0, (int)$post->fresh()->like_count);
            } else {
                PostLike::firstOrCreate(
                    ['post_id' => $postId, 'user_id' => $user->id],
                    ['created_at' => \now()]
                );
                $post->increment('like_count');
                $newCount = (int)$post->fresh()->like_count;

                $topicTitleShort = '';
                $topicTitle = Topic::where('id', $post->topic_id)->value('title');

                if ($topicTitle && !empty($topicTitle)) {
                    $topicTitleShort = mb_strlen($topicTitle) > 25 ? mb_substr($topicTitle, 0, 25) . '…' : $topicTitle;
                }
                (new \App\Services\UserActivityService())->log((int)$user->id, \App\Services\UserActivityService::ACTION_LIKE_GIVEN, (int)$postId, [
                    'topic_id' => $post->topic_id,
                    'owner_id' => $post->user_id,
                    'topic_title' => $topicTitleShort,
                ]);

                if ((int)$post->user_id !== (int)$user->id) {
                    try {
                        $this->app->event()->dispatch(new \App\Events\PostLiked($post, $user), \App\Events\PostLiked::NAME);
                    } catch (\Throwable $e) {
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('Post like error: ' . $e->getMessage());
            $errMsg = lang('topic.operation_failed');
            if (core_config('app.debug', false)) {
                $errMsg .= ': ' . $e->getMessage();
            }
            if ($isAjax) {
                $this->json(['ok' => false, 'error' => $errMsg], 500);
                return '';
            }
            $this->app->session()->getFlashBag()->add('like_error', $errMsg);
        }

        if ($isAjax) {
            $this->json(['ok' => true, 'liked' => $alreadyLiked ? 0 : 1, 'count' => $newCount]);
            return '';
        }
        $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
        return '';
    }

    /**
     * Report post. Login required; report reason required.
     */
    public function reportPost(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('report_error', lang('topic.report_login_required'));
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('post_report', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('report_error', core__('common.invalid_csrf'));
            $this->redirect(core_url(''));
            return '';
        }

        $postId = resolve_post_id($id);
        if ($postId === null) {
            $this->app->session()->getFlashBag()->add('report_error', lang('error.not_found'));
            $this->redirect(core_url(''));
            return '';
        }
        $post = Post::find($postId);
        $redirectTopic = $post ? core_url('topic/' . topic_url_path_by_id($post->topic_id)) : core_url('');

        $ip = \App\Services\SecurityService::clientIp();
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_REPORT, (int) $user->id, $ip);
        if (!$r['allowed']) {
            $this->app->session()->getFlashBag()->add('report_error', $r['message']);
            $this->redirect($redirectTopic);
            return '';
        }

        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') {
            $this->app->session()->getFlashBag()->add('report_error', lang('topic.report_reason_required'));
            $this->redirect($redirectTopic);
            return '';
        }

        if (!$post) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        try {
            PostReport::create([
                'post_id' => (int) $postId,
                'reporter_user_id' => $user->id,
                'reason' => $reason,
                'status' => PostReport::STATUS_PENDING,
                'created_at' => \now(),
            ]);

            $postAuthorId = $post->user_id;
            if ($postAuthorId && (int)$postAuthorId !== (int)$user->id) {
                try {
                    $this->app->event()->dispatch(new \App\Events\PostReported($post, $user), \App\Events\PostReported::NAME);
                } catch (\Throwable $e) {
                }
            }
            $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_REPORT, (int) $user->id, $ip);
            $this->app->session()->getFlashBag()->add('report_ok', lang('topic.report_received'));
        } catch (\Throwable $e) {
            error_log('Post report error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('report_error', lang('topic.report_save_failed'));
        }
        $this->redirect(core_url('topic/' . topic_url_path_by_id($post->topic_id)));
        return '';
    }

    private function canEditPost(object $user, object $post): bool
    {
        $rid = (int)($user->role_id ?? 0);
        if ($rid === 1 || $rid === 2) {
            return true;
        }
        if ((int)$post->user_id !== (int)$user->id) {
            return false;
        }
        $timeout = (int) $this->getSetting('edit_timeout_minutes', '0');
        if ($timeout > 0 && !empty($post->created_at)) {
            $created = strtotime($post->created_at);
            if ($created !== false && (time() - $created) > ($timeout * 60)) {
                return false;
            }
        }
        return true;
    }

    public function delete(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }

        if (!core_csrf_valid('delete_topic', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $topic = Topic::with('forum')->find($topicId);

        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        // Konu sahibi veya admin (1) / moderatör (2) silebilir
        $rid = (int)($user->role_id ?? 0);
        if ($topic->user_id != $user->id && $rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_delete_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        try {
            $topicService = core_make(\App\Services\TopicService::class);
            $topicService->deleteTopic($topic, (int) $user->id);

            $this->app->event()->dispatch(new \App\Events\TopicDeleted($topic, (int) $user->id), \App\Events\TopicDeleted::NAME);

            // Ana sayfa ve portal cache'ini temizle; silinen konu listeden hemen kalkar
            $cache = $this->app->cache();
            $cache->delete('home_categories');
            $cache->delete('forum_stats');

            $this->redirect(core_url("forum/{$topic->forum->slug}"));
            return '';
        } catch (\Throwable $e) {
            error_log('Topic delete error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('topic_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
    }

    /** Konu kilidi aç/kapat. Sadece admin/moderatör. Kilitliyken kimse (admin dahil) cevap yazamaz. */
    public function toggleLock(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $rid = (int)($user->role_id ?? 0);
        if ($rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (!core_csrf_valid('topic_action', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $topic = Topic::find($topicId);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $newLock = (int)($topic->is_locked ?? 0) ? 0 : 1;

        $topic->is_locked = $newLock;
        $topic->updated_at = \now();
        $topic->save();

        $this->app->session()->getFlashBag()->add('topic_ok', $newLock ? lang('topic.locked_success') : lang('topic.unlocked_success'));
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /** Konu sabitle / sabit kaldır. Sadece admin/moderatör. */
    public function toggleSticky(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $rid = (int)($user->role_id ?? 0);
        if ($rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (!core_csrf_valid('topic_action', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $topic = Topic::find($topicId);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $newSticky = (int)($topic->is_sticky ?? 0) ? 0 : 1;

        $topic->is_sticky = $newSticky;
        $topic->updated_at = \now();
        $topic->save();

        $this->app->session()->getFlashBag()->add('topic_ok', $newSticky ? lang('topic.sticky_set') : lang('topic.sticky_removed'));
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /** Konuyu yukarı taşı (bump). Rol bazlı günlük bump hakkı topic_bumps ile sınırlı. */
    public function bump(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('topic_action', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $topic = Topic::find($topicId);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if (isset($topic->type) && $topic->type === 'article') {
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $role = $user->role;
        if (!$role || $role->bump_per_day <= 0) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('quota.bump_exceeded'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $today = \now()->format('Y-m-d');
        $bumpedToday = \Illuminate\Database\Capsule\Manager::table('topic_bumps')
            ->where('user_id', $user->id)
            ->where('bumped_at', $today)
            ->count();
        if ($bumpedToday >= $role->bump_per_day) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('quota.bump_exceeded'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        \Illuminate\Database\Capsule\Manager::table('topic_bumps')->insert([
            'user_id' => $user->id,
            'topic_id' => (int) $topic->id,
            'bumped_at' => $today,
            'created_at' => \now(),
        ]);
        $topic->last_post_at = \now();
        $topic->last_post_user_id = $user->id;
        $topic->updated_at = \now();
        $topic->save();

        $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.bump_success'));
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /** Konuyu makaleye dönüştür. Sadece admin/moderatör. Makaleler /article/{id} ve /articles listesinde görünür. */
    public function convertToArticle(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $rid = (int)($user->role_id ?? 0);
        if ($rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (!core_csrf_valid('topic_action', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $topic = Topic::find($topicId);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if (isset($topic->type) && $topic->type === 'article') {
            $this->redirect(core_url(article_url_path_by_id($topicId)));
            return '';
        }
        $articleForumId = (int) $this->getSetting('article_forum_id', '0');

        $topic->type = 'article';
        $topic->updated_at = \now();
        if ($articleForumId > 0) {
            $topic->forum_id = $articleForumId;
        }
        $topic->save();

        $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.converted_to_article'));
        $this->redirect(core_url(article_url_path_by_id($topicId)));
        return '';
    }

    /** Makaleyi foruma dönüştür. Sadece admin/moderatör. */
    public function convertToForum(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $rid = (int)($user->role_id ?? 0);
        if ($rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_permission'));
            $this->redirect(core_url(article_url_path_by_id($topicId)));
            return '';
        }
        if (!core_csrf_valid('topic_action', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url(article_url_path_by_id($topicId)));
            return '';
        }
        $topic = Topic::find($topicId);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if (!isset($topic->type) || $topic->type !== 'article') {
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $topic->type = 'topic';
        $topic->updated_at = \now();
        $topic->save();

        $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.converted_to_forum'));
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /**
     * Mesaj toplu işlem: birleştir veya sil. Sadece admin/moderatör.
     * POST: post_ids[] (array), action = merge | delete
     */
    public function postsBulk(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $rid = (int)($user->role_id ?? 0);
        if ($rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (!core_csrf_valid('posts_bulk', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $postIds = isset($_POST['post_ids']) && is_array($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        $postIds = array_filter(array_unique($postIds));
        $action = (string)($_POST['action'] ?? '');
        if (count($postIds) < 2 && $action === 'merge') {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.merge_min_two'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (count($postIds) < 1) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.select_messages'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $topic = Topic::find($topicId);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if ($action === 'merge') {
            sort($postIds);
            $firstPostId = (int)($topic->first_post_id ?? 0);
            $postIds = array_filter($postIds, fn ($pid) => $pid !== $firstPostId); // ilk mesajı birleştirme listesinden çıkar
            if (count($postIds) < 2) {
                $this->app->session()->getFlashBag()->add('topic_error', lang('topic.merge_first_exclude'));
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                return '';
            }
            $keepId = (int)$postIds[0];
            $mergeIds = array_slice($postIds, 1);
            $mergeIds = array_filter($mergeIds, fn ($pid) => $pid !== $firstPostId); // ilk mesaj asla silinmesin

            $keepPost = Post::where('topic_id', $topicId)->where('id', $keepId)->first();
            if (!$keepPost) {
                $this->app->session()->getFlashBag()->add('topic_error', lang('topic.target_post_not_found'));
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                return '';
            }
            $newBody = $keepPost->body;
            $newBodyHtml = $keepPost->body_html;

            $others = Post::where('topic_id', $topicId)->whereIn('id', $mergeIds)->get();
            foreach ($others as $other) {
                $newBody .= "\n\n" . $other->body;
                $newBodyHtml .= '<p class="merge-sep"></p>' . ($other->body_html ?? '');
            }

            try {
                DB::connection()->beginTransaction();
                $keepPost->body = $newBody;
                $keepPost->body_html = $newBodyHtml;
                $keepPost->updated_at = \now();
                $keepPost->save();

                Post::where('topic_id', $topicId)->whereIn('id', $mergeIds)->delete(); // this is soft delete
                PostLike::whereIn('post_id', $mergeIds)->delete();
                PostReport::whereIn('post_id', $mergeIds)->delete();

                $delta = count($mergeIds);
                $topic->reply_count = max(0, $topic->reply_count - $delta);
                $topic->updated_at = \now();
                $topic->save();

                Forum::where('id', $topic->forum_id)->update(['post_count' => DB::raw('GREATEST(0, CAST(post_count AS SIGNED) - ' . (int) $delta . ')')]);
                DB::table('forum_stats')->where('id', 1)->update([
                    'total_posts' => DB::raw('GREATEST(0, CAST(total_posts AS SIGNED) - ' . (int) $delta . ')'),
                ]);

                DB::connection()->commit();
                $alertSvc = new UserAlertService();
                foreach ($mergeIds as $mid) {
                    $alertSvc->deleteForContent('post', (int) $mid);
                }
                $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.merge_success'));
            } catch (\Throwable $e) {
                DB::connection()->rollBack();
                error_log('Topic posts bulk merge: ' . $e->getMessage());
                $this->app->session()->getFlashBag()->add('topic_error', $this->dbErrorMessage($e));
            }
        } elseif ($action === 'delete') {
            $postsToDelete = Post::where('topic_id', $topicId)->whereIn('id', $postIds)->get();
            $toDelete = [];
            $hasFirst = false;
            foreach ($postsToDelete as $row) {
                if (!empty($row->is_first_post)) {
                    $hasFirst = true;
                }
                $toDelete[] = (int)$row->id;
            }
            if ($hasFirst) {
                $this->app->session()->getFlashBag()->add('topic_error', lang('topic.first_post_cannot_delete'));
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                return '';
            }
            if (empty($toDelete)) {
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                return '';
            }
            $delta = count($toDelete);

            try {
                DB::connection()->beginTransaction();
                PostLike::whereIn('post_id', $toDelete)->delete();
                PostReport::whereIn('post_id', $toDelete)->delete();
                Post::where('topic_id', $topicId)->whereIn('id', $toDelete)->update(['deleted_at' => \now(), 'deleted_by' => $user->id]);

                $topic->reply_count = max(0, $topic->reply_count - $delta);
                $topic->updated_at = \now();
                $topic->save();

                Forum::where('id', $topic->forum_id)->update(['post_count' => DB::raw('GREATEST(0, CAST(post_count AS SIGNED) - ' . (int) $delta . ')')]);
                DB::table('forum_stats')->where('id', 1)->update([
                    'total_posts' => DB::raw('GREATEST(0, CAST(total_posts AS SIGNED) - ' . (int) $delta . ')'),
                ]);

                DB::connection()->commit();

                $alertSvc = new UserAlertService();
                foreach ($postsToDelete as $deletedPost) {
                    try {
                        $this->app->event()->dispatch(new \App\Events\PostDeleted($deletedPost, (int) $user->id), \App\Events\PostDeleted::NAME);
                    } catch (\Throwable $e) {
                        // Eklenti hatası ana akışı bozmasın
                    }
                    $alertSvc->deleteForContent('post', (int) $deletedPost->id);
                }

                $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.posts_deleted'));
            } catch (\Throwable $e) {
                DB::connection()->rollBack();
                error_log('Topic posts bulk delete: ' . $e->getMessage());
                $this->app->session()->getFlashBag()->add('topic_error', $this->dbErrorMessage($e));
            }
        } else {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.invalid_action'));
        }
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    /** Seçilen mesajları toplu raporla. Sadece admin/moderatör. */
    public function postsBulkReport(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $rid = (int)($user->role_id ?? 0);
        if ($rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (!core_csrf_valid('posts_bulk_report', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.report_reason_required'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        $postIds = isset($_POST['post_ids']) && is_array($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        $postIds = array_filter(array_unique($postIds));
        if (empty($postIds)) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.select_messages'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }
        if (!Topic::where('id', $topicId)->exists()) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        // Sadece bu konuya ait mesajları raporla (güvenlik: başka konudan post_id gönderilmesin)
        $allowedIds = Post::where('topic_id', $topicId)->whereIn('id', $postIds)->pluck('id')->toArray();
        $insertData = [];
        foreach ($allowedIds as $postId) {
            $insertData[] = [
                'post_id' => $postId,
                'reporter_user_id' => $user->id,
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => \now(),
            ];
        }
        if (!empty($insertData)) {
            PostReport::insert($insertData);
        }

        $this->app->session()->getFlashBag()->add('topic_ok', lang('topic.reports_submitted', ['count' => count($allowedIds)]));
        $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
        return '';
    }

    public function moveForm(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('auth_error', core__('auth.login_required'));
            $this->redirect(core_url('login'));
            return '';
        }

        $topic = Topic::find($topicId);

        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if (($user->role_id ?? 0) !== 1) { // Only admin for now
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_action_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $forums = \App\Models\Forum::orderBy('name', 'asc')->get(['id', 'name']);

        // UI may expect legacy stdClass/json structure
        // stdclass olarak hazırlıyoruz:
        $topicObj = (object)$topic->toArray();
        $forumsArr = json_decode(json_encode($forums));

        $error = $this->app->session()->getFlashBag()->get('topic_move_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;

        return $this->layout('topics/move', [
            'topic' => $topicObj,
            'forums' => $forumsArr,
            'pageTitle' => lang('topic.move_page_title', ['title' => $topic->title]),
            'error' => $error
        ], false);
    }

    public function move(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user || ($user->role_id ?? 0) !== 1) {
            $this->redirect(core_url('login'));
            return '';
        }

        if (!core_csrf_valid('move_topic', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_move_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/move'));
            return '';
        }

        $newForumId = (int)($_POST['forum_id'] ?? 0);

        if (!\App\Models\Forum::where('id', $newForumId)->exists()) {
            $this->app->session()->getFlashBag()->add('topic_move_error', lang('topic.move_target_forum_not_found'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/move'));
            return '';
        }

        $topic = Topic::find($topicId);

        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if ($topic->forum_id == $newForumId) {
            $this->app->session()->getFlashBag()->add('topic_move_error', lang('topic.move_already_in_forum'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/move'));
            return '';
        }

        try {
            $topicService = core_make(\App\Services\TopicService::class);
            if ($topicService->moveTopic($topicId, $newForumId)) {
                $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
                return '';
            }

            $this->app->session()->getFlashBag()->add('topic_move_error', lang('topic.move_target_forum_not_found'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/move'));
            return '';
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('topic_move_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/move'));
            return '';
        }
    }

    public function mergeForm(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('auth_error', core__('auth.login_required'));
            $this->redirect(core_url('login'));
            return '';
        }

        $topic = Topic::find($topicId);

        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        if (($user->role_id ?? 0) !== 1) { // Only admin for now
            $this->app->session()->getFlashBag()->add('topic_error', lang('topic.no_action_permission'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId)));
            return '';
        }

        $error = $this->app->session()->getFlashBag()->get('topic_merge_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;

        return $this->layout('topics/merge', [
            'topic' => (object) $topic->only(['id', 'title', 'forum_id', 'user_id']),
            'pageTitle' => lang('topic.merge_page_title', ['title' => $topic->title]),
            'error' => $error
        ], false);
    }

    public function merge(string $id): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user || ($user->role_id ?? 0) !== 1) {
            $this->redirect(core_url('login'));
            return '';
        }

        if (!core_csrf_valid('merge_topic', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_merge_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/merge'));
            return '';
        }

        $targetTopicId = (int)($_POST['target_topic_id'] ?? 0);

        if ($targetTopicId === (int)$topicId) {
            $this->app->session()->getFlashBag()->add('topic_merge_error', lang('topic.merge_same_topic'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/merge'));
            return '';
        }

        $sourceTopic = Topic::find($topicId);
        if (!$sourceTopic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $targetTopic = Topic::find($targetTopicId);
        if (!$targetTopic) {
            $this->app->session()->getFlashBag()->add('topic_merge_error', lang('topic.merge_target_not_found'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/merge'));
            return '';
        }

        try {
            $topicService = core_make(\App\Services\TopicService::class);
            if ($topicService->mergeTopics($topicId, $targetTopicId)) {
                $this->redirect(core_url('topic/' . topic_url_path_by_id($targetTopicId)));
                return '';
            }

            $this->app->session()->getFlashBag()->add('topic_merge_error', lang('topic.merge_target_not_found'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/merge'));
            return '';
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('topic_merge_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicId) . '/merge'));
            return '';
        }
    }

    private function getUserPreference(int $userId, string $key): string
    {
        try {
            $v = UserPreference::where('user_id', $userId)->where('preference_key', $key)->value('value');
            return $v !== null ? (string) $v : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Sends "a reply was posted to your topic" email to topic author (if follow_created_email is on). */
    private function sendTopicReplyEmail(int $toUserId, string $topicTitle, string $fromUsername, string $topicUrl): void
    {
        try {
            $topicUrl = $this->toAbsoluteUrl($topicUrl);
            $u = \App\Models\User::where('id', $toUserId)->whereNotNull('email')->where('email', '!=', '')->first();
            if (!$u) {
                return;
            }
            $mailer = new \App\Services\MailService($this->app);
            $subject = lang('topic.email_reply_subject', ['title' => mb_substr($topicTitle, 0, 60)]);
            $bodyHtml = '<p>' . lang('topic.email_hello', ['name' => htmlspecialchars($u->username)]) . '</p>';
            $bodyHtml .= '<p><strong>' . htmlspecialchars($fromUsername) . '</strong> ' . lang('topic.email_reply_intro') . '</p>';
            $bodyHtml .= '<p><a href="' . htmlspecialchars($topicUrl) . '">' . htmlspecialchars($topicTitle) . '</a></p>';
            $mailer->send($u->email, $subject, $bodyHtml, strip_tags($bodyHtml));
        } catch (\Throwable $e) {
        }
    }

    /** Sends "reply posted to topic" email to subscribers who have email preference enabled. */
    private function sendSubscriberReplyEmails(array $subscriberIds, int $topicId, string $topicTitle, string $fromUsername, string $topicUrl): void
    {
        if (empty($subscriberIds)) {
            return;
        }
        $topicUrl = $this->toAbsoluteUrl($topicUrl);
        $users = \App\Models\User::whereIn('id', array_map('intval', $subscriberIds))->whereNotNull('email')->where('email', '!=', '')->get();
        $mailer = new \App\Services\MailService($this->app);
        foreach ($users as $u) {
            try {
                if ($this->getUserPreference((int) $u->id, 'follow_interacted_email') !== '1') {
                    continue;
                }
                $subject = lang('topic.email_new_reply_subject', ['title' => mb_substr($topicTitle, 0, 60)]);
                $bodyHtml = '<p>' . lang('topic.email_hello', ['name' => htmlspecialchars($u->username)]) . '</p>';
                $bodyHtml .= '<p><strong>' . htmlspecialchars($fromUsername) . '</strong> ' . lang('topic.email_subscriber_intro') . '</p>';
                $bodyHtml .= '<p><a href="' . htmlspecialchars($topicUrl) . '">' . htmlspecialchars($topicTitle) . '</a></p>';
                $bodyHtml .= '<p style="color:#666;font-size:12px;">' . lang('topic.notification_preference_hint') . '</p>';
                $mailer->send($u->email, $subject, $bodyHtml, strip_tags($bodyHtml));
            } catch (\Throwable $e) {
            }
        }
    }

    private function toAbsoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return function_exists('full_site_url') ? full_site_url('') : '/';
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (function_exists('full_site_url')) {
            return full_site_url(ltrim($url, '/'));
        }

        $base = rtrim((string) core_config('app.url', ''), '/');
        if ($base === '') {
            $scheme = \App\Services\SecurityService::isHttpsRequest() ? 'https' : 'http';
            $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }

        return $base . '/' . ltrim($url, '/');
    }

    /** @return array{topic: ?Topic, user: ?\App\Models\User, blocked: array<int>} */
    private function resolveLiveTopicContext(int $topicId): array
    {
        $topic = Topic::with('forum')->find($topicId);
        if (!$topic) {
            return ['topic' => null, 'user' => null, 'blocked' => []];
        }

        $user = $this->app->auth()->user();
        $currentUserId = $user ? (int) $user->id : null;

        if (($topic->status ?? '') === Topic::STATUS_CANCELLED) {
            return ['topic' => null, 'user' => $user, 'blocked' => []];
        }
        $isScheduledOwn = ($topic->status ?? '') === Topic::STATUS_SCHEDULED
            && $currentUserId
            && (int) $topic->user_id === $currentUserId;
        if (($topic->status ?? '') === Topic::STATUS_SCHEDULED && !$isScheduledOwn) {
            return ['topic' => null, 'user' => $user, 'blocked' => []];
        }

        if ((int) ($topic->is_private ?? 0) === 1) {
            $rid = $user ? (int) ($user->role_id ?? 0) : 0;
            $isOwnerOrStaff = $currentUserId === (int) $topic->user_id || $rid === 1 || $rid === 2;
            $isAllowedViewer = $currentUserId
                && \App\Models\TopicPrivateViewer::where('topic_id', $topicId)->where('user_id', $currentUserId)->exists();
            if (!$isOwnerOrStaff && !$isAllowedViewer) {
                return ['topic' => null, 'user' => $user, 'blocked' => []];
            }
        }

        if ($user && !$user->hasPermission('forum.view', $topic->forum)) {
            return ['topic' => null, 'user' => $user, 'blocked' => []];
        }

        $blocked = $currentUserId ? $this->layoutService()->getBlockedUserIds($currentUserId) : [];
        if (!empty($blocked) && in_array((int) ($topic->user_id ?? 0), $blocked, true)) {
            return ['topic' => null, 'user' => $user, 'blocked' => []];
        }

        return ['topic' => $topic, 'user' => $user, 'blocked' => $blocked];
    }

    public function liveRepliesStream(string $id): never
    {
        $topicId = $this->resolveTopicIdentifier($id);
        if ($topicId === null) {
            http_response_code(404);
            exit;
        }
        $ctx = $this->resolveLiveTopicContext($topicId);
        if (!$ctx['topic']) {
            http_response_code(403);
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
        if ($lastId <= 0) {
            $lastId = (int) ($_GET['last_id'] ?? 0);
        }
        $blocked = $ctx['blocked'];
        $maxIterations = 12;
        $sleepSeconds = 2;

        for ($i = 0; $i < $maxIterations; $i++) {
            if (connection_aborted()) {
                exit;
            }

            $query = Post::where('topic_id', $topicId)
                ->whereNull('deleted_at')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(1);
            if (!empty($blocked)) {
                $query->whereNotIn('user_id', $blocked);
            }
            $latest = $query->first(['id', 'user_id', 'created_at']);

            if ($latest) {
                $lastId = (int) $latest->id;
                echo "event: topic-reply\n";
                echo 'id: ' . $lastId . "\n";
                echo 'data: ' . json_encode([
                    'topic_id' => (int) $topicId,
                    'post_id' => $lastId,
                    'user_id' => (int) ($latest->user_id ?? 0),
                    'created_at' => $this->formatDateTime($latest->created_at),
                ], JSON_UNESCAPED_UNICODE) . "\n\n";
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

    public function livePostCard(string $id, string $postId): string
    {
        $topicId = $this->resolveTopicIdentifier($id);
        $pid = (int) $postId;
        if ($topicId === null || $pid <= 0) {
            return $this->liveJson(['ok' => false, 'error' => 'Invalid request'], 400);
        }

        $ctx = $this->resolveLiveTopicContext($topicId);
        if (!$ctx['topic']) {
            return $this->liveJson(['ok' => false, 'error' => 'Forbidden'], 403);
        }

        $post = Post::with([
            'user' => function ($q) {
                $q->withCount(['posts as user_post_count' => fn ($q2) => $q2->whereNull('deleted_at')])
                    ->withSum(['posts as user_like_count' => fn ($q2) => $q2->whereNull('deleted_at')], 'like_count');
            },
            'user.role',
            'attachments',
            'replyTo.user',
            'replies.user',
        ])
            ->where('id', $pid)
            ->where('topic_id', $topicId)
            ->whereNull('deleted_at')
            ->first();
        if (!$post) {
            return $this->liveJson(['ok' => false, 'error' => 'Not found'], 404);
        }

        $blocked = $ctx['blocked'];
        if (!empty($blocked) && in_array((int) ($post->user_id ?? 0), $blocked, true)) {
            return $this->liveJson(['ok' => false, 'error' => 'Not found'], 404);
        }

        $topic = $ctx['topic'];
        $currentUser = $ctx['user'];
        $currentUserId = $currentUser ? (int) $currentUser->id : null;
        $isQuestion = ($topic->type ?? 'topic') === 'question';
        $acceptedPostId = (int) ($topic->accepted_post_id ?? 0) ?: null;

        $likedByMe = false;
        $voteByMe = 0;
        if ($currentUserId !== null) {
            $likedByMe = PostLike::where('user_id', $currentUserId)->where('post_id', $pid)->exists();
            $voteByMe = (int) (PostVote::where('user_id', $currentUserId)->where('post_id', $pid)->value('value') ?? 0);
        }

        $u = $post->user;
        $onlineThreshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $isOnline = $u ? User::where('id', (int) $u->id)->where('last_activity_at', '>=', $onlineThreshold)->exists() : false;
        $userPostCount = (int) ($u->user_post_count ?? 0);
        $userLikeCount = (int) ($u->user_like_count ?? $u->posts_sum_like_count ?? 0);
        $repPos = (int) ($u->reputation_positive ?? 0);
        $repNeg = (int) ($u->reputation_negative ?? 0);
        $rewardLevel = RewardLevel::forUser($userPostCount, $repPos - $repNeg, $userLikeCount);

        $pObj = (object) [
            'id' => (int) $post->id,
            'reply_to_id' => $post->reply_to_id ? (int) $post->reply_to_id : null,
            'replied_post' => $post->replyTo ? (object) [
                'id' => $post->replyTo->id,
                'user_id' => $post->replyTo->user_id,
                'username' => $post->replyTo->user->username ?? '',
                'body_html' => $post->replyTo->body_html,
                'is_first_post' => (bool) $post->replyTo->is_first_post,
            ] : null,
            'inbound_replies' => $post->replies ? $post->replies->map(function ($r) {
                return (object) [
                    'id' => $r->id,
                    'user_id' => $r->user_id,
                    'username' => $r->user->username ?? '',
                    'body_html' => $r->body_html,
                    'created_at' => is_string($r->created_at) ? $r->created_at : ($r->created_at ? $r->created_at->format('Y-m-d H:i:s') : null),
                    'avatar_path' => $r->user->avatar_path ?? null,
                ];
            })->all() : [],
            'body' => $post->body,
            'body_html' => $post->body_html,
            'like_count' => (int) ($post->like_count ?? 0),
            'is_first_post' => (bool) ($post->is_first_post ?? false),
            'created_at' => $this->formatDateTime($post->created_at),
            'edited_at' => $this->formatDateTime($post->edited_at),
            'edit_count' => (int) ($post->edit_count ?? 0),
            'user_id' => $u->id ?? null,
            'username' => $u->username ?? '',
            'avatar_path' => $u->avatar_path ?? null,
            'location' => $u->location ?? null,
            'is_verified' => (bool) ($u->is_verified ?? false),
            'is_banned' => (bool) ($u->is_banned ?? false),
            'user_joined' => $this->formatDateTime($u->created_at),
            'role_name' => $u->role->name ?? null,
            'role_color' => $u->role->color ?? null,
            'custom_title' => $u->custom_title ?? null,
            'user_post_count' => $userPostCount,
            'user_like_count' => $userLikeCount,
            'reputation_positive' => $repPos,
            'reputation_negative' => $repNeg,
            'reward_points' => (int) ($u->reward_points ?? 0),
            'warning_points' => (int) ($u->warning_points ?? 0),
            'is_online' => $isOnline,
            'user_signature' => $u->signature ?? '',
            'reward_level' => $rewardLevel ? (object) ['id' => $rewardLevel->id, 'name' => $rewardLevel->name, 'badge_label' => $rewardLevel->badge_label, 'badge_icon' => $rewardLevel->badge_icon, 'badge_css' => $rewardLevel->badge_css] : null,
            'is_birthday_today' => $u->is_birthday_today ?? false,
            'first_name' => $u->first_name ?? null,
            'last_name' => $u->last_name ?? null,
            'show_name' => $u->show_name ?? null,
            'net_votes' => (int) ($post->net_votes ?? 0),
            'vote_by_me' => $voteByMe,
            'liked_by_me' => $likedByMe,
            'custom_fields' => [],
            'attachments' => $post->attachments->all(),
        ];
        $postsTemp = [$pObj];
        $this->attachPostbitCustomFields($postsTemp);
        $pObj = $postsTemp[0];

        $postLoopIndexQuery = Post::query()
            ->where('topic_id', $topicId)
            ->whereNull('deleted_at')
            ->where('id', '<=', $pid);
        if (!empty($blocked)) {
            $postLoopIndexQuery->whereNotIn('user_id', $blocked);
        }
        $postLoopIndex = max(1, (int) $postLoopIndexQuery->count());

        $topicObj = (object) [
            'id' => (int) $topic->id,
            'title' => (string) ($topic->title ?? ''),
            'slug' => (string) ($topic->slug ?? ''),
            'user_id' => (int) ($topic->user_id ?? 0),
            'is_locked' => (bool) ($topic->is_locked ?? false),
            'type' => (string) ($topic->type ?? 'topic'),
        ];

        $html = $this->app->twig('frontend')->render('partials/postbit.html.twig', [
            'p' => $pObj,
            'postLoopIndex' => $postLoopIndex,
            'topic' => $topicObj,
            'user' => $currentUser,
            'is_question' => $isQuestion,
            'accepted_post_id' => $acceptedPostId,
            'reputationEnabled' => $this->getSetting('reputation_enabled', '1') === '1',
            'enable_inline_quotes' => $this->getSetting('enable_inline_quotes', '1') === '1',
            'show_signatures_to_guests' => $this->getSetting('show_signatures_to_guests', '1') === '1',
            'post_vote_csrf' => $isQuestion ? core_csrf_token('post_vote') : '',
        ]);

        return $this->liveJson([
            'ok' => true,
            'post_id' => $pid,
            'html' => $html,
        ]);
    }

    private function liveJson(array $payload, int $statusCode = 200): string
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);

        return '';
    }

    public function showSinglePostByPos(string $topicId, string $pos): string
    {
        $topicIdInt = (int) $topicId;
        $offset = max(0, (int) $pos - 1);
        $topic = Topic::with('forum')->whereNull('deleted_at')->find($topicIdInt);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        if ((int) ($topic->is_private ?? 0) === 1) {
            $user = $this->app->auth()->user();
            $currentUserId = $user ? (int) $user->id : 0;
            $rid = $user ? (int) ($user->role_id ?? 0) : 0;
            $isOwnerOrStaff = $currentUserId === (int) $topic->user_id || $rid === 1 || $rid === 2;
            $isAllowedViewer = $currentUserId > 0 && \App\Models\TopicPrivateViewer::where('topic_id', $topicIdInt)->where('user_id', $currentUserId)->exists();
            if (!$isOwnerOrStaff && !$isAllowedViewer) {
                return $this->layout('topic/private_topic', [
                    'pageTitle' => lang('topic.private_page_title'),
                    'topic_title' => $topic->title,
                    'forum_slug' => $topic->forum->slug ?? '',
                    'forum_name' => $topic->forum->name ?? '',
                ], false);
            }
        }
        $topicObj = (object) [
            'id' => $topic->id,
            'title' => $topic->title,
            'slug' => $topic->slug,
            'forum_id' => $topic->forum_id,
            'forum_name' => $topic->forum->name ?? '',
            'forum_slug' => $topic->forum->slug ?? '',
            'type' => $topic->type ?? 'topic',
        ];
        $post = Post::with(['user' => fn ($q) => $q->withCount(['posts as post_count' => fn ($q2) => $q2->whereNull('deleted_at')]), 'user.role'])
            ->where('topic_id', $topicIdInt)->whereNull('posts.deleted_at')->orderBy('id')->offset($offset)->limit(1)->first();
        if (!$post) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $postObj = (object) [
            'id' => $post->id,
            'topic_id' => $post->topic_id,
            'user_id' => $post->user_id,
            'body_html' => $post->body_html,
            'created_at' => $this->formatDateTime($post->created_at),
            'updated_at' => $this->formatDateTime($post->updated_at),
            'edited_at' => $this->formatDateTime($post->edited_at),
            'edit_count' => (int) ($post->edit_count ?? 0),
            'is_first_post' => (bool) ($post->is_first_post ?? false),
            'username' => $post->user->username ?? null,
            'avatar_path' => $post->user->avatar_path ?? null,
            'is_banned' => (bool) ($post->user->is_banned ?? false),
            'role_name' => $post->user->role->name ?? null,
            'role_color' => $post->user->role->color ?? null,
            'post_count' => (int) ($post->user->post_count ?? 0),
        ];
        $user = $this->app->auth()->user();
        return $this->layout('topic/single_post', [
            'pageTitle' => lang('topic.single_post_page_title', ['title' => $topic->title]),
            'topic' => $topicObj,
            'post' => $postObj,
            'cu' => $user,
        ], false);
    }
}
