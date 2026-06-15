<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Forum;
use App\Services\SearchService;

/**
 * Search controller: categorized results with filters. Paginated HTML + JSON API.
 */
class SearchController extends BaseController
{
    public function index(): string
    {
        $data = $this->runSearch(false);
        $forums = Forum::orderBy('name', 'asc')->get(['id', 'name', 'slug']);

        $queryParams = array_filter([
            'q' => $data['query'],
            'tag' => $data['tag'],
            'type' => $data['type'],
            'forum_id' => isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : (isset($_GET['forum']) ? (int)$_GET['forum'] : null),
            'author' => trim((string)($_GET['author'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
        ], fn ($v) => $v !== '' && $v !== null && $v !== 0);

        $forumId = isset($queryParams['forum_id']) ? (int)$queryParams['forum_id'] : null;
        if ($forumId === 0) {
            $forumId = null;
        }

        $paginationBase = core_url('search') . (empty($queryParams) ? '?' : '?' . http_build_query($queryParams) . '&');

        $performSearch = $data['performSearch'];
        $query = $data['query'];
        $tag = $data['tag'];

        $pageTitle = $performSearch
            ? ($tag !== '' ? (lang('search.tag_label') . htmlspecialchars($tag)) : (lang('search.query_label') . htmlspecialchars($query)))
            : lang('search.page_title_default');

        return $this->layout('search', [
            'query' => $query,
            'tag' => $tag,
            'type' => $data['type'],
            'forumId' => $forumId,
            'author' => trim((string)($_GET['author'] ?? '')),
            'dateFrom' => trim((string)($_GET['date_from'] ?? '')),
            'dateTo' => trim((string)($_GET['date_to'] ?? '')),
            'results' => $data['results'],
            'totals' => $data['totals'],
            'forums' => $forums,
            'pageTitle' => $pageTitle,
            'totalResults' => $data['totalResults'],
            'page' => $data['page'],
            'totalPages' => $data['totalPages'],
            'queryParams' => $queryParams,
            'paginationBase' => $paginationBase,
            'searchCategories' => $this->buildCategoryMeta($data['totals']),
        ], false);
    }

    /** JSON endpoint for live AJAX search. */
    public function api(): string
    {
        $data = $this->runSearch(true);
        $this->json([
            'ok' => true,
            'query' => $data['query'],
            'tag' => $data['tag'],
            'type' => $data['type'],
            'results' => $data['results'],
            'totals' => $data['totals'],
            'totalResults' => $data['totalResults'],
            'page' => $data['page'],
            'totalPages' => $data['totalPages'],
            'categories' => $this->buildCategoryMeta($data['totals']),
        ]);
        return '';
    }

    /** @return array<string, mixed> */
    private function runSearch(bool $live): array
    {
        $forumId = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : (isset($_GET['forum']) ? (int)$_GET['forum'] : null);
        if ($forumId === 0) {
            $forumId = null;
        }

        $service = new SearchService([
            'documentation_enabled' => $this->getSetting('documentation_enabled', '0') === '1',
            'idelist_enabled' => $this->isIdelistEnabled(),
        ]);

        return $service->search([
            'q' => trim((string)($_GET['q'] ?? '')),
            'tag' => trim((string)($_GET['tag'] ?? '')),
            'type' => (string)($_GET['type'] ?? 'all'),
            'forum_id' => $forumId,
            'author' => trim((string)($_GET['author'] ?? '')),
            'date_from' => trim((string)($_GET['date_from'] ?? '')),
            'date_to' => trim((string)($_GET['date_to'] ?? '')),
            'page' => max(1, (int)($_GET['page'] ?? 1)),
            'live' => $live,
        ]);
    }

    private function isIdelistEnabled(): bool
    {
        $cached = $this->app->cache()->get('idelist.enabled');
        if ($cached !== null) {
            return (bool)$cached;
        }
        try {
            return \App\Modules\Idelist\Models\IdelistSetting::getValue('module_enabled', '1') === '1';
        } catch (\Throwable $e) {
            return true;
        }
    }

    /**
     * @param array<string, int> $totals
     * @return list<array<string, mixed>>
     */
    private function buildCategoryMeta(array $totals): array
    {
        $categories = [
            ['key' => 'all', 'icon' => 'fa-layer-group', 'label' => lang('search.type_all')],
            ['key' => 'forums', 'icon' => 'fa-folder-tree', 'label' => lang('search.type_forums')],
            ['key' => 'topics', 'icon' => 'fa-comments', 'label' => lang('search.type_topics')],
            ['key' => 'articles', 'icon' => 'fa-newspaper', 'label' => lang('search.type_articles')],
            ['key' => 'posts', 'icon' => 'fa-message', 'label' => lang('search.type_posts')],
            ['key' => 'users', 'icon' => 'fa-user-group', 'label' => lang('search.type_users')],
        ];

        if ($this->getSetting('documentation_enabled', '0') === '1') {
            $categories[] = ['key' => 'docs', 'icon' => 'fa-book', 'label' => lang('search.type_docs')];
        }
        if ($this->isIdelistEnabled()) {
            $categories[] = ['key' => 'ideas', 'icon' => 'fa-lightbulb', 'label' => lang('search.type_ideas')];
        }

        foreach ($categories as &$cat) {
            $key = $cat['key'];
            $cat['count'] = $key === 'all' ? array_sum($totals) : ($totals[$key] ?? 0);
        }
        unset($cat);

        return $categories;
    }
}
