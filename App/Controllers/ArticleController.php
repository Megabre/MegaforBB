<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use App\Services\TopicPrefixScopeService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Makale listesi ve makale detay (full width, postbit üstte, yorumlar altta).
 * Articles are stored in topics table with type='article'.
 */
class ArticleController extends TopicController
{
    private function getViewerContext(): array
    {
        $user = $this->app->auth()->user();
        $userId = $user ? (int) $user->id : null;
        $isStaff = $user && $user->role && $user->role->is_staff;
        return [$userId, $isStaff];
    }

    /** Makale kategorilerindeki forum ID listesi (yazım izni ve filtre için). */
    private function getArticleForumIds(): array
    {
        $categoryIds = Category::articleCategories()->pluck('id')->all();
        if ($categoryIds === []) {
            return [];
        }
        return Forum::whereIn('category_id', $categoryIds)->pluck('id')->all();
    }

    /** Makale kategorileri ve içlerindeki forumlar (create form ve index filtre için). */
    private function getArticleCategoriesWithForums(): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $categories = Category::articleCategories()->orderBy('sort_order')->orderBy('id')->get();
        $out = [];
        foreach ($categories as $cat) {
            $forums = Forum::where('category_id', $cat->id)->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'slug']);
            if ($forums->isNotEmpty()) {
                $forumIds = $forums->pluck('id')->all();
                $articleCount = Topic::visibleToUserWithPrivacy($userId, $isStaff)
                    ->whereRaw("COALESCE(type, 'topic') = 'article'")
                    ->whereIn('forum_id', $forumIds)
                    ->count();
                $out[] = (object) ['id' => $cat->id, 'name' => $cat->name, 'slug' => $cat->slug, 'icon' => $cat->icon ?? '', 'color' => $cat->color ?? '#6b7280', 'forums' => $forums, 'article_count' => $articleCount];
            }
        }
        return $out;
    }

    /** Makale sayfası sol sidebar verileri: yazarlar, istatistikler, en çok okunanlar. */
    private function getArticleSidebarData(): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $articleForumIds = $this->getArticleForumIds();
        if ($articleForumIds === []) {
            return ['top_authors' => [], 'stats' => ['total' => 0, 'total_views' => 0], 'most_read' => []];
        }
        $baseQuery = Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->whereRaw("COALESCE(type, 'topic') = 'article'")
            ->whereIn('forum_id', $articleForumIds);
        $total = (clone $baseQuery)->count();
        $totalViews = (clone $baseQuery)->sum('view_count');
        $topAuthors = Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->whereRaw("COALESCE(type, 'topic') = 'article'")
            ->whereIn('forum_id', $articleForumIds)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();
        $userIds = $topAuthors->pluck('user_id')->unique()->filter()->all();
        $users = $userIds !== [] ? \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id') : collect();
        $topAuthorsWithNames = $topAuthors->map(function ($row) use ($users) {
            $u = $users->get($row->user_id);
            return (object) ['user_id' => $row->user_id, 'username' => $u ? $u->username : '', 'article_count' => (int) $row->cnt];
        })->all();
        $mostRead = (clone $baseQuery)->orderByDesc('view_count')->limit(8)->get(['id', 'title', 'slug', 'view_count']);
        return [
            'top_authors' => $topAuthorsWithNames,
            'stats' => ['total' => $total, 'total_views' => (int) $totalViews],
            'most_read' => $mostRead,
        ];
    }

    /** İlk img src'sini body_html'den çıkar */
    private static function extractCoverImage(?string $bodyHtml): ?string
    {
        if ($bodyHtml === null || $bodyHtml === '') {
            return null;
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $bodyHtml, $m)) {
            return $m[1];
        }
        return null;
    }

    /** Makale listesi: /articles — forum filtresi (sidebar forumlara göre), view=list|grid */
    public function index(): string
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $viewParam = trim((string) ($_GET['view'] ?? ''));
        $viewMode = $viewParam === 'grid' ? 'grid' : ($viewParam === 'list' ? 'list' : ($this->getSetting('articles_view_mode', 'list') === 'grid' ? 'grid' : 'list'));
        $forumSlug = trim((string) ($_GET['forum'] ?? ''));
        $perPage = 20;
        [$userId, $isStaff] = $this->getViewerContext();

        $articleForumIds = $this->getArticleForumIds();
        $articleForumsSidebar = $this->getArticleForumsForSidebar();
        $currentForumSlug = null;
        $forumIdForFilter = null;
        if ($forumSlug !== '' && $articleForumIds !== []) {
            $forum = Forum::whereIn('id', $articleForumIds)->where('slug', $forumSlug)->first(['id', 'slug', 'name']);
            if ($forum) {
                $forumIdForFilter = (int) $forum->id;
                $currentForumSlug = $forum->slug;
            }
        }

        $query = Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->with('user')
            ->whereRaw("COALESCE(type, 'topic') = 'article'");
        if ($forumIdForFilter !== null) {
            $query->where('forum_id', $forumIdForFilter);
        }
        $total = $query->count();
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        $articles = (clone $query)
            ->orderByRaw('COALESCE(last_post_at, created_at) DESC')->orderByDesc('id')
            ->offset(($page - 1) * $perPage)->limit($perPage)
            ->get();
        $firstPostBodies = Post::whereIn('topic_id', $articles->pluck('id'))->where('is_first_post', 1)->pluck('body_html', 'topic_id');
        foreach ($articles as $a) {
            $a->author_avatar_path = $a->user ? $a->user->avatar_path : null;
            $a->username = $a->user ? $a->user->username : null;
            $a->first_body_html = $firstPostBodies[$a->id] ?? null;
            $a->cover_image = self::extractCoverImage($a->first_body_html ?? null);
        }

        $user = $this->app->auth()->user();
        $canCreate = $user && $articleForumIds !== [];
        $articleSidebar = $this->getArticleSidebarData();

        return $this->layout('article/index', [
            'pageTitle' => lang('article.page_title_list'),
            'articles' => $articles,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'viewMode' => $viewMode,
            'canCreate' => $canCreate,
            'articleForumsSidebar' => $articleForumsSidebar,
            'currentForumSlug' => $currentForumSlug,
            'articleSidebar' => $articleSidebar,
        ], false);
    }

    /** Sidebar için makale kategorilerindeki forumlar + makale sayısı (sıralı). */
    private function getArticleForumsForSidebar(): array
    {
        [$userId, $isStaff] = $this->getViewerContext();
        $categories = Category::articleCategories()->orderBy('sort_order')->orderBy('id')->get();
        $out = [];
        foreach ($categories as $cat) {
            $forums = Forum::where('category_id', $cat->id)->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'slug']);
            foreach ($forums as $f) {
                $count = Topic::visibleToUserWithPrivacy($userId, $isStaff)
                    ->whereRaw("COALESCE(type, 'topic') = 'article'")
                    ->where('forum_id', $f->id)
                    ->count();
                $out[] = (object) ['id' => $f->id, 'name' => $f->name, 'slug' => $f->slug, 'category_name' => $cat->name, 'article_count' => $count];
            }
        }
        return $out;
    }

    /** Makale ekleme formu: /articles/new — makale kategorilerindeki forumlar listelenir */
    public function create(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $articleCategoriesWithForums = $this->getArticleCategoriesWithForums();
        $allowedForumIds = $this->getArticleForumIds();
        if ($allowedForumIds === []) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('article.enable_in_admin'));
            $this->redirect(core_url('articles'));
            return '';
        }
        $defaultForumId = (int) $this->getSetting('article_forum_id', '0');
        if (!\in_array($defaultForumId, $allowedForumIds, true)) {
            $defaultForumId = $allowedForumIds[0];
        }
        $prefixesByForum = [];
        foreach ($allowedForumIds as $fid) {
            $fObj = Forum::find((int) $fid);
            if ($fObj) {
                $prefixesByForum[(int) $fid] = TopicPrefixScopeService::prefixesForForum($fObj)
                    ->map(static fn ($p) => ['id' => (int) $p->id, 'name' => (string) $p->name])
                    ->values()
                    ->all();
            }
        }
        $defaultForumObj = Forum::find($defaultForumId);
        $prefixes = $defaultForumObj ? TopicPrefixScopeService::prefixesForForum($defaultForumObj)->all() : [];
        $error = $this->app->session()->getFlashBag()->get('topic_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;

        return $this->layout('article/create', [
            'pageTitle' => lang('article.add_page_title'),
            'articleCategoriesWithForums' => $articleCategoriesWithForums,
            'defaultForumId' => $defaultForumId,
            'prefixes' => $prefixes,
            'prefixes_by_forum_json' => json_encode($prefixesByForum, JSON_UNESCAPED_UNICODE),
            'error' => $error,
        ], true);
    }

    /** Makale kaydet: POST /articles/store */
    public function store(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('new_topic', (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('articles/new'));
            return '';
        }
        $allowedForumIds = $this->getArticleForumIds();
        if ($allowedForumIds === []) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('article.not_configured'));
            $this->redirect(core_url('articles'));
            return '';
        }
        $articleForumId = (int) ($_POST['forum_id'] ?? 0);
        if (!\in_array($articleForumId, $allowedForumIds, true)) {
            $articleForumId = $allowedForumIds[0];
        }
        $role = $user->role;
        if ($role && $role->daily_topic_limit > 0) {
            $todayStart = \now()->startOfDay()->format('Y-m-d H:i:s');
            $topicsToday = Topic::where('user_id', $user->id)->where('created_at', '>=', $todayStart)->count();
            if ($topicsToday >= $role->daily_topic_limit) {
                $this->app->session()->getFlashBag()->add('topic_error', lang('quota.daily_topic_exceeded'));
                $this->redirect(core_url('articles/new'));
                return '';
            }
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $prefixId = (int) ($_POST['prefix_id'] ?? 0);
        $articleForum = Forum::find($articleForumId);
        if ($articleForum && $prefixId > 0 && !TopicPrefixScopeService::isPrefixAllowedForForum($articleForum, $prefixId)) {
            $prefixId = 0;
        }

        $cleanBody = trim(strip_tags(str_replace(['&nbsp;', '&#160;', '&zwj;'], '', $body)));
        $hasEmbedOnly = ($cleanBody === '' && (str_contains($body, '<iframe') || str_contains($body, 'mfbb-media-embed') || str_contains($body, 'class="mfbb-media-embed"')));
        if ($title === '' || ($cleanBody === '' && !$hasEmbedOnly)) {
            $this->app->session()->getFlashBag()->add('topic_error', core__('forum.title_body_required'));
            $this->redirect(core_url('articles/new'));
            return '';
        }
        $censorship = $this->app->censorship();
        if ($censorship->isCensorshipEnabled()) {
            if ($censorship->applyToTopicTitles()) {
                $titleCheck = $censorship->checkContent($title);
                if (!$titleCheck['allowed']) {
                    $this->app->session()->getFlashBag()->add('topic_error', lang('censorship.content_blocked'));
                    $this->redirect(core_url('articles/new'));
                    return '';
                }
                $title = $titleCheck['filtered_text'];
            }
            if ($censorship->applyToPosts()) {
                $bodyCheck = $censorship->checkContent($body);
                if (!$bodyCheck['allowed']) {
                    $this->app->session()->getFlashBag()->add('topic_error', lang('censorship.content_blocked'));
                    $this->redirect(core_url('articles/new'));
                    return '';
                }
                $body = $bodyCheck['filtered_text'];
            }
        }
        $maxTitleLen = (int) $this->getSetting('max_topic_title_length', '200');
        if ($maxTitleLen > 0 && mb_strlen($title) > $maxTitleLen) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('article.title_max', ['max' => $maxTitleLen]));
            $this->redirect(core_url('articles/new'));
            return '';
        }
        $maxPostLen = (int) $this->getSetting('max_post_length', '0');
        if ($maxPostLen > 0 && mb_strlen($body) > $maxPostLen) {
            $this->app->session()->getFlashBag()->add('topic_error', lang('article.content_max', ['max' => $maxPostLen]));
            $this->redirect(core_url('articles/new'));
            return '';
        }
        $topicId = null;
        try {
            DB::transaction(function () use ($user, $articleForumId, $title, $body, $prefixId, &$topicId) {
                $sefService = new \App\Services\TopicUrlService();
                $topicSlug = $sefService->generateSlugFromTitle($title, 0);
                if ($topicSlug === '' || $topicSlug === '-0') {
                    $topicSlug = 'article-' . time();
                }
                $topic = Topic::create([
                    'forum_id' => $articleForumId,
                    'user_id' => $user->id,
                    'prefix_id' => $prefixId > 0 ? $prefixId : null,
                    'title' => $title,
                    'slug' => $topicSlug,
                    'type' => 'article',
                    'last_post_at' => \now(),
                ]);
                $topicId = $topic->id;
                $topic->slug = $sefService->generateSlugFromTitle($title, $topicId);
                if ($sefService->getMode() === \App\Services\TopicUrlService::MODE_RANDOM) {
                    $topic->url_key = $sefService->generateUniqueUrlKey();
                }
                $topic->save();
                $bodyHtml = core_body_to_html($body);
                $bodyHtml = $this->processMentions($bodyHtml);
                $bodyHtml = core_process_post_refs($bodyHtml, $topicId);
                $post = Post::create([
                    'topic_id' => $topicId,
                    'user_id' => $user->id,
                    'body' => $body,
                    'body_html' => $bodyHtml,
                    'is_first_post' => true,
                ]);
                $topic->update([
                    'first_post_id' => $post->id,
                    'last_post_id' => $post->id,
                    'last_post_user_id' => $user->id,
                ]);
                Forum::where('id', $articleForumId)->update([
                    'topic_count' => DB::raw('topic_count + 1'),
                    'post_count' => DB::raw('post_count + 1'),
                    'last_post_id' => $post->id,
                    'last_post_user_id' => $user->id,
                ]);
                DB::table('forum_stats')->where('id', 1)->update([
                    'total_topics' => DB::raw('total_topics + 1'),
                    'total_posts' => DB::raw('total_posts + 1'),
                ]);
            });
            $this->app->cache()->delete('forum_stats');
            $this->app->cache()->delete('home_categories');
            $this->redirect(core_url(function_exists('article_url_path_by_id') ? article_url_path_by_id((int) $topicId) : 'article/' . $topicId));
            return '';
        } catch (\RuntimeException $e) {
            $this->app->session()->getFlashBag()->add('topic_error', $e->getMessage());
            $this->redirect(core_url('articles/new'));
            return '';
        } catch (\Throwable $e) {
            error_log('Article creation error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('topic_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('articles/new'));
            return '';
        }
    }

    /** Tek makale: /articles/{categorySlug}/{articleSlug} — canonical URL */
    public function showByCategoryAndSlug(string $categorySlug, string $articleSlug): string
    {
        $category = Category::articleCategories()->where('slug', $categorySlug)->first();
        if (!$category) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $forumIds = Forum::where('category_id', $category->id)->pluck('id')->all();
        if ($forumIds === []) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $topic = $this->resolveArticleBySlugInForums($articleSlug, $forumIds);
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        return $this->renderArticleShow($topic);
    }

    /** Eski /article/{id} — makale kategori içindeyse yeni URL'e yönlendir, değilse göster */
    public function show(string $id): string
    {
        $topicId = resolve_topic_id($id);
        if ($topicId === null) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $user = $this->app->auth()->user();
        $topic = Topic::visibleToUserWithPrivacy($user ? (int)$user->id : null, $user && $user->role && $user->role->is_staff)->with('forum.category')->where('id', $topicId)->whereRaw("COALESCE(type, 'topic') = 'article'")->first();
        if (!$topic) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $category = $topic->forum->category ?? null;
        if ($category !== null && !empty($category->is_article_category) && function_exists('article_url_path_by_id')) {
            $newPath = article_url_path_by_id((int) $topic->id);
            if (strpos($newPath, 'articles/') === 0) {
                header('Location: ' . core_url($newPath), true, 301);
                exit;
            }
        }
        return $this->renderArticleShow($topic);
    }

    private function resolveArticleBySlugInForums(string $articleSlug, array $forumIds): ?Topic
    {
        $user = $this->app->auth()->user();
        $topic = Topic::visibleToUserWithPrivacy($user ? (int)$user->id : null, $user && $user->role && $user->role->is_staff)
            ->whereIn('forum_id', $forumIds)
            ->whereRaw("COALESCE(type, 'topic') = 'article'")
            ->where(function ($q) use ($articleSlug) {
                $q->where('slug', $articleSlug)
                    ->orWhere('url_key', $articleSlug);
            })
            ->first();
        if ($topic) {
            return $topic;
        }
        if (ctype_digit($articleSlug) && (int) $articleSlug > 0) {
            return Topic::visibleToUserWithPrivacy($user ? (int)$user->id : null, $user && $user->role && $user->role->is_staff)
                ->whereIn('forum_id', $forumIds)
                ->whereRaw("COALESCE(type, 'topic') = 'article'")
                ->where('id', (int) $articleSlug)
                ->first();
        }
        return null;
    }

    private function renderArticleShow(Topic $topic): string
    {
        $user = $this->app->auth()->user();
        $currentUserId = $user ? (int) $user->id : null;
        $blocked = $currentUserId ? $this->layoutService()->getBlockedUserIds($currentUserId) : [];
        if (in_array((int) $topic->user_id, $blocked, true)) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        $topic->increment('view_count');
        $topic->view_count = (int) $topic->view_count + 1;
        // Makale sayfasında ilk sayfa için yeterli mesaj (ilk post + yorumlar); engelli kullanıcılar hariç.
        // article tipinde getPosts makale gövdesini (ilk post) misafirlere de gösterir.
        $posts = $this->getPosts((int) $topic->id, $currentUserId, 'article', null, 1, 5000, $blocked);
        $firstPost = $posts[0] ?? null;
        // İlk post listede yoksa (veri tutarsızlığı) first_post_id ile yükle ki makale gövdesi görünsün
        if ($firstPost === null && !empty($topic->first_post_id)) {
            $firstPostOnly = $this->getPosts((int) $topic->id, $currentUserId, 'article', null, 1, 1, []);
            $firstPost = $firstPostOnly[0] ?? null;
        }
        $commentPosts = $firstPost !== null ? array_slice($posts, 1) : $posts;
        $articleCommentsEnabled = $this->getSetting('article_comments_enabled', '1') === '1';
        $reputationEnabled = $this->getSetting('reputation_enabled', '1') === '1';
        $topicError = $this->app->session()->getFlashBag()->get('topic_error');
        $topicError = is_array($topicError) ? ($topicError[0] ?? '') : $topicError;
        $topicOk = $this->app->session()->getFlashBag()->get('topic_ok');
        $topicOk = is_array($topicOk) ? ($topicOk[0] ?? '') : $topicOk;
        $replyError = $this->app->session()->getFlashBag()->get('reply_error');
        $replyError = is_array($replyError) ? ($replyError[0] ?? '') : $replyError;

        return $this->layout('article/show', [
            'topic' => $topic,
            'posts' => $posts,
            'firstPost' => $firstPost,
            'commentPosts' => $commentPosts,
            'pageTitle' => $topic->title,
            'articleCommentsEnabled' => $articleCommentsEnabled,
            'reputationEnabled' => $reputationEnabled,
            'cu' => $user,
            'topicError' => $topicError,
            'topicOk' => $topicOk,
            'error' => $replyError,
        ], false);
    }
}
