<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Prefix;
use App\Models\Tag;
use App\Models\Topic;
use App\Services\SefUrlService;
use App\Services\TopicUrlService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin: Konu Ayarları – Tüm konu işlemleri (listeleme, filtreleme, tek düzenleme, toplu işlemler).
 */
class AdminTopicSettingsController extends AdminController
{
    private const TOPIC_TYPES = ['topic', 'article', 'question', 'poll'];
    private const TOPIC_STATUSES = ['published', 'scheduled', 'cancelled'];
    private const BULK_ACTIONS = [
        'delete',
        'restore',
        'move_forum',
        'set_prefix',
        'set_type',
        'lock',
        'unlock',
        'sticky',
        'unsticky',
        'convert_to_article',
        'convert_to_topic',
        'set_status',
        'set_private',
        'set_public',
    ];

    public function index(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $query = Topic::withTrashed()
            ->with(['forum:id,name,slug,category_id', 'forum.category:id,name,slug', 'user:id,username', 'prefix:id,name,slug']);

        // Filtreler
        $search = trim((string) ($_GET['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('id', '=', (int) $search);
            });
        }
        $forumId = isset($_GET['forum_id']) ? (int) $_GET['forum_id'] : 0;
        if ($forumId > 0) {
            $query->where('forum_id', $forumId);
        }
        $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : 0;
        if ($categoryId > 0) {
            $query->whereHas('forum', fn (Builder $q) => $q->where('category_id', $categoryId));
        }
        $type = trim((string) ($_GET['type'] ?? ''));
        if ($type !== '' && in_array($type, self::TOPIC_TYPES, true)) {
            $query->whereRaw("COALESCE(type, 'topic') = ?", [$type]);
        }
        $status = trim((string) ($_GET['status'] ?? ''));
        if ($status !== '') {
            if ($status === 'trashed') {
                $query->onlyTrashed();
            } elseif (in_array($status, self::TOPIC_STATUSES, true)) {
                $query->whereNull('deleted_at')->where('status', $status);
            }
        } else {
            $query->whereNull('deleted_at');
        }
        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sort = $_GET['sort'] ?? 'id_desc';
        $sortColumn = 'id';
        $sortDir = 'desc';
        if ($sort === 'title_asc') {
            $sortColumn = 'title';
            $sortDir = 'asc';
        } elseif ($sort === 'title_desc') {
            $sortColumn = 'title';
            $sortDir = 'desc';
        } elseif ($sort === 'created_asc') {
            $sortColumn = 'created_at';
            $sortDir = 'asc';
        } elseif ($sort === 'created_desc') {
            $sortColumn = 'created_at';
            $sortDir = 'desc';
        } elseif ($sort === 'last_post_desc') {
            $sortColumn = 'last_post_at';
            $sortDir = 'desc';
        } elseif ($sort === 'views_desc') {
            $sortColumn = 'view_count';
            $sortDir = 'desc';
        }
        $query->orderBy($sortColumn, $sortDir);

        $perPage = min(100, max(10, (int) ($_GET['per_page'] ?? 25)));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $total = $query->count();
        $topics = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
        $basePath = core_url($adminPath . '/topic-settings');
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $firstItem = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
        $lastItem = $total === 0 ? 0 : min($page * $perPage, $total);
        $pageLinks = [];
        $queryGet = $_GET;
        for ($i = 1; $i <= $lastPage; $i++) {
            $queryGet['page'] = $i;
            $pageLinks[] = [
                'label' => (string) $i,
                'url' => $basePath . '?' . http_build_query($queryGet),
                'active' => $i === $page,
            ];
        }
        $paginator = (object) [
            'total' => $total,
            'firstItem' => $firstItem,
            'lastItem' => $lastItem,
            'currentPage' => $page,
        ];

        $categories = Category::orderBy('sort_order')->orderBy('id')->get(['id', 'name']);
        $forums = Forum::orderBy('category_id')->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'slug', 'category_id']);
        $prefixes = Prefix::orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'slug']);

        return $this->view('topics/index', [
            'pageTitle' => lang('admin.topic_settings.title'),
            'topics' => $topics,
            'paginator' => $paginator,
            'categories' => $categories,
            'forums' => $forums,
            'prefixes' => $prefixes,
            'filters' => [
                'search' => $search,
                'forum_id' => $forumId,
                'category_id' => $categoryId,
                'type' => $type,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
                'per_page' => $perPage,
                'page' => $page,
            ],
            'topicTypes' => self::TOPIC_TYPES,
            'topicStatuses' => self::TOPIC_STATUSES,
            'pageLinks' => $pageLinks,
        ]);
    }

    public function edit(string $id): string
    {
        $topic = Topic::withTrashed()->with(['forum.category', 'user', 'prefix', 'tags'])->findOrFail((int) $id);
        $categories = Category::orderBy('sort_order')->get();
        $forums = Forum::with('category')->orderBy('category_id')->orderBy('sort_order')->get();
        $prefixes = Prefix::orderBy('sort_order')->get();
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);
        $topicTagIds = $topic->tags->pluck('id')->toArray();

        $firstPost = Post::where('topic_id', $topic->id)->where('is_first_post', 1)->first();
        $privateViewers = $topic->privateViewers ?? collect();
        $topicUrlService = new TopicUrlService();
        $topicPublicUrl = core_url('topic/' . $topicUrlService->pathForTopic($topic));

        return $this->view('topics/edit', [
            'pageTitle' => lang('admin.topic_settings.edit_title'),
            'topic' => $topic,
            'firstPost' => $firstPost,
            'categories' => $categories,
            'forums' => $forums,
            'prefixes' => $prefixes,
            'tags' => $tags,
            'topicTagIds' => $topicTagIds,
            'privateViewers' => $privateViewers,
            'topicTypes' => self::TOPIC_TYPES,
            'topicStatuses' => self::TOPIC_STATUSES,
            'topicPublicUrl' => $topicPublicUrl,
        ]);
    }

    public function update(string $id): void
    {
        if (!core_csrf_valid('admin_topic_settings_update', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/topic-settings'));
            return;
        }
        $topic = Topic::withTrashed()->findOrFail((int) $id);

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title !== '') {
            $topic->title = $title;
            $topicUrl = new TopicUrlService();
            $topic->slug = $topicUrl->generateSlugFromTitle($title, (int) $topic->id);
            $sef = new SefUrlService();
            if ($sef->getMode() === SefUrlService::MODE_RANDOM && (empty($topic->url_key))) {
                $topicUrl->ensureUrlKey($topic);
            }
        }

        $topic->forum_id = (int) ($_POST['forum_id'] ?? $topic->forum_id);
        $topic->prefix_id = isset($_POST['prefix_id']) && $_POST['prefix_id'] !== '' ? (int) $_POST['prefix_id'] : null;
        $topic->type = in_array($_POST['type'] ?? '', self::TOPIC_TYPES, true) ? $_POST['type'] : ($topic->type ?? 'topic');
        $topic->is_sticky = isset($_POST['is_sticky']) && $_POST['is_sticky'] === '1' ? 1 : 0;
        $topic->is_locked = isset($_POST['is_locked']) && $_POST['is_locked'] === '1' ? 1 : 0;
        $topic->is_private = isset($_POST['is_private']) && $_POST['is_private'] === '1' ? 1 : 0;
        $topic->is_solved = isset($_POST['is_solved']) && $_POST['is_solved'] === '1' ? 1 : 0;
        $topic->accepted_post_id = isset($_POST['accepted_post_id']) && $_POST['accepted_post_id'] !== '' ? (int) $_POST['accepted_post_id'] : null;
        $topic->status = in_array($_POST['status'] ?? '', self::TOPIC_STATUSES, true) ? $_POST['status'] : ($topic->status ?? 'published');
        $scheduled = trim((string) ($_POST['scheduled_publish_at'] ?? ''));
        $topic->scheduled_publish_at = $scheduled !== '' ? $scheduled : null;

        if ($topic->trashed()) {
            $topic->deleted_at = null;
            $topic->deleted_by = null;
        }
        $topic->save();

        // Etiketler (mevcut seçim + yeni eklenen isimler)
        $tagIds = [];
        if (!empty($_POST['tag_ids']) && is_array($_POST['tag_ids'])) {
            $tagIds = array_map('intval', array_filter($_POST['tag_ids']));
        }
        $newTagNames = trim((string) ($_POST['new_tag_names'] ?? ''));
        if ($newTagNames !== '') {
            $names = array_unique(array_filter(array_map('trim', preg_split('/[,;\n]+/', $newTagNames))));
            foreach ($names as $name) {
                if ($name === '') {
                    continue;
                }
                $slug = \Forecor\Core\Str::slug($name) ?: 'tag-' . substr(md5($name), 0, 8);
                $existing = Tag::where('slug', $slug)->orWhere('name', $name)->first();
                if ($existing) {
                    $tagIds[] = $existing->id;
                } else {
                    $tag = Tag::create(['name' => $name, 'slug' => $slug, 'use_count' => 0]);
                    $tagIds[] = $tag->id;
                }
            }
            $tagIds = array_values(array_unique($tagIds));
        }
        $topic->tags()->sync($tagIds);

        // İlk mesaj gövdesi (opsiyonel) – Summernote HTML veya BBCode
        $firstPostBody = $_POST['first_post_body'] ?? null;
        if ($firstPostBody !== null) {
            $first = Post::where('topic_id', $topic->id)->where('is_first_post', 1)->first();
            if ($first) {
                $first->body = $firstPostBody;
                if (function_exists('core_sanitize_html')) {
                    $isHtml = (bool) preg_match('/<[a-z][a-z0-9]*[\s>]/i', $firstPostBody);
                    $bodyHtml = $isHtml
                        ? core_sanitize_html($firstPostBody)
                        : (function_exists('core_body_to_html') ? core_body_to_html($firstPostBody) : core_sanitize_html(core_quote_bb_to_html($firstPostBody)));
                    if (function_exists('core_process_mentions')) {
                        $bodyHtml = core_process_mentions($bodyHtml);
                    }
                    if (function_exists('core_process_post_refs')) {
                        $bodyHtml = core_process_post_refs($bodyHtml, (int) $topic->id);
                    }
                    $first->body_html = $bodyHtml;
                } else {
                    $first->body_html = $firstPostBody;
                }
                $first->save();
            }
        }

        $this->app->cache()->delete('home_categories');
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/topic-settings/edit/' . $topic->id));
    }

    public function bulk(): void
    {
        if (!core_csrf_valid('admin_topic_settings_bulk', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/topic-settings'));
            return;
        }
        $ids = isset($_POST['topic_ids']) && is_array($_POST['topic_ids'])
            ? array_map('intval', array_filter($_POST['topic_ids']))
            : [];
        $action = trim((string) ($_POST['bulk_action'] ?? ''));
        if (empty($ids) || !in_array($action, self::BULK_ACTIONS, true)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/topic-settings'));
            return;
        }

        $query = Topic::withTrashed()->whereIn('id', $ids);
        $topicUrl = new TopicUrlService();
        $count = 0;

        switch ($action) {
            case 'delete':
                foreach ($query->get() as $topic) {
                    if (!$topic->trashed()) {
                        $topic->delete();
                        $topic->update(['deleted_by' => $this->app->auth()->user()?->id]);
                        $count++;
                    }
                }
                break;
            case 'restore':
                foreach ($query->onlyTrashed()->get() as $topic) {
                    $topic->restore();
                    $topic->update(['deleted_by' => null]);
                    $count++;
                }
                break;
            case 'move_forum':
                $forumId = (int) ($_POST['bulk_forum_id'] ?? 0);
                if ($forumId > 0) {
                    $count = $query->update(['forum_id' => $forumId]);
                }
                break;
            case 'set_prefix':
                $prefixId = isset($_POST['bulk_prefix_id']) && $_POST['bulk_prefix_id'] !== '' ? (int) $_POST['bulk_prefix_id'] : null;
                $count = $query->update(['prefix_id' => $prefixId]);
                break;
            case 'set_type':
                $type = trim((string) ($_POST['bulk_type'] ?? ''));
                if (in_array($type, self::TOPIC_TYPES, true)) {
                    $count = $query->update(['type' => $type]);
                }
                break;
            case 'lock':
                $count = $query->update(['is_locked' => 1]);
                break;
            case 'unlock':
                $count = $query->update(['is_locked' => 0]);
                break;
            case 'sticky':
                $count = $query->update(['is_sticky' => 1]);
                break;
            case 'unsticky':
                $count = $query->update(['is_sticky' => 0]);
                break;
            case 'convert_to_article':
                $count = $query->update(['type' => 'article']);
                break;
            case 'convert_to_topic':
                $count = $query->update(['type' => 'topic']);
                break;
            case 'set_status':
                $status = trim((string) ($_POST['bulk_status'] ?? ''));
                if (in_array($status, self::TOPIC_STATUSES, true)) {
                    $count = $query->update(['status' => $status]);
                }
                break;
            case 'set_private':
                $count = $query->update(['is_private' => 1]);
                break;
            case 'set_public':
                $count = $query->update(['is_private' => 0]);
                break;
        }

        $this->app->cache()->delete('home_categories');
        $redirect = core_url(env('ADMIN_PATH', 'admin') . '/topic-settings');
        $queryParams = array_filter([
            'search' => $_POST['return_search'] ?? $_GET['search'] ?? '',
            'forum_id' => $_POST['return_forum_id'] ?? $_GET['forum_id'] ?? '',
            'category_id' => $_POST['return_category_id'] ?? $_GET['category_id'] ?? '',
            'type' => $_POST['return_type'] ?? $_GET['type'] ?? '',
            'status' => $_POST['return_status'] ?? $_GET['status'] ?? '',
            'page' => $_POST['return_page'] ?? $_GET['page'] ?? '',
        ]);
        if (!empty($queryParams)) {
            $redirect .= '?' . http_build_query($queryParams);
        }
        $this->redirect($redirect);
    }
}
