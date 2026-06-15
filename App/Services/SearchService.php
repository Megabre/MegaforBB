<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocPage;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Modules\Idelist\Models\Idea;

/**
 * Unified site search: forums, topics, articles, posts, users, docs, ideas.
 */
class SearchService
{
    public const PER_PAGE = 15;

    public const LIVE_PREVIEW_LIMIT = 5;

    private const VALID_TYPES = ['all', 'forums', 'topics', 'articles', 'posts', 'users', 'docs', 'ideas'];

    /** @var array<string, bool> */
    private array $features;

    public function __construct(array $features = [])
    {
        $this->features = array_merge([
            'documentation_enabled' => false,
            'idelist_enabled' => false,
        ], $features);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function search(array $params): array
    {
        $query = trim((string)($params['q'] ?? ''));
        $tag = trim((string)($params['tag'] ?? ''));
        $type = (string)($params['type'] ?? 'all');
        $forumId = isset($params['forum_id']) ? (int)$params['forum_id'] : null;
        if ($forumId === 0) {
            $forumId = null;
        }
        $author = trim((string)($params['author'] ?? ''));
        $dateFrom = trim((string)($params['date_from'] ?? ''));
        $dateTo = trim((string)($params['date_to'] ?? ''));
        $page = max(1, (int)($params['page'] ?? 1));
        $live = (bool)($params['live'] ?? false);

        if (!in_array($type, self::VALID_TYPES, true)) {
            $type = 'all';
        }

        $performSearch = mb_strlen($query) >= 2 || $tag !== '';
        $limit = $live && $type === 'all' ? self::LIVE_PREVIEW_LIMIT : self::PER_PAGE;
        $offset = ($page - 1) * self::PER_PAGE;

        $results = [
            'forums' => [],
            'topics' => [],
            'articles' => [],
            'posts' => [],
            'users' => [],
            'docs' => [],
            'ideas' => [],
        ];
        $totals = array_fill_keys(array_keys($results), 0);

        if ($performSearch) {
            $searchQuery = $this->sanitizeSearchQuery($query);
            $useFulltext = $searchQuery !== '';
            $typesToSearch = $this->resolveSearchTypes($type);

            if (in_array('forums', $typesToSearch, true)) {
                $data = $this->searchForums($query, $limit, $type === 'all' && $live ? 0 : $offset);
                $results['forums'] = $data['items'];
                $totals['forums'] = $data['total'];
            }
            if (in_array('topics', $typesToSearch, true)) {
                $data = $this->searchTopics($searchQuery, $query, $forumId, $author, $dateFrom, $dateTo, $tag, false, $limit, $type === 'all' && $live ? 0 : $offset);
                $results['topics'] = $data['items'];
                $totals['topics'] = $data['total'];
            }
            if (in_array('articles', $typesToSearch, true)) {
                $data = $this->searchTopics($searchQuery, $query, $forumId, $author, $dateFrom, $dateTo, $tag, true, $limit, $type === 'all' && $live ? 0 : $offset);
                $results['articles'] = $data['items'];
                $totals['articles'] = $data['total'];
            }
            if (in_array('posts', $typesToSearch, true)) {
                $data = $this->searchPosts($searchQuery, $query, $forumId, $author, $dateFrom, $dateTo, $useFulltext, $limit, $type === 'all' && $live ? 0 : $offset);
                $results['posts'] = $data['items'];
                $totals['posts'] = $data['total'];
            }
            if (in_array('users', $typesToSearch, true)) {
                $data = $this->searchUsers($query, $dateFrom, $dateTo, $limit, $type === 'all' && $live ? 0 : $offset);
                $results['users'] = $data['items'];
                $totals['users'] = $data['total'];
            }
            if (in_array('docs', $typesToSearch, true) && $this->features['documentation_enabled']) {
                $data = $this->searchDocs($query, $limit, $type === 'all' && $live ? 0 : $offset);
                $results['docs'] = $data['items'];
                $totals['docs'] = $data['total'];
            }
            if (in_array('ideas', $typesToSearch, true) && $this->features['idelist_enabled']) {
                $data = $this->searchIdeas($query, $limit, $type === 'all' && $live ? 0 : $offset);
                $results['ideas'] = $data['items'];
                $totals['ideas'] = $data['total'];
            }
        }

        $totalResults = array_sum($totals);
        $activeTotal = $type === 'all' ? $totalResults : ($totals[$type] ?? 0);
        $totalPages = $type === 'all'
            ? 1
            : max(1, (int)ceil($activeTotal / self::PER_PAGE));

        return [
            'query' => $query,
            'tag' => $tag,
            'type' => $type,
            'results' => $results,
            'totals' => $totals,
            'totalResults' => $totalResults,
            'page' => $page,
            'totalPages' => $totalPages,
            'performSearch' => $performSearch,
        ];
    }

    /** @return list<string> */
    private function resolveSearchTypes(string $type): array
    {
        if ($type === 'all') {
            $types = ['forums', 'topics', 'articles', 'posts', 'users'];
            if ($this->features['documentation_enabled']) {
                $types[] = 'docs';
            }
            if ($this->features['idelist_enabled']) {
                $types[] = 'ideas';
            }
            return $types;
        }
        return [$type];
    }

    private function sanitizeSearchQuery(string $query): string
    {
        $words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        $safe = [];
        foreach ($words as $w) {
            $w = preg_replace('/[+\-*"()]/u', '', $w);
            if ($w !== '') {
                $safe[] = '+' . $w . '*';
            }
        }
        return implode(' ', $safe) ?: $query;
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    private function searchForums(string $query, int $limit, int $offset): array
    {
        $baseQuery = Forum::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%$query%")
                    ->orWhere('description', 'like', "%$query%");
            });

        $total = (clone $baseQuery)->count();
        $items = (clone $baseQuery)
            ->orderBy('name')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Forum $f) => [
                'id' => (int)$f->id,
                'name' => $f->name,
                'slug' => $f->slug,
                'description' => mb_substr(strip_tags((string)($f->description ?? '')), 0, 160),
                'topic_count' => (int)($f->topic_count ?? 0),
                'post_count' => (int)($f->post_count ?? 0),
                'url' => core_url('forum/' . rawurlencode((string)$f->slug)),
            ])
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    private function searchTopics(
        string $searchQuery,
        string $likeQuery,
        ?int $forumId,
        string $author,
        string $dateFrom,
        string $dateTo,
        string $tag,
        bool $articlesOnly,
        int $limit,
        int $offset
    ): array {
        if ($tag !== '') {
            return $this->searchTopicsByTag($tag, $forumId, $articlesOnly, $limit, $offset);
        }

        $options = ['limit' => $limit + 20, 'offset' => $offset];
        $filters = [];
        if ($forumId) {
            $filters[] = "forum_id = $forumId";
        }
        if (!empty($filters)) {
            $options['filter'] = implode(' AND ', $filters);
        }

        try {
            $meili = new MeilisearchService();
            $data = $meili->searchWithTotal($searchQuery ?: $likeQuery, $options);
            $hits = $data['hits'] ?? [];
            $ids = array_map(fn ($h) => (int)($h['id'] ?? 0), $hits);
            $ids = array_values(array_filter($ids));

            if ($ids === []) {
                return ['items' => [], 'total' => 0];
            }

            $dbQuery = Topic::with(['user', 'forum'])
                ->published()
                ->whereIn('id', $ids)
                ->whereNull('deleted_at');

            if ($articlesOnly) {
                $dbQuery->where('type', 'article');
            } else {
                $dbQuery->where(function ($q) {
                    $q->whereNull('type')->orWhere('type', '!=', 'article');
                });
            }
            if ($author !== '') {
                $dbQuery->whereHas('user', fn ($q) => $q->where('username', 'like', "%$author%"));
            }
            if ($dateFrom !== '') {
                $dbQuery->where('created_at', '>=', $dateFrom . ' 00:00:00');
            }
            if ($dateTo !== '') {
                $dbQuery->where('created_at', '<=', $dateTo . ' 23:59:59');
            }

            $topics = $dbQuery->get()->keyBy('id');
            $items = [];
            foreach ($hits as $hit) {
                $id = (int)($hit['id'] ?? 0);
                $topic = $topics->get($id);
                if (!$topic) {
                    continue;
                }
                $items[] = $this->formatTopic($topic);
                if (count($items) >= $limit) {
                    break;
                }
            }

            $total = count($items) < $limit
                ? count($items)
                : (int)($data['total'] ?? count($items));

            return ['items' => $items, 'total' => $total];
        } catch (\Throwable $e) {
            return ['items' => [], 'total' => 0];
        }
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    private function searchTopicsByTag(string $tag, ?int $forumId, bool $articlesOnly, int $limit, int $offset): array
    {
        $baseQuery = Topic::with(['user', 'forum'])
            ->published()
            ->whereNull('deleted_at')
            ->whereHas('tags', fn ($q) => $q->where('slug', $tag)->orWhere('name', $tag));

        if ($forumId) {
            $baseQuery->where('forum_id', $forumId);
        }
        if ($articlesOnly) {
            $baseQuery->where('type', 'article');
        } else {
            $baseQuery->where(function ($q) {
                $q->whereNull('type')->orWhere('type', '!=', 'article');
            });
        }

        $total = (clone $baseQuery)->count();
        $items = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Topic $t) => $this->formatTopic($t))
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /** @return array<string, mixed> */
    private function formatTopic(Topic $topic): array
    {
        return [
            'id' => (int)$topic->id,
            'title' => $topic->title,
            'slug' => $topic->slug,
            'url_key' => $topic->url_key ?? null,
            'username' => $topic->user->username ?? '',
            'forum_name' => $topic->forum->name ?? '',
            'forum_slug' => $topic->forum->slug ?? '',
            'reply_count' => (int)($topic->reply_count ?? 0),
            'view_count' => (int)($topic->view_count ?? 0),
            'created_at' => $topic->created_at?->format('Y-m-d H:i') ?? '',
            'url' => core_url('topic/' . topic_url_path($topic)),
            'type' => $topic->type ?? 'topic',
        ];
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    private function searchPosts(
        string $searchQuery,
        string $likeQuery,
        ?int $forumId,
        string $author,
        string $dateFrom,
        string $dateTo,
        bool $useFulltext,
        int $limit,
        int $offset
    ): array {
        $baseQuery = Post::with(['user', 'topic.forum'])
            ->whereNull('deleted_at')
            ->whereHas('topic', function ($q) use ($forumId) {
                $q->whereNull('deleted_at');
                if ($forumId) {
                    $q->where('forum_id', $forumId);
                }
            });

        if ($author !== '') {
            $baseQuery->whereHas('user', fn ($q) => $q->where('username', 'like', "%$author%"));
        }
        if ($dateFrom !== '') {
            $baseQuery->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $baseQuery->where('created_at', '<=', $dateTo . ' 23:59:59');
        }
        if ($useFulltext && $searchQuery !== '') {
            $baseQuery->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$searchQuery])
                ->orderByRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE) DESC', [$searchQuery]);
        } else {
            $words = preg_split('/\s+/u', $likeQuery, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($words as $w) {
                $baseQuery->where('body', 'like', "%$w%");
            }
        }

        $total = (clone $baseQuery)->count();
        $items = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function (Post $p) {
                $topic = $p->topic;
                $snippet = mb_substr(strip_tags((string)$p->body), 0, 220);
                return [
                    'id' => (int)$p->id,
                    'topic_id' => (int)($p->topic_id ?? 0),
                    'topic_title' => $topic->title ?? '',
                    'forum_name' => $topic?->forum->name ?? '',
                    'username' => $p->user->username ?? '',
                    'body_snippet' => $snippet,
                    'created_at' => $p->created_at?->format('Y-m-d H:i') ?? '',
                    'url' => $topic
                        ? core_url('topic/' . topic_url_path($topic) . '#post-' . $p->id)
                        : core_url('topic/' . ($p->topic_id ?? 0)),
                ];
            })
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    private function searchUsers(string $query, string $dateFrom, string $dateTo, int $limit, int $offset): array
    {
        $baseQuery = User::where('is_banned', 0)
            ->where('username', 'like', "%$query%");
        if ($dateFrom !== '') {
            $baseQuery->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $baseQuery->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $total = (clone $baseQuery)->count();
        $items = (clone $baseQuery)
            ->orderBy('username')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function (User $u) {
                $avatar = (string)($u->avatar_path ?? '');
                return [
                    'id' => (int)$u->id,
                    'username' => $u->username,
                    'post_count' => (int)($u->post_count ?? 0),
                    'created_at' => $u->created_at?->format('Y-m-d') ?? '',
                    'avatar_url' => $avatar !== '' ? asset_url($avatar) : null,
                    'url' => core_url('member/' . rawurlencode((string)$u->username)),
                ];
            })
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    private function searchDocs(string $query, int $limit, int $offset): array
    {
        $baseQuery = DocPage::with('section')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%$query%")
                    ->orWhere('content', 'like', "%$query%");
            });

        $total = (clone $baseQuery)->count();
        $items = (clone $baseQuery)
            ->orderBy('title')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function (DocPage $page) use ($query) {
                $section = $page->section;
                $path = $section ? $section->getPathString() . '/' . $page->slug : $page->slug;
                $plain = strip_tags((string)$page->content);
                $pos = mb_stripos($plain, $query);
                $snippet = $pos !== false
                    ? mb_substr($plain, max(0, $pos - 40), 200)
                    : mb_substr($plain, 0, 200);

                return [
                    'id' => (int)$page->id,
                    'title' => $page->title,
                    'section_title' => $section->title ?? '',
                    'snippet' => $snippet,
                    'url' => core_url('documentation/' . $path),
                ];
            })
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    private function searchIdeas(string $query, int $limit, int $offset): array
    {
        $baseQuery = Idea::with(['user', 'category'])
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%$query%")
                    ->orWhere('description', 'like', "%$query%");
            });

        $total = (clone $baseQuery)->count();
        $items = (clone $baseQuery)
            ->orderByDesc('vote_count')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function (Idea $idea) {
                return [
                    'id' => (int)$idea->id,
                    'title' => $idea->title,
                    'slug' => $idea->slug,
                    'username' => $idea->user->username ?? '',
                    'category_name' => $idea->category->name ?? '',
                    'status' => $idea->status ?? '',
                    'vote_count' => (int)($idea->vote_count ?? 0),
                    'views_count' => (int)($idea->views_count ?? 0),
                    'snippet' => mb_substr(strip_tags((string)$idea->description), 0, 200),
                    'created_at' => $idea->created_at?->format('Y-m-d') ?? '',
                    'url' => core_url('idelist/' . rawurlencode((string)$idea->slug)),
                ];
            })
            ->all();

        return ['items' => $items, 'total' => $total];
    }
}
