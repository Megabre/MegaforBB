<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\ProfileComment;
use App\Models\RewardLevel;
use App\Models\Topic;
use App\Models\TopicSubscription;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserPreference;
use App\Models\UserReputation;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Member list and member profile (topics, posts, likes, reputation).
 */
class MemberController extends BaseController
{
    public function index(): string
    {
        if ($this->getSetting('members_list_enabled', '1') !== '1') {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $sort = trim((string)($_GET['sort'] ?? 'posts'));
        $order = strtoupper((string)($_GET['order'] ?? 'desc'));
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $search = trim((string)($_GET['q'] ?? ''));

        $query = User::with('role')->withCount(['posts' => fn ($q) => $q->whereNull('deleted_at')])
            ->withCount(['topics' => fn ($q) => $q->whereNull('deleted_at')->where(fn ($q2) => $q2->whereIn('type', $this->getTopicListTypes())->orWhereNull('type'))])
            ->withSum(['posts' => fn ($q) => $q->whereNull('deleted_at')], 'like_count')
            ->where('is_banned', 0);
        if ($search !== '') {
            $query->where('username', 'like', '%' . $search . '%');
        }
        $total = $query->count();
        if ($sort === 'joined') {
            $query->orderBy('created_at', $order === 'DESC' ? 'desc' : 'asc');
        } elseif ($sort === 'reputation') {
            $query->orderByRaw('(COALESCE(reputation_positive,0) - COALESCE(reputation_negative,0)) ' . $order);
        } elseif ($sort === 'topics') {
            $query->orderBy('topics_count', $order === 'DESC' ? 'desc' : 'asc');
        } else {
            $query->orderBy('posts_count', $order === 'DESC' ? 'desc' : 'asc');
        }
        $query->orderBy('id', 'asc');
        $members = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
        $members = $members->map(fn ($u) => (object)[
            'id' => $u->id, 'username' => $u->username, 'avatar_path' => $u->avatar_path, 'created_at' => $u->created_at?->format('Y-m-d H:i:s'), 'location' => $u->location ?? null,
            'role_name' => $u->role?->name, 'role_color' => $u->role?->color,
            'reputation_net' => (int)($u->reputation_positive ?? 0) - (int)($u->reputation_negative ?? 0),
            'post_count' => $u->posts_count, 'topic_count' => $u->topics_count, 'like_count' => (int)($u->posts_sum_like_count ?? 0),
            'url_key' => $u->url_key ?? null,
        ])->all();

        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        $topPosters = User::withCount(['posts' => fn ($q) => $q->whereNull('deleted_at')])->where('is_banned', 0)
            ->having('posts_count', '>', 0)->orderByDesc('posts_count')->limit(5)->get(['id', 'username', 'avatar_path'])
            ->map(fn ($u) => (object)['id' => $u->id, 'username' => $u->username, 'avatar_path' => $u->avatar_path, 'post_count' => $u->posts_count])->all();
        $newestMembers = User::where('is_banned', 0)->orderByDesc('created_at')->limit(5)->get(['id', 'username', 'avatar_path', 'created_at'])->map(fn ($u) => (object)['id' => $u->id, 'username' => $u->username, 'avatar_path' => $u->avatar_path, 'created_at' => $u->created_at?->format('Y-m-d H:i:s')])->all();

        $stats = $this->getStats();
        $recentTopics = $this->getRecentTopics();
        $popularTopics = $this->getPopularTopics();
        $sidebarWidgets = \App\Models\SidebarWidget::getCachedList();

        return $this->layout('members/index', [
            'pageTitle' => lang('common.members'),
            'members' => $members,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'sort' => $sort,
            'order' => $order,
            'search' => $search,
            'reputationEnabled' => $this->getSetting('reputation_enabled', '1') === '1',
            'topPosters' => $topPosters,
            'newestMembers' => $newestMembers,
            'stats' => $stats,
            'recentTopics' => $recentTopics,
            'popularTopics' => $popularTopics,
            'sidebarWidgets' => $sidebarWidgets,
        ], true);
    }

    public function profile(string $username): string
    {
        $user = resolve_member($username);
        if (!$user) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = User::with('role', 'usedInvitation.inviter')->find($user->id);
        if (!$user) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user->role_name = $user->role?->name;
        $user->inviter_user = $user->usedInvitation?->inviter;
        $schema = DB::connection()->getSchemaBuilder();
        $user->show_invite_stats = $schema->hasColumn('users', 'available_invites');
        if ($user->show_invite_stats) {
            $user->invitations_sent_count = $user->invitations()->count();
        }
        $user->role_slug = $user->role?->slug ?? null;
        $currentUser = $this->app->auth()->user();
        $viewerId = $currentUser ? (int) $currentUser->id : null;
        $isStaff = $currentUser && $currentUser->role && $currentUser->role->is_staff;
        $stats = $this->getUserStats((int)$user->id);
        $isOwnProfile = $currentUser && (int)$currentUser->id === (int)$user->id;
        $reputationEnabled = $this->getSetting('reputation_enabled', '1') === '1';
        $idelistEnabled = $this->isIdelistEnabled();
        $iBlockedThem = false;
        $theyBlockedMe = false;
        if ($currentUser && !$isOwnProfile) {
            $theyBlockedMe = UserBlock::where('user_id', $user->id)->where('blocked_user_id', $currentUser->id)->exists();
            $iBlockedThem = UserBlock::where('user_id', $currentUser->id)->where('blocked_user_id', $user->id)->exists();
        }
        $netRep = (int)($stats->reputation_positive ?? 0) - (int)($stats->reputation_negative ?? 0);
        $rewardLevel = RewardLevel::forUser((int)($stats->post_count ?? 0), $netRep, (int)($stats->like_count ?? 0));
        $rewardLevelData = $rewardLevel ? (object)['id' => $rewardLevel->id, 'name' => $rewardLevel->name, 'badge_label' => $rewardLevel->badge_label, 'badge_icon' => $rewardLevel->badge_icon, 'badge_css' => $rewardLevel->badge_css] : null;

        $showFullProfile = !$theyBlockedMe && !$iBlockedThem;
        $profileCommentsEnabled = $this->getSetting('profile_comments_enabled', '1') === '1';
        $allowProfileComments = '1';
        if ($profileCommentsEnabled) {
            $pref = UserPreference::where('user_id', $user->id)->where('preference_key', 'allow_profile_comments')->first();
            $allowProfileComments = $pref ? $pref->value : '1';
        }
        $profileComments = [];
        if ($showFullProfile && $profileCommentsEnabled && $allowProfileComments === '1') {
            $profileComments = ProfileComment::where('user_id', $user->id)
                ->with('author:id,username,avatar_path')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(function ($c) {
                    $a = $c->author;
                    $avatarUrl = ($a && $a->avatar_path) ? asset_url($a->avatar_path) : ('https://ui-avatars.com/api/?name=' . rawurlencode($a->username ?? ''));
                    return (object)[
                        'id' => $c->id,
                        'body_html' => $c->body_html ?? '',
                        'created_at' => $c->created_at,
                        'author_id' => $c->author_id,
                        'author_username' => $a->username ?? null,
                        'author_avatar_url' => $avatarUrl,
                    ];
                })
                ->all();
        }

        $activityFilter = $_GET['filter'] ?? 'all';
        if (!in_array($activityFilter, ['all', 'forum', 'articles', 'ideas'], true)) {
            $activityFilter = 'all';
        }
        $activityStream = $showFullProfile
            ? $this->getActivityStream((int) $user->id, $viewerId, (bool) $isStaff, $activityFilter, 15, 0)
            : [];
        $activityHasMore = $showFullProfile
            && $this->activityStreamHasMore((int) $user->id, $viewerId, (bool) $isStaff, $activityFilter, 15, 0);

        $profileViewStats = $this->getProfileViewStats((int) $user->id);
        if ($showFullProfile && $viewerId && !$isOwnProfile) {
            $this->recordProfileView((int) $user->id, $viewerId);
            $profileViewStats = $this->getProfileViewStats((int) $user->id);
        }

        $profileData = [
            'profileUser' => $user,
            'userStats' => $stats,
            'activityStream' => $activityStream,
            'activityFilter' => $activityFilter,
            'activityHasMore' => $activityHasMore,
            'rewardLevel' => $rewardLevelData,
            'pageTitle' => $user->username,
            'isOwnProfile' => $isOwnProfile,
            'reputationEnabled' => $reputationEnabled,
            'idelistEnabled' => $idelistEnabled,
            'iBlockedThem' => $iBlockedThem,
            'theyBlockedMe' => $theyBlockedMe,
            'showFullProfile' => $showFullProfile,
            'profileCommentsEnabled' => $profileCommentsEnabled,
            'allowProfileComments' => $allowProfileComments === '1',
            'profileComments' => $profileComments,
            'profileViewStats' => $profileViewStats,
        ];
        $profileData = $this->app->hooks()->applyFilters('user.profile_data', $profileData, $user);
        return $this->layout('profile', $profileData, false);
    }

    /** AJAX: profil ziyaretçi listesi. */
    public function viewersAjax(string $username): string
    {
        $user = resolve_member($username);
        if (!$user) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            return json_encode(['error' => 'not_found'], JSON_UNESCAPED_UNICODE);
        }
        $viewers = $this->getProfileViewersList((int) $user->id, 50);
        $stats = $this->getProfileViewStats((int) $user->id);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode([
            'unique_viewers' => $stats->unique_viewers,
            'total_views' => $stats->total_views,
            'viewers' => $viewers,
        ], JSON_UNESCAPED_UNICODE);
    }

