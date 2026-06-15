<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;

class UserActivityService
{
    public const FILTER_ALL = 'all';

    public const ACTION_TOPIC_CREATED = 'topic_created';
    public const ACTION_POST_CREATED = 'post_created';
    public const ACTION_LIKE_GIVEN = 'like_given';
    public const ACTION_REP_GIVEN = 'rep_given';
    public const ACTION_USER_REGISTERED = 'user_registered';

    private const FETCH_LIMIT = 80;
    private const BODY_SNIPPET_LEN = 70;

    private const FILTERABLE_ACTIONS = [
        self::ACTION_TOPIC_CREATED,
        self::ACTION_POST_CREATED,
        self::ACTION_LIKE_GIVEN,
        self::ACTION_REP_GIVEN,
        self::ACTION_USER_REGISTERED,
    ];

    /**
     * Kullanıcı aktivitesini loglar (Capsule/Eloquent).
     */
    public function log(int $userId, string $actionType, ?int $itemId = null, ?array $details = null): void
    {
        try {
            $detailsJson = $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
            DB::table('user_activities')->insert([
                'user_id' => $userId,
                'action_type' => $actionType,
                'item_id' => $itemId,
                'details' => $detailsJson,
                'created_at' => \now(),
            ]);
        } catch (\Throwable $e) {
            error_log('UserActivityService log error: ' . $e->getMessage());
        }
    }

    /**
     * Zaman tüneli: topics, posts ve user_activities (like, rep, register) birleştirir.
     */
    public function getActivities(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $all = [];
        $fetchLimit = max(self::FETCH_LIMIT, $offset + $perPage + 20);
        $normalized = $this->normalizeFilters($filters);
        $actionFilter = $normalized['action'];
        $userFilter = $normalized['user'];

        try {
            // 1) Yeni açılan konular
            if ($actionFilter === self::FILTER_ALL || $actionFilter === self::ACTION_TOPIC_CREATED) {
                $topicsQuery = DB::table('topics as t')
                    ->join('users as u', 'u.id', '=', 't.user_id')
                    ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
                    ->leftJoin('forums as f', 'f.id', '=', 't.forum_id')
                    ->whereNull('t.deleted_at')
                    ->whereIn(DB::raw("COALESCE(t.type, 'topic')"), ['topic', 'question']);
                if ($userFilter !== '') {
                    $topicsQuery->where('u.username', '=', $userFilter);
                }
                $topics = $topicsQuery
                    ->orderByDesc('t.created_at')
                    ->limit($fetchLimit)
                    ->get([
                        't.id', 't.created_at', 't.title', 't.slug', 't.forum_id',
                        'u.username', 'u.avatar_path', 'r.color as role_color',
                        'f.name as forum_name',
                    ]);
                foreach ($topics as $row) {
                    $all[] = (object)[
                        'created_at'   => $row->created_at,
                        'username'     => $row->username,
                        'avatar_path'  => $row->avatar_path,
                        'role_color'   => $row->role_color,
                        'action_type'  => self::ACTION_TOPIC_CREATED,
                        'item_id'      => (int) $row->id,
                        'details'      => [
                            'title'     => $row->title,
                            'slug'      => $row->slug,
                            'forum_id'  => (int) $row->forum_id,
                            'forum_name' => $row->forum_name ?? '',
                        ],
                    ];
                }
            }

            // 2) Yeni yorumlar
            if ($actionFilter === self::FILTER_ALL || $actionFilter === self::ACTION_POST_CREATED) {
                $postsQuery = DB::table('posts as p')
                    ->join('topics as t', function ($j) {
                        $j->on('t.id', '=', 'p.topic_id')->whereNull('t.deleted_at');
                    })
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
                    ->where('p.is_first_post', 0)
                    ->whereNull('p.deleted_at');
                if ($userFilter !== '') {
                    $postsQuery->where('u.username', '=', $userFilter);
                }
                $posts = $postsQuery
                    ->orderByDesc('p.created_at')
                    ->limit($fetchLimit)
                    ->get([
                        'p.id', 'p.created_at', 'p.topic_id', 'p.body_html',
                        't.title as topic_title',
                        'u.username', 'u.avatar_path', 'r.color as role_color',
                    ]);
                foreach ($posts as $row) {
                    $snippet = '';
                    if (!empty($row->body_html)) {
                        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($row->body_html)));
                        $snippet = mb_strlen($plain) > self::BODY_SNIPPET_LEN
                            ? mb_substr($plain, 0, self::BODY_SNIPPET_LEN) . '…'
                            : $plain;
                    }
                    $all[] = (object)[
                        'created_at'  => $row->created_at,
                        'username'    => $row->username,
                        'avatar_path' => $row->avatar_path,
                        'role_color'  => $row->role_color,
                        'action_type' => self::ACTION_POST_CREATED,
                        'item_id'     => (int) $row->id,
                        'details'     => [
                            'topic_id'     => (int) $row->topic_id,
                            'topic_title'  => $row->topic_title ?? '',
                            'body_snippet' => $snippet,
                        ],
                    ];
                }
            }

