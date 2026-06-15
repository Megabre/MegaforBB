<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Forum;
use App\Models\Prefix;
use App\Services\TopicPrefixScopeService;
use Forecor\Core\Str;

class AdminForumController extends AdminController
{
    public function index(): string
    {
        $categories = Category::with([
            'forumsTopLevel' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')->with([
                'subforums' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            ]),
        ])->orderBy('sort_order')->get();

        return $this->view('forums/index', [
            'pageTitle' => admin__('menu.forums'),
            'categories' => $categories,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function createCategory(): string
    {
        return $this->view('forums/category_form', [
            'user' => $this->app->auth()->user(),
            'allPrefixes' => Prefix::orderBy('sort_order')->orderBy('id')->get(),
            'categoryPrefixIds' => [],
            'category' => new Category(),
        ]);
    }

    public function storeCategory(): void
    {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $sort_order = (int) ($_POST['sort_order'] ?? 0);
        $isArticleCategory = !empty($_POST['is_article_category']) && $_POST['is_article_category'] === '1';
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'kategori';
        }
        $cat = Category::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'icon' => $_POST['icon'] ?? '',
            'color' => $_POST['color'] ?? '#cccccc',
            'sort_order' => $sort_order,
            'is_article_category' => $isArticleCategory,
        ]);
        $pfx = isset($_POST['category_prefix_ids']) && is_array($_POST['category_prefix_ids'])
            ? array_map('intval', $_POST['category_prefix_ids']) : [];
        TopicPrefixScopeService::syncCategoryPrefixes((int) $cat->id, $pfx);
        $this->app->cache()->delete('home_categories');

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/forums'));
    }

    public function editCategory(string $id): string
    {
        $category = Category::findOrFail((int) $id);
        $allPrefixes = Prefix::orderBy('sort_order')->orderBy('id')->get();

        return $this->view('forums/category_form', [
            'pageTitle' => admin__('common.edit') . ' ' . admin__('menu.categories'),
            'category' => $category,
            'user' => $this->app->auth()->user(),
            'allPrefixes' => $allPrefixes,
            'categoryPrefixIds' => TopicPrefixScopeService::categoryPrefixIds((int) $category->id),
        ]);
    }

    public function updateCategory(string $id): void
    {
        $category = Category::findOrFail((int) $id);

        $name = $_POST['name'] ?? '';
        $category->name = $name;
        $category->description = $_POST['description'] ?? '';
        $category->icon = $_POST['icon'] ?? '';
        $category->color = $_POST['color'] ?? '#cccccc';
        $category->sort_order = (int) ($_POST['sort_order'] ?? 0);
        $category->is_article_category = !empty($_POST['is_article_category']) && $_POST['is_article_category'] === '1';

        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'kategori';
        }
        $pfx = isset($_POST['category_prefix_ids']) && is_array($_POST['category_prefix_ids'])
            ? array_map('intval', $_POST['category_prefix_ids']) : [];
        TopicPrefixScopeService::syncCategoryPrefixes((int) $category->id, $pfx);
        $category->slug = $slug;
        $category->save();
        $this->app->cache()->delete('home_categories');

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/forums'));
    }

    public function deleteCategory(string $id): void
    {
        $category = Category::findOrFail((int) $id);
        TopicPrefixScopeService::syncCategoryPrefixes((int) $category->id, []);
        $category->delete();
        $this->app->cache()->delete('home_categories');
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/forums'));
    }

    // --- Forums ---

    public function createForum(): string
    {
        $categories = Category::orderBy('sort_order')->get();
        $forums = Forum::orderBy('name')->get();
        $data = [
            'pageTitle' => admin__('common.add') . ' ' . admin__('menu.forums'),
            'forum' => new Forum(),
            'categories' => $categories,
            'forums' => $forums,
            'user' => $this->app->auth()->user(),
            'allPrefixes' => Prefix::orderBy('sort_order')->orderBy('id')->get(),
            'forumPrefixIds' => [],
        ];
        $data['admin_forum_form_extra'] = $this->app->hooks()->doAction('admin.forum.form_extra', null);

        return $this->view('forums/forum_form', $data);
    }

    public function storeForum(): void
    {
        $name = $_POST['name'] ?? '';
        $baseSlug = Str::slug($name);
        $slug = $this->ensureUniqueForumSlug($baseSlug, null);

        $forum = Forum::create([
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'parent_id' => !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null,
            'name' => $name,
            'slug' => $slug,
            'description' => $_POST['description'] ?? '',
            'icon' => $_POST['icon'] ?? '',
            'image_url' => $_POST['image_url'] ?? '',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'forum_type' => $_POST['forum_type'] ?? 'discussion',
            'default_sort_order' => $_POST['default_sort_order'] ?? 'last_post_desc',
            'topic_date_limit' => (int) ($_POST['topic_date_limit'] ?? 0),
            'indexing_mode' => (int) ($_POST['indexing_mode'] ?? 1),
            'min_tags' => (int) ($_POST['min_tags'] ?? 0),
            'allow_new_posts' => isset($_POST['allow_new_posts']) ? 1 : 0,
            'moderate_new_topics' => isset($_POST['moderate_new_topics']) ? 1 : 0,
            'moderate_new_posts' => isset($_POST['moderate_new_posts']) ? 1 : 0,
            'count_user_posts' => isset($_POST['count_user_posts']) ? 1 : 0,
            'include_in_new_posts' => isset($_POST['include_in_new_posts']) ? 1 : 0,
        ]);
        $pfx = isset($_POST['forum_prefix_ids']) && is_array($_POST['forum_prefix_ids'])
            ? array_map('intval', $_POST['forum_prefix_ids']) : [];
        TopicPrefixScopeService::syncForumPrefixes((int) $forum->id, $pfx);
        $this->app->hooks()->doAction('admin.forum.saved', $forum);
        $this->app->cache()->delete('home_categories');

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/forums'));
    }

    public function editForum(string $id): string
    {
        $forum = Forum::findOrFail((int) $id);
        $categories = Category::orderBy('sort_order')->get();
        $forums = Forum::orderBy('name')->get();
        $data = [
            'pageTitle' => admin__('common.edit') . ' ' . admin__('menu.forums'),
            'forum' => $forum,
            'categories' => $categories,
            'forums' => $forums,
            'user' => $this->app->auth()->user(),
            'allPrefixes' => Prefix::orderBy('sort_order')->orderBy('id')->get(),
            'forumPrefixIds' => TopicPrefixScopeService::forumPrefixIds((int) $forum->id),
        ];
        $data['admin_forum_form_extra'] = $this->app->hooks()->doAction('admin.forum.form_extra', $forum);

        return $this->view('forums/forum_form', $data);
    }

    public function updateForum(string $id): void
    {
        $forum = Forum::findOrFail((int) $id);

        $name = $_POST['name'] ?? '';
        $forum->name = $name;
        $forum->category_id = (int) ($_POST['category_id'] ?? 0);
        $forum->parent_id = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
        $forum->description = $_POST['description'] ?? '';
        $forum->icon = $_POST['icon'] ?? '';
        $forum->image_url = $_POST['image_url'] ?? '';
        $forum->sort_order = (int) ($_POST['sort_order'] ?? 0);

        $forum->forum_type = $_POST['forum_type'] ?? 'discussion';
        $forum->default_sort_order = $_POST['default_sort_order'] ?? 'last_post_desc';
        $forum->topic_date_limit = (int) ($_POST['topic_date_limit'] ?? 0);
        $forum->indexing_mode = (int) ($_POST['indexing_mode'] ?? 1);
        $forum->min_tags = (int) ($_POST['min_tags'] ?? 0);
        $forum->allow_new_posts = isset($_POST['allow_new_posts']) ? 1 : 0;
        $forum->moderate_new_topics = isset($_POST['moderate_new_topics']) ? 1 : 0;
        $forum->moderate_new_posts = isset($_POST['moderate_new_posts']) ? 1 : 0;
        $forum->count_user_posts = isset($_POST['count_user_posts']) ? 1 : 0;
        $forum->include_in_new_posts = isset($_POST['include_in_new_posts']) ? 1 : 0;

        $baseSlug = Str::slug($name);
        $pfx = isset($_POST['forum_prefix_ids']) && is_array($_POST['forum_prefix_ids'])
            ? array_map('intval', $_POST['forum_prefix_ids']) : [];
        TopicPrefixScopeService::syncForumPrefixes((int) $forum->id, $pfx);
        $forum->slug = $this->ensureUniqueForumSlug($baseSlug, $forum->id);
        $forum->save();
        $this->app->hooks()->doAction('admin.forum.saved', $forum);
        $this->app->cache()->delete('home_categories');

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/forums'));
    }

    public function deleteForum(string $id): void
    {
        $forum = Forum::findOrFail((int) $id);
        TopicPrefixScopeService::syncForumPrefixes((int) $forum->id, []);
        $forum->delete();
        $this->app->cache()->delete('home_categories');
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/forums'));
    }

    public function reorder(): void
    {
        $input = null;
        if (isset($_POST['payload'])) {
            $decoded = json_decode((string) $_POST['payload'], true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }
        if (!is_array($input)) {
            $decoded = json_decode(file_get_contents('php://input'), true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        $token = (string) ($_POST['_token'] ?? ($input['_token'] ?? ''));
        if (!is_array($input) || !core_csrf_valid('csrf', $token)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false]);
            exit;
        }

        if (isset($input['categories']) && is_array($input['categories'])) {
            foreach ($input['categories'] as $index => $catId) {
                $c = Category::find((int) $catId);
                if ($c) {
                    $c->sort_order = $index;
                    $c->save();
                }
            }
        }

        if (isset($input['forums']) && is_array($input['forums'])) {
            foreach ($input['forums'] as $fData) {
                $f = Forum::find((int) ($fData['id'] ?? 0));
                if ($f) {
                    if (isset($fData['sort_order'])) {
                        $f->sort_order = (int) $fData['sort_order'];
                    }
                    if (isset($fData['category_id'])) {
                        $f->category_id = (int) $fData['category_id'];
                    }
                    if (array_key_exists('parent_id', $fData)) {
                        $f->parent_id = $fData['parent_id'] === null || $fData['parent_id'] === '' ? null : (int) $fData['parent_id'];
                    }
                    $f->save();
                }
            }
        }

        $this->app->cache()->delete('home_categories');
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Verilen slug forums tablosunda benzersiz değilse -1, -2, ... ekleyerek benzersiz slug döndürür.
     * Güncellemede mevcut forum id hariç tutulur (excludeForumId).
     */
    private function ensureUniqueForumSlug(string $baseSlug, ?int $excludeForumId): string
    {
        $slug = $baseSlug ?: 'forum';
        $query = Forum::where('slug', $slug);
        if ($excludeForumId !== null) {
            $query->where('id', '!=', $excludeForumId);
        }
        if (!$query->exists()) {
            return $slug;
        }
        $suffix = 1;
        do {
            $candidate = $slug . '-' . $suffix;
            $q = Forum::where('slug', $candidate);
            if ($excludeForumId !== null) {
                $q->where('id', '!=', $excludeForumId);
            }
            if (!$q->exists()) {
                return $candidate;
            }
            ++$suffix;
        } while (true);
    }
}