    protected function profileViewsTableExists(): bool
    {
        try {
            return DB::connection()->getSchemaBuilder()->hasTable('profile_views');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function recordProfileView(int $profileUserId, int $viewerUserId): void
    {
        if (!$this->profileViewsTableExists() || $profileUserId === $viewerUserId) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $row = DB::table('profile_views')
            ->where('profile_user_id', $profileUserId)
            ->where('viewer_user_id', $viewerUserId)
            ->first();
        if ($row) {
            $last = strtotime((string) ($row->last_viewed_at ?? ''));
            if ($last && (time() - $last) < 1800) {
                return;
            }
            DB::table('profile_views')
                ->where('profile_user_id', $profileUserId)
                ->where('viewer_user_id', $viewerUserId)
                ->update([
                    'view_count' => (int) $row->view_count + 1,
                    'last_viewed_at' => $now,
                ]);
            return;
        }
        DB::table('profile_views')->insert([
            'profile_user_id' => $profileUserId,
            'viewer_user_id' => $viewerUserId,
            'view_count' => 1,
            'first_viewed_at' => $now,
            'last_viewed_at' => $now,
        ]);
    }

    protected function getProfileViewStats(int $profileUserId): object
    {
        if (!$this->profileViewsTableExists()) {
            return (object) ['unique_viewers' => 0, 'total_views' => 0];
        }
        $agg = DB::table('profile_views')
            ->where('profile_user_id', $profileUserId)
            ->selectRaw('COUNT(*) as unique_viewers, COALESCE(SUM(view_count), 0) as total_views')
            ->first();

        return (object) [
            'unique_viewers' => (int) ($agg->unique_viewers ?? 0),
            'total_views' => (int) ($agg->total_views ?? 0),
        ];
    }

    /** @return array<int, object> */
    protected function getProfileViewersList(int $profileUserId, int $limit = 50): array
    {
        if (!$this->profileViewsTableExists()) {
            return [];
        }
        $rows = DB::table('profile_views')
            ->join('users', 'users.id', '=', 'profile_views.viewer_user_id')
            ->where('profile_views.profile_user_id', $profileUserId)
            ->orderByDesc('profile_views.last_viewed_at')
            ->limit($limit)
            ->get([
                'users.id',
                'users.username',
                'users.avatar_path',
                'profile_views.view_count',
                'profile_views.last_viewed_at',
            ]);

        return $rows->map(function ($r) {
            $avatarUrl = $r->avatar_path
                ? asset_url($r->avatar_path)
                : ('https://ui-avatars.com/api/?name=' . rawurlencode($r->username ?? ''));
            return (object) [
                'id' => (int) $r->id,
                'username' => $r->username,
                'avatar_url' => $avatarUrl,
                'view_count' => (int) $r->view_count,
                'last_viewed_at' => $r->last_viewed_at
                    ? date('d.m.Y H:i', strtotime((string) $r->last_viewed_at))
                    : '',
                'profile_url' => core_url('member/' . rawurlencode((string) $r->username)),
            ];
        })->all();
    }

    public function block(string $username): string
    {
        $current = $this->app->auth()->user();
        if (!$current) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('member_block', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('member/' . rawurlencode($username)));
            return '';
        }
        $target = resolve_member($username);
        if (!$target || (int)$target->id === (int)$current->id) {
            $this->redirect($target ? core_url('member/' . member_url_path($target)) : core_url('members'));
            return '';
        }
        try {
            UserBlock::firstOrCreate(['user_id' => $current->id, 'blocked_user_id' => $target->id]);
        } catch (\Throwable $e) {
        }
        $this->app->session()->getFlashBag()->add('profile_ok', lang('member.blocked'));
        $this->redirect(core_url('member/' . member_url_path($target)));
        return '';
    }

    public function unblock(string $username): string
    {
        $current = $this->app->auth()->user();
        if (!$current) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('member_unblock', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('member/' . rawurlencode($username)));
            return '';
        }
        $target = resolve_member($username);
        if ($target) {
            UserBlock::where('user_id', $current->id)->where('blocked_user_id', $target->id)->delete();
        }
        $this->app->session()->getFlashBag()->add('profile_ok', lang('member.unblocked'));
        $this->redirect($target ? core_url('member/' . member_url_path($target)) : core_url('members'));
        return '';
    }