            // 3) Beğeni / Rep / Kayıt: user_activities
            $canFetchActivity = $actionFilter === self::FILTER_ALL || in_array($actionFilter, [self::ACTION_LIKE_GIVEN, self::ACTION_REP_GIVEN, self::ACTION_USER_REGISTERED], true);
            if ($canFetchActivity) {
                $activitiesQuery = DB::table('user_activities as a')
                    ->join('users as u', 'u.id', '=', 'a.user_id')
                    ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
                    ->leftJoin('posts as p_like', function ($join) {
                        $join->on('p_like.id', '=', 'a.item_id')
                            ->where('a.action_type', '=', self::ACTION_LIKE_GIVEN)
                            ->whereNull('p_like.deleted_at');
                    })
                    ->leftJoin('topics as t_like', function ($join) {
                        $join->on('t_like.id', '=', 'p_like.topic_id')
                            ->where('a.action_type', '=', self::ACTION_LIKE_GIVEN)
                            ->whereNull('t_like.deleted_at');
                    });

                if ($userFilter !== '') {
                    $activitiesQuery->where('u.username', '=', $userFilter);
                }

                if ($actionFilter === self::FILTER_ALL) {
                    $activitiesQuery->where(function ($q) {
                        $q->whereIn('a.action_type', [self::ACTION_REP_GIVEN, self::ACTION_USER_REGISTERED])
                            ->orWhere(function ($q2) {
                                $q2->where('a.action_type', '=', self::ACTION_LIKE_GIVEN)
                                    ->whereNotNull('p_like.id')
                                    ->whereNotNull('t_like.id');
                            });
                    });
                } elseif ($actionFilter === self::ACTION_LIKE_GIVEN) {
                    $activitiesQuery->where('a.action_type', self::ACTION_LIKE_GIVEN)
                        ->whereNotNull('p_like.id')
                        ->whereNotNull('t_like.id');
                } else {
                    $activitiesQuery->where('a.action_type', $actionFilter);
                }

                $activities = $activitiesQuery
                    ->orderByDesc('a.created_at')
                    ->limit($fetchLimit)
                    ->get([
                        'a.created_at',
                        'a.action_type',
                        'a.item_id',
                        'a.details',
                        'u.username',
                        'u.avatar_path',
                        'r.color as role_color',
                        't_like.id as like_topic_id',
                        't_like.title as like_topic_title',
                    ]);

                foreach ($activities as $row) {
                    $details = $row->details ? (is_string($row->details) ? json_decode($row->details, true) : $row->details) : [];
                    if (!is_array($details)) {
                        $details = [];
                    }
                    if ($row->action_type === self::ACTION_LIKE_GIVEN && !empty($row->like_topic_title)) {
                        $details['topic_id'] = (int) $row->like_topic_id;
                        $details['topic_title'] = $row->like_topic_title;
                    }
                    $all[] = (object)[
                        'created_at'  => $row->created_at,
                        'username'    => $row->username,
                        'avatar_path' => $row->avatar_path,
                        'role_color'  => $row->role_color,
                        'action_type' => $row->action_type,
                        'item_id'     => (int) $row->item_id,
                        'details'     => $details,
                    ];
                }
            }

            usort($all, static fn ($a, $b) => strcmp($b->created_at, $a->created_at));
            return array_slice($all, $offset, $perPage);
        } catch (\Throwable $e) {
            error_log('UserActivityService getActivities error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{action: string, user: string}
     */
    public function normalizeFilters(array $filters): array
    {
        $action = (string) ($filters['action'] ?? self::FILTER_ALL);
        $action = in_array($action, array_merge([self::FILTER_ALL], self::FILTERABLE_ACTIONS), true) ? $action : self::FILTER_ALL;

        $user = trim((string) ($filters['user'] ?? ''));
        $user = preg_replace('/[\x00-\x1F\x7F]/u', '', $user) ?? '';
        if (mb_strlen($user) > 64) {
            $user = mb_substr($user, 0, 64);
        }

        return ['action' => $action, 'user' => $user];
    }
}