    protected function isIdelistEnabled(): bool
    {
        try {
            return \App\Modules\Idelist\Models\IdelistSetting::getValue('module_enabled', '1') === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Forum konuları (topic + question). */
    protected function forumTopicsQuery(?int $viewerId, bool $isStaff, int $userId)
    {
        return Topic::visibleToUserWithPrivacy($viewerId, $isStaff)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->whereIn('type', $this->getTopicListTypes())->orWhereNull('type'));
    }

    /** Makaleler (type = article). */
    protected function articleTopicsQuery(?int $viewerId, bool $isStaff, int $userId)
    {
        return Topic::visibleToUserWithPrivacy($viewerId, $isStaff)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereRaw("COALESCE(type, 'topic') = 'article'");
    }

    /** Forum mesajları (makale dışı konulardaki yanıtlar). */
    protected function forumPostsQuery(?int $viewerId, bool $isStaff, int $userId)
    {
        return Post::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereHas('topic', fn ($q) => $q->visibleToUserWithPrivacy($viewerId, $isStaff)
                ->whereNull('deleted_at')
                ->where(fn ($q2) => $q2->whereIn('type', $this->getTopicListTypes())->orWhereNull('type')));
    }

    protected function getUserStats(int $userId): object
    {
        $viewer = $this->app->auth()->user();
        $viewerId = $viewer ? (int) $viewer->id : null;
        $isStaff = $viewer && $viewer->role && $viewer->role->is_staff;
        $topicCount = (int) $this->forumTopicsQuery($viewerId, $isStaff, $userId)->count();
        $forumReplyCount = (int) $this->forumPostsQuery($viewerId, $isStaff, $userId)
            ->where('is_first_post', 0)
            ->count();
        $articleCount = (int) $this->articleTopicsQuery($viewerId, $isStaff, $userId)->count();
        $ideaCount = 0;
        if ($this->isIdelistEnabled()) {
            try {
                $ideaCount = (int) \App\Modules\Idelist\Models\Idea::where('user_id', $userId)->count();
            } catch (\Throwable $e) {
            }
        }
        $postCount = $forumReplyCount;
        $likeCount = (int) Post::where('user_id', $userId)->whereNull('deleted_at')->sum('like_count');
        $subCount = 0;
        try {
            $subCount = (int) TopicSubscription::where('user_id', $userId)->count();
        } catch (\Throwable $e) {
        }
        $u = User::where('id', $userId)->first(['reputation_positive', 'reputation_negative', 'reward_points', 'created_at']);
        $contentCount = $topicCount + $forumReplyCount + $articleCount + $ideaCount;
        $repGivenPositive = 0;
        $repGivenNegative = 0;
        $likedPostsCount = 0;
        try {
            $repGivenPositive = (int) UserReputation::where('from_user_id', $userId)->where('value', 1)->count();
            $repGivenNegative = (int) UserReputation::where('from_user_id', $userId)->where('value', -1)->count();
            $likedPostsCount = (int) Post::where('user_id', $userId)->whereNull('deleted_at')->where('like_count', '>', 0)->count();
        } catch (\Throwable $e) {
        }
        $netRep = (int) ($u->reputation_positive ?? 0) - (int) ($u->reputation_negative ?? 0);
        return (object)[
            'post_count' => $postCount,
            'topic_count' => $topicCount,
            'forum_reply_count' => $forumReplyCount,
            'article_count' => $articleCount,
            'idea_count' => $ideaCount,
            'content_count' => $contentCount,
            'like_count' => $likeCount,
            'liked_posts_count' => $likedPostsCount,
            'subscription_count' => $subCount,
            'reputation_positive' => (int)($u->reputation_positive ?? 0),
            'reputation_negative' => (int)($u->reputation_negative ?? 0),
            'reputation_net' => $netRep,
            'rep_given_positive' => $repGivenPositive,
            'rep_given_negative' => $repGivenNegative,
            'reward_points' => (int) ($u->reward_points ?? 0),
            'joined_at' => $u->created_at?->format('d.m.Y'),
        ];
    }

    /** Aylık aktivite serileri — istatistik grafik kartları. */
    protected function getUserStatsCharts(int $userId, int $months = 12): object
    {
        $viewer = $this->app->auth()->user();
        $viewerId = $viewer ? (int) $viewer->id : null;
        $isStaff = $viewer && $viewer->role && $viewer->role->is_staff;

        $monthKeys = [];
        $monthLabels = [];
        $monthShort = [
            '01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis', '05' => 'May', '06' => 'Haz',
            '07' => 'Tem', '08' => 'Ağu', '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara',
        ];
        $start = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        for ($i = 0; $i < $months; $i++) {
            $d = $start->modify("+{$i} months");
            $ym = $d->format('Y-m');
            $monthKeys[] = $ym;
            $monthLabels[] = ($monthShort[$d->format('m')] ?? $d->format('M')) . ' ' . $d->format('y');
        }
        $since = $start->format('Y-m-d 00:00:00');

        $mapCounts = function ($rows) use ($monthKeys): array {
            $indexed = [];
            foreach ($rows as $r) {
                $indexed[(string) $r->ym] = (int) $r->cnt;
            }

            return array_map(static fn (string $k): int => $indexed[$k] ?? 0, $monthKeys);
        };

        $topics = $mapCounts(
            $this->forumTopicsQuery($viewerId, $isStaff, $userId)
                ->where('created_at', '>=', $since)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->get()
        );

        $posts = $mapCounts(
            $this->forumPostsQuery($viewerId, $isStaff, $userId)
                ->where('is_first_post', 0)
                ->where('created_at', '>=', $since)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->get()
        );

        $articles = $mapCounts(
            $this->articleTopicsQuery($viewerId, $isStaff, $userId)
                ->where('created_at', '>=', $since)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->get()
        );

        $ideas = array_fill(0, $months, 0);
        if ($this->isIdelistEnabled()) {
            try {
                $ideas = $mapCounts(
                    \App\Modules\Idelist\Models\Idea::where('user_id', $userId)
                        ->where('created_at', '>=', $since)
                        ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                        ->groupBy('ym')
                        ->get()
                );
            } catch (\Throwable $e) {
            }
        }

        $likes = $mapCounts(
            Post::where('user_id', $userId)
                ->whereNull('deleted_at')
                ->where('created_at', '>=', $since)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COALESCE(SUM(like_count), 0) as cnt")
                ->groupBy('ym')
                ->get()
        );

        $repReceived = $mapCounts(
            UserReputation::where('to_user_id', $userId)
                ->where('created_at', '>=', $since)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->get()
        );

        $content = [];
        for ($i = 0; $i < $months; $i++) {
            $content[$i] = $topics[$i] + $posts[$i] + $articles[$i] + $ideas[$i];
        }

        $delta = static function (array $series): array {
            $len = count($series);
            $thisMonth = $len > 0 ? (int) $series[$len - 1] : 0;
            $lastMonth = $len > 1 ? (int) $series[$len - 2] : 0;

            return ['this_month' => $thisMonth, 'prev_month' => $lastMonth, 'delta' => $thisMonth - $lastMonth];
        };

        return (object) [
            'labels' => $monthLabels,
            'months' => $monthKeys,
            'content' => $content,
            'topics' => $topics,
            'posts' => $posts,
            'articles' => $articles,
            'ideas' => $ideas,
            'likes' => $likes,
            'rep_received' => $repReceived,
            'deltas' => (object) [
                'content' => $delta($content),
                'topics' => $delta($topics),
                'posts' => $delta($posts),
                'articles' => $delta($articles),
                'ideas' => $delta($ideas),
                'likes' => $delta($likes),
                'rep_received' => $delta($repReceived),
            ],
        ];
    }

    protected function getContributionsPreview(int $userId, ?int $viewerId, bool $isStaff): object
    {
        $forumTopics = $this->forumTopicsQuery($viewerId, $isStaff, $userId)
            ->with('forum')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => (object) [
                'id' => $t->id,
                'title' => $t->title,
                'created_at' => $t->created_at?->format('d.m.Y H:i'),
                'context' => $t->forum?->name ?? '',
                'url' => core_url('topic/' . topic_url_path($t)),
            ])
            ->all();

        $forumPosts = $this->forumPostsQuery($viewerId, $isStaff, $userId)
            ->with(['topic.forum'])
            ->where('is_first_post', 0)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($p) => (object) [
                'id' => $p->id,
                'title' => $p->topic?->title ?? '',
                'created_at' => $p->created_at?->format('d.m.Y H:i'),
                'context' => $p->topic?->forum?->name ?? '',
                'url' => core_url('topic/' . topic_url_path_by_id((int) $p->topic_id)) . '#post-' . $p->id,
                'excerpt' => mb_substr(strip_tags((string) ($p->body_html ?? $p->body ?? '')), 0, 120),
            ])
            ->all();

        $articles = $this->articleTopicsQuery($viewerId, $isStaff, $userId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'created_at'])
            ->map(fn ($t) => (object) [
                'id' => $t->id,
                'title' => $t->title,
                'created_at' => $t->created_at?->format('d.m.Y H:i'),
                'context' => lang('article.articles'),
                'url' => core_url('articles/' . article_url_path_by_id((int) $t->id)),
            ])
            ->all();

        $ideas = [];
        if ($this->isIdelistEnabled()) {
            try {
                $ideas = \App\Modules\Idelist\Models\Idea::where('user_id', $userId)
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(['id', 'title', 'slug', 'created_at'])
                    ->map(fn ($i) => (object) [
                        'id' => $i->id,
                        'title' => $i->title,
                        'created_at' => $i->created_at?->format('d.m.Y H:i'),
                        'context' => 'Idelist',
                        'url' => core_url('idelist/' . ($i->slug ?? $i->id)),
                    ])
                    ->all();
            } catch (\Throwable $e) {
            }
        }

        return (object) [
            'forum_topics' => $forumTopics,
            'forum_posts' => $forumPosts,
            'articles' => $articles,
            'ideas' => $ideas,
        ];
    }

    /** IPS tarzı kronolojik etkinlik akışı (filtrelenebilir, sayfalanabilir). */
    protected function getActivityStream(int $userId, ?int $viewerId, bool $isStaff, string $filter = 'all', int $limit = 15, int $offset = 0): array
    {
        $items = $this->buildActivityStreamItems($userId, $viewerId, $isStaff, $filter, $offset + $limit + 1);

        return array_slice($items, $offset, $limit);
    }

    protected function activityStreamHasMore(int $userId, ?int $viewerId, bool $isStaff, string $filter, int $limit, int $offset): bool
    {
        $items = $this->buildActivityStreamItems($userId, $viewerId, $isStaff, $filter, $offset + $limit + 1);

        return count($items) > $offset + $limit;
    }

    protected function buildActivityStreamItems(int $userId, ?int $viewerId, bool $isStaff, string $filter, int $fetchCount): array
    {
        if (!in_array($filter, ['all', 'forum', 'articles', 'ideas'], true)) {
            $filter = 'all';
        }
        $fetchCount = max(1, min(120, $fetchCount));
        $items = [];
        $perType = $filter === 'all' ? max(8, (int) ceil($fetchCount / 2)) : $fetchCount;

        if ($filter === 'all' || $filter === 'forum') {
            foreach ($this->forumTopicsQuery($viewerId, $isStaff, $userId)
                ->with('forum')
                ->orderByDesc('created_at')
                ->limit($perType)
                ->get() as $t) {
                $items[] = (object) [
                    'kind' => 'topic',
                    'title' => $t->title,
                    'url' => core_url('topic/' . topic_url_path($t)),
                    'context' => $t->forum?->name ?? '',
                    'excerpt' => null,
                    'created_at' => $t->created_at?->format('d.m.Y H:i'),
                    'sort_at' => $t->created_at?->format('Y-m-d H:i:s') ?? '',
                    'type_label' => lang('profile.topics'),
                ];
            }
            foreach ($this->forumPostsQuery($viewerId, $isStaff, $userId)
                ->with(['topic.forum'])
                ->where('is_first_post', 0)
                ->orderByDesc('created_at')
                ->limit($perType)
                ->get() as $p) {
                $items[] = (object) [
                    'kind' => 'post',
                    'title' => $p->topic?->title ?? '',
                    'url' => core_url('topic/' . topic_url_path_by_id((int) $p->topic_id)) . '#post-' . $p->id,
                    'context' => $p->topic?->forum?->name ?? '',
                    'excerpt' => mb_substr(strip_tags((string) ($p->body_html ?? $p->body ?? '')), 0, 120),
                    'created_at' => $p->created_at?->format('d.m.Y H:i'),
                    'sort_at' => $p->created_at?->format('Y-m-d H:i:s') ?? '',
                    'type_label' => lang('profile.posts'),
                ];
            }
        }

        if ($filter === 'all' || $filter === 'articles') {
            foreach ($this->articleTopicsQuery($viewerId, $isStaff, $userId)
                ->orderByDesc('created_at')
                ->limit($perType)
                ->get(['id', 'title', 'created_at']) as $t) {
                $items[] = (object) [
                    'kind' => 'article',
                    'title' => $t->title,
                    'url' => core_url('articles/' . article_url_path_by_id((int) $t->id)),
                    'context' => lang('article.articles'),
                    'excerpt' => null,
                    'created_at' => $t->created_at?->format('d.m.Y H:i'),
                    'sort_at' => $t->created_at?->format('Y-m-d H:i:s') ?? '',
                    'type_label' => lang('article.articles'),
                ];
            }
        }

        if (($filter === 'all' || $filter === 'ideas') && $this->isIdelistEnabled()) {
            try {
                foreach (\App\Modules\Idelist\Models\Idea::where('user_id', $userId)
                    ->orderByDesc('created_at')
                    ->limit($perType)
                    ->get(['id', 'title', 'slug', 'created_at']) as $i) {
                    $items[] = (object) [
                        'kind' => 'idea',
                        'title' => $i->title,
                        'url' => core_url('idelist/' . ($i->slug ?? $i->id)),
                        'context' => lang('member.contributions_ideas'),
                        'excerpt' => null,
                        'created_at' => $i->created_at?->format('d.m.Y H:i'),
                        'sort_at' => $i->created_at?->format('Y-m-d H:i:s') ?? '',
                        'type_label' => lang('member.contributions_ideas'),
                    ];
                }
            } catch (\Throwable $e) {
            }
        }

        usort($items, static fn ($a, $b) => strcmp($b->sort_at, $a->sort_at));

        return $items;
    }

    /** AJAX: profil etkinlik akışı parçası. */
    public function activityAjax(string $username): string
    {
        $user = resolve_member($username);
        if (!$user) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            return json_encode(['error' => 'not_found'], JSON_UNESCAPED_UNICODE);
        }
        $currentUser = $this->app->auth()->user();
        $viewerId = $currentUser ? (int) $currentUser->id : null;
        $isStaff = $currentUser && $currentUser->role && $currentUser->role->is_staff;
        if ($currentUser && (int) $currentUser->id !== (int) $user->id) {
            $theyBlockedMe = UserBlock::where('user_id', $user->id)->where('blocked_user_id', $currentUser->id)->exists();
            $iBlockedThem = UserBlock::where('user_id', $currentUser->id)->where('blocked_user_id', $user->id)->exists();
            if ($theyBlockedMe || $iBlockedThem) {
                header('Content-Type: application/json; charset=utf-8');
                return json_encode(['html' => '', 'has_more' => false], JSON_UNESCAPED_UNICODE);
            }
        }
        $filter = $_GET['filter'] ?? 'all';
        if (!in_array($filter, ['all', 'forum', 'articles', 'ideas'], true)) {
            $filter = 'all';
        }
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $limit = min(30, max(5, (int) ($_GET['limit'] ?? 15)));
        $items = $this->getActivityStream((int) $user->id, $viewerId, (bool) $isStaff, $filter, $limit, $offset);
        $hasMore = $this->activityStreamHasMore((int) $user->id, $viewerId, (bool) $isStaff, $filter, $limit, $offset);
        $html = $this->app->twig('frontend')->render('partials/profile_activity_stream_items.html.twig', [
            'activityStream' => $items,
            'profileUser' => $user,
        ]);
        header('Content-Type: application/json; charset=utf-8');
        return json_encode(['html' => $html, 'has_more' => $hasMore, 'count' => count($items)], JSON_UNESCAPED_UNICODE);
    }

    public function stats(string $username): string
    {
        return $this->memberStatsPage($username);
    }

    public function topics(string $username): string
    {
        return $this->memberListPage($username, 'topics');
    }

    public function forum(string $username): string
    {
        return $this->memberListPage($username, 'forum');
    }

    public function posts(string $username): string
    {
        return $this->memberListPage($username, 'posts');
    }

    public function articles(string $username): string
    {
        return $this->memberListPage($username, 'articles');
    }

    public function ideas(string $username): string
    {
        return $this->memberListPage($username, 'ideas');
    }

    public function about(string $username): string
    {
        $user = resolve_member($username);
        if (!$user) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $this->redirect(core_url('member/' . member_url_path($user)));
        return '';
    }

    public function likes(string $username): string
    {
        return $this->memberListPage($username, 'likes');
    }

    public function reputation(string $username): string
    {
        return $this->memberListPage($username, 'reputation');
    }

    /** Abone olunan konular — sadece kendi profili. */
    public function subscriptions(string $username): string
    {
        $current = $this->app->auth()->user();
        if (!$current) {
            $this->redirect(core_url('login'));
            return '';
        }
        $user = resolve_member($username);
        if (!$user || (int)$user->id !== (int)$current->id) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $stats = $this->getUserStats((int)$user->id);
        $reputationEnabled = $this->getSetting('reputation_enabled', '1') === '1';
        $viewer = $this->app->auth()->user();
        $viewerId = $viewer ? (int) $viewer->id : null;
        $isStaff = $viewer && $viewer->role && $viewer->role->is_staff;
        $subscriptions = TopicSubscription::with(['topic' => fn ($q) => $q->whereNull('deleted_at'), 'topic.forum'])
            ->where('user_id', $user->id)
            ->whereHas('topic', fn ($q) => $q->visibleToUserWithPrivacy($viewerId, $isStaff)->whereNull('deleted_at'))
            ->get()
            ->map(function ($ts) {
                $t = $ts->topic;
                $f = $t ? $t->forum : null;
                return (object) [
                    'sub_id' => $ts->id,
                    'subscribed_at' => $ts->created_at,
                    'id' => $t ? $t->id : null,
                    'title' => $t ? $t->title : null,
                    'slug' => $t ? $t->slug : null,
                    'reply_count' => $t ? $t->reply_count : null,
                    'last_post_at' => $t ? $t->last_post_at : null,
                    'forum_name' => $f ? $f->name : null,
                    'forum_slug' => $f ? $f->slug : null,
                ];
            })
            ->filter(fn ($o) => $o->id !== null)
            ->sortByDesc('last_post_at')
            ->values()
            ->take(100)
            ->all();
        return $this->layout('member/subscriptions', [
            'profileUser' => $user,
            'userStats' => $stats,
            'subscriptions' => $subscriptions,
            'pageTitle' => lang('member.subscriptions_page_title'),
            'reputationEnabled' => $reputationEnabled,
            'idelistEnabled' => $this->isIdelistEnabled(),
        ], false);
    }

    protected function memberListPage(string $username, string $type): string
    {
        $user = resolve_member($username);
        if (!$user) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = User::with('role', 'usedInvitation.inviter')->find($user->id);
        if (!$user) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user->role_name = $user->role?->name;
        $user->inviter_user = $user->usedInvitation?->inviter;
        $viewer = $this->app->auth()->user();
        $viewerId = $viewer ? (int) $viewer->id : null;
        $isStaff = $viewer && $viewer->role && $viewer->role->is_staff;
        $userId = (int)$user->id;
        $stats = $this->getUserStats($userId);
        $reputationEnabled = $this->getSetting('reputation_enabled', '1') === '1';
        $idelistEnabled = $this->isIdelistEnabled();
        $isOwnProfile = $viewer && (int) $viewer->id === $userId;
        $netRep = (int) ($stats->reputation_net ?? 0);
        $rewardLevel = RewardLevel::forUser((int) ($stats->post_count ?? 0), $netRep, (int) ($stats->like_count ?? 0));
        $rewardLevelData = $rewardLevel ? (object) ['id' => $rewardLevel->id, 'name' => $rewardLevel->name, 'badge_label' => $rewardLevel->badge_label, 'badge_icon' => $rewardLevel->badge_icon, 'badge_css' => $rewardLevel->badge_css] : null;
        $layoutData = [
            'profileUser' => $user,
            'userStats' => $stats,
            'rewardLevel' => $rewardLevelData,
            'isOwnProfile' => $isOwnProfile,
            'reputationEnabled' => $reputationEnabled,
            'idelistEnabled' => $idelistEnabled,
        ];
        if ($type === 'topics') {
            $items = $this->forumTopicsQuery($viewerId, $isStaff, $userId)
                ->with('forum')
                ->orderByDesc('created_at')->limit(50)->get()
                ->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'forum_name' => $t->forum?->name, 'forum_slug' => $t->forum?->slug])->all();
            return $this->layout('member/topics', array_merge($layoutData, ['items' => $items, 'pageTitle' => lang('member.topics_page_title', ['name' => $user->username])]), false);
        }
        if ($type === 'forum') {
            $topics = $this->forumTopicsQuery($viewerId, $isStaff, $userId)
                ->with('forum')
                ->orderByDesc('created_at')->limit(30)->get()
                ->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'forum_name' => $t->forum?->name, 'forum_slug' => $t->forum?->slug])->all();
            $posts = $this->forumPostsQuery($viewerId, $isStaff, $userId)
                ->with(['topic.forum'])
                ->where('is_first_post', 0)
                ->orderByDesc('created_at')->limit(30)->get()
                ->map(fn ($p) => (object)['id' => $p->id, 'body' => $p->body, 'body_html' => $p->body_html, 'created_at' => $p->created_at?->format('Y-m-d H:i:s'), 'topic_id' => $p->topic_id, 'topic_title' => $p->topic?->title, 'topic_slug' => $p->topic?->slug, 'forum_slug' => $p->topic?->forum?->slug, 'forum_name' => $p->topic?->forum?->name])->all();
            return $this->layout('member/forum', array_merge($layoutData, ['topics' => $topics, 'posts' => $posts, 'pageTitle' => lang('member.forum_page_title', ['name' => $user->username])]), false);
        }
        if ($type === 'posts') {
            $items = $this->forumPostsQuery($viewerId, $isStaff, $userId)
                ->with(['topic.forum'])
                ->where('is_first_post', 0)
                ->orderByDesc('created_at')->limit(30)->get()
                ->map(fn ($p) => (object)['id' => $p->id, 'body' => $p->body, 'body_html' => $p->body_html, 'created_at' => $p->created_at?->format('Y-m-d H:i:s'), 'topic_id' => $p->topic_id, 'topic_title' => $p->topic?->title, 'topic_slug' => $p->topic?->slug, 'forum_slug' => $p->topic?->forum?->slug])->all();
            return $this->layout('member/posts', array_merge($layoutData, ['items' => $items, 'pageTitle' => lang('member.posts_page_title', ['name' => $user->username])]), false);
        }
        if ($type === 'articles') {
            $items = $this->articleTopicsQuery($viewerId, $isStaff, $userId)
                ->orderByDesc('created_at')->limit(50)->get(['id', 'title', 'slug', 'view_count', 'created_at'])
                ->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'url' => core_url('articles/' . article_url_path_by_id((int) $t->id))])->all();
            return $this->layout('member/articles', array_merge($layoutData, ['items' => $items, 'pageTitle' => lang('member.articles_page_title', ['name' => $user->username])]), false);
        }
        if ($type === 'ideas') {
            if (!$idelistEnabled) {
                http_response_code(404);
                return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
            }
            $items = [];
            try {
                $items = \App\Modules\Idelist\Models\Idea::where('user_id', $userId)
                    ->orderByDesc('created_at')->limit(50)->get(['id', 'title', 'slug', 'vote_count', 'status', 'created_at'])
                    ->map(fn ($i) => (object)['id' => $i->id, 'title' => $i->title, 'slug' => $i->slug, 'vote_count' => $i->vote_count, 'status' => $i->status, 'created_at' => $i->created_at?->format('Y-m-d H:i:s'), 'url' => core_url('idelist/' . ($i->slug ?? $i->id))])->all();
            } catch (\Throwable $e) {
            }
            return $this->layout('member/ideas', array_merge($layoutData, ['items' => $items, 'pageTitle' => lang('member.ideas_page_title', ['name' => $user->username])]), false);
        }
        if ($type === 'likes') {
            $items = Post::with('topic')
                ->where('user_id', $userId)
                ->where('like_count', '>', 0)
                ->whereHas('topic', fn ($q) => $q->visibleToUserWithPrivacy($viewerId, $isStaff)->whereNull('deleted_at'))
                ->orderByDesc('like_count')
                ->limit(30)
                ->get()
                ->map(fn ($p) => (object)['id' => $p->id, 'like_count' => $p->like_count, 'body_html' => $p->body_html, 'created_at' => $p->created_at?->format('Y-m-d H:i:s'), 'topic_id' => $p->topic_id, 'topic_title' => $p->topic?->title, 'topic_slug' => $p->topic?->slug])->all();
            return $this->layout('member/likes', array_merge($layoutData, ['items' => $items, 'pageTitle' => $user->username . ' - ' . lang('member.liked_posts_title')]), false);
        }
        if ($type === 'reputation') {
            $positive = [];
            $negative = [];
            try {
                $rows = UserReputation::with([
                    'fromUser',
                    'post' => static fn ($q) => $q->withTrashed()->with(['topic' => static fn ($tq) => $tq->withTrashed()]),
                ])->where('to_user_id', $userId)->orderByDesc('created_at')->get();
                foreach ($rows as $row) {
                    $ctx = $this->reputationEntryPostContext($row->post);
                    $obj = (object) [
                        'id' => $row->id,
                        'value' => $row->value,
                        'comment' => $row->comment,
                        'created_at' => $row->created_at,
                        'from_username' => $row->fromUser ? $row->fromUser->username : null,
                        'post_id' => $row->post_id,
                        'post_url' => $ctx['post_url'],
                        'topic_title' => $ctx['topic_title'],
                    ];
                    if ((int)$row->value === 1) {
                        $positive[] = $obj;
                    } else {
                        $negative[] = $obj;
                    }
                }
            } catch (\Throwable $e) {
            }
            $tab = (isset($_GET['tab']) && $_GET['tab'] === 'negative') ? 'negative' : 'positive';
            return $this->layout('member/reputation', array_merge($layoutData, [
                'positive' => $positive,
                'negative' => $negative,
                'tab' => $tab,
                'pageTitle' => $user->username . ' - Reputation',
            ]), false);
        }
        return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
    }

    protected function memberStatsPage(string $username): string
    {
        $user = resolve_member($username);
        if (!$user) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = User::with('role')->find($user->id);
        if (!$user) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user->role_name = $user->role?->name;
        $stats = $this->getUserStats((int) $user->id);
        $currentUser = $this->app->auth()->user();
        $isOwnProfile = $currentUser && (int) $currentUser->id === (int) $user->id;
        $reputationEnabled = $this->getSetting('reputation_enabled', '1') === '1';
        $netRep = (int) ($stats->reputation_net ?? 0);
        $rewardLevel = RewardLevel::forUser((int) ($stats->post_count ?? 0), $netRep, (int) ($stats->like_count ?? 0));
        $rewardLevelData = $rewardLevel ? (object) ['id' => $rewardLevel->id, 'name' => $rewardLevel->name, 'badge_label' => $rewardLevel->badge_label, 'badge_icon' => $rewardLevel->badge_icon, 'badge_css' => $rewardLevel->badge_css] : null;
        $coverUrl = $user->cover_photo_path ? asset_url($user->cover_photo_path) : null;
        $avatarUrl = $user->avatar_path ? asset_url($user->avatar_path) : ('https://ui-avatars.com/api/?name=' . rawurlencode($user->username ?? ''));

        return $this->layout('member/stats', [
            'profileUser' => $user,
            'userStats' => $stats,
            'statsCharts' => $this->getUserStatsCharts((int) $user->id),
            'rewardLevel' => $rewardLevelData,
            'isOwnProfile' => $isOwnProfile,
            'reputationEnabled' => $reputationEnabled,
            'idelistEnabled' => $this->isIdelistEnabled(),
            'coverUrl' => $coverUrl,
            'avatarUrl' => $avatarUrl,
            'pageTitle' => lang('member.stats_page_title', ['name' => $user->username]),
        ], false);
    }

    public function giveRep(string $username): string
    {
        $current = $this->app->auth()->user();
        if (!$current) {
            $this->redirect(core_url('login'));
            return '';
        }
        if ($this->getSetting('reputation_enabled', '1') !== '1') {
            $this->app->session()->getFlashBag()->add('rep_error', lang('member.rep_disabled'));
            $this->redirect(core_url('member/' . rawurlencode($username)));
            return '';
        }
        if (!core_csrf_valid('give_rep', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('member/' . rawurlencode($username)));
            return '';
        }
        $target = resolve_member($username);
        if (!$target || (int)$target->id === (int)$current->id) {
            $this->redirect($target ? core_url('member/' . member_url_path($target)) : core_url('members'));
            return '';
        }
        $value = (int)($_POST['value'] ?? 0);
        if ($value !== 1 && $value !== -1) {
            $value = 1;
        }
        $comment = trim((string)($_POST['comment'] ?? ''));
        $postId = !empty($_POST['post_id']) ? (int)$_POST['post_id'] : null;
        $postRow = null;
        if ($postId) {
            $postRow = Post::with('topic')->where('id', $postId)->first();
            if (
                !$postRow
                || !$postRow->topic
                || $postRow->deleted_at
                || $postRow->topic->deleted_at
                || (int)$postRow->user_id !== (int)$target->id
            ) {
                $postId = null;
                $postRow = null;
            }
        }
        $repDetails = ['value' => $value, 'target_username' => $username];
        if ($postId && $postRow) {
            $repDetails['post_id'] = $postId;
            $repDetails['topic_id'] = $postRow->topic_id;
            $repDetails['topic_title'] = mb_strlen($postRow->topic->title ?? '') > 25 ? mb_substr($postRow->topic->title, 0, 25) . '…' : ($postRow->topic->title ?? '');
        }
        $repSaved = false;
        try {
            UserReputation::create(['from_user_id' => $current->id, 'to_user_id' => $target->id, 'post_id' => $postId, 'value' => $value, 'comment' => $comment ?: null, 'created_at' => \now()]);
            if ($value === 1) {
                User::where('id', $target->id)->increment('reputation_positive');
            } else {
                User::where('id', $target->id)->increment('reputation_negative');
            }
            (new \App\Services\UserActivityService())->log((int)$current->id, \App\Services\UserActivityService::ACTION_REP_GIVEN, (int)$target->id, $repDetails);
            try {
                $this->app->event()->dispatch(new \App\Events\ReputationGiven($current, $target, $value, $postId), \App\Events\ReputationGiven::NAME);
            } catch (\Throwable $e) {
            }
            $repSaved = true;
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('rep_error', lang('member.rep_give_failed'));
        }
        if ($repSaved && $postId && $postRow && $postRow->topic) {
            $this->redirect(core_url('topic/' . topic_url_path_by_id((int)$postRow->topic_id)) . '#post-' . (int)$postId);
            return '';
        }
        $this->redirect(core_url('member/' . member_url_path($target)));
        return '';
    }

    /** Profil yorumu ekle (POST). */
    public function addProfileComment(string $username): string
    {
        $current = $this->app->auth()->user();
        if (!$current) {
            $this->app->session()->getFlashBag()->add('profile_comment_error', lang('member.profile_comment_login_required'));
            $this->redirect(core_url('login'));
            return '';
        }
        if ($this->getSetting('profile_comments_enabled', '1') !== '1') {
            $this->app->session()->getFlashBag()->add('profile_comment_error', lang('member.profile_comments_disabled'));
            $this->redirect(core_url('member/' . rawurlencode($username)));
            return '';
        }
        if (!core_csrf_valid('profile_comment', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('member/' . rawurlencode($username)));
            return '';
        }
        $target = resolve_member($username);
        if (!$target || (int)$target->id === (int)$current->id) {
            $this->redirect($target ? core_url('member/' . member_url_path($target)) : core_url('members'));
            return '';
        }
        $theyBlockedMe = UserBlock::where('user_id', $target->id)->where('blocked_user_id', $current->id)->exists();
        $iBlockedThem = UserBlock::where('user_id', $current->id)->where('blocked_user_id', $target->id)->exists();
        if ($theyBlockedMe || $iBlockedThem) {
            $this->redirect(core_url('member/' . member_url_path($target)));
            return '';
        }
        $pref = UserPreference::where('user_id', $target->id)->where('preference_key', 'allow_profile_comments')->first();
        if ($pref && $pref->value !== '1') {
            $this->app->session()->getFlashBag()->add('profile_comment_error', lang('member.profile_comments_disabled'));
            $this->redirect(core_url('member/' . member_url_path($target)));
            return '';
        }
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '') {
            $this->app->session()->getFlashBag()->add('profile_comment_error', lang('member.profile_comment_placeholder'));
            $this->redirect(core_url('member/' . member_url_path($target)));
            return '';
        }
        $bodyHtml = core_body_to_html($body);
        ProfileComment::create([
            'user_id' => $target->id,
            'author_id' => $current->id,
            'body' => $body,
            'body_html' => $bodyHtml,
            'created_at' => \now(),
        ]);
        $this->app->session()->getFlashBag()->add('profile_ok', lang('member.profile_comment_added'));
        $this->redirect(core_url('member/' . member_url_path($target)));
        return '';
    }

    /** Profil yorumu sil (POST). Yorumu yazan veya profil sahibi silebilir. */
    public function deleteProfileComment(string $username, int $id): string
    {
        $current = $this->app->auth()->user();
        if (!$current) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('profile_comment_delete', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('member/' . rawurlencode($username)));
            return '';
        }
        $target = resolve_member($username);
        if (!$target) {
            $this->redirect(core_url('members'));
            return '';
        }
        $comment = ProfileComment::where('id', $id)->where('user_id', $target->id)->first();
        if (!$comment) {
            $this->redirect(core_url('member/' . member_url_path($target)));
            return '';
        }
        $isAuthor = (int)$comment->author_id === (int)$current->id;
        $isProfileOwner = (int)$target->id === (int)$current->id;
        if (!$isAuthor && !$isProfileOwner) {
            $this->redirect(core_url('member/' . member_url_path($target)));
            return '';
        }
        $comment->delete();
        $this->app->session()->getFlashBag()->add('profile_ok', lang('member.profile_comment_deleted'));
        $this->redirect(core_url('member/' . member_url_path($target)));
        return '';
    }

    /**
     * @return array{post_url: ?string, topic_title: ?string}
     */
    private function reputationEntryPostContext(?Post $post): array
    {
        if (!$post) {
            return ['post_url' => null, 'topic_title' => null];
        }
        $post->loadMissing(['topic']);
        $topic = $post->topic;
        if (!$topic || $topic->deleted_at || $post->deleted_at) {
            return [
                'post_url' => null,
                'topic_title' => ($topic && !$topic->deleted_at) ? (string)($topic->title ?? '') : null,
            ];
        }
        $url = core_url('topic/' . topic_url_path_by_id((int)$topic->id)) . '#post-' . (int)$post->id;

        return [
            'post_url' => $url,
            'topic_title' => (string)($topic->title ?? ''),
        ];
    }
}
