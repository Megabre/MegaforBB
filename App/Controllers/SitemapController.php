<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DocPage;
use App\Models\Forum;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;

/**
 * Generates dynamic sitemap.xml and robots.txt for SEO.
 */
class SitemapController extends BaseController
{
    /**
     * Generates XML sitemap.
     */
    public function sitemap(): string
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex'); // Sitemap'ın kendisi indekslenmesin
        if (!headers_sent()) {
            http_response_code(200);
        }

        $urls = [];
        $baseUrl = rtrim((string) core_config('app.url', ''), '/');
        if ($baseUrl === '') {
            $baseUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }

        try {
            // Homepage (priority 1.0, daily)
            $urls[] = [
                'loc' => $baseUrl . core_url(''),
                'lastmod' => date('c'),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ];

            // All forums (priority 0.8, daily)
            try {
                $forums = Forum::orderBy('sort_order')->orderBy('id')->get(['id', 'slug', 'updated_at']);
                foreach ($forums as $row) {
                    $lastmod = !empty($row->updated_at) ? (is_object($row->updated_at) ? $row->updated_at->format('c') : date('c', strtotime($row->updated_at))) : date('c');
                    $urls[] = [
                        'loc' => $baseUrl . core_url('forum/' . rawurlencode($row->slug ?? '')),
                        'lastmod' => $lastmod,
                        'changefreq' => 'daily',
                        'priority' => '0.8',
                    ];
                }
            } catch (\Throwable $e) {
                // Forums table or columns may not exist
            }

            // All non-deleted topics (priority 0.6, weekly) - exclude articles
            try {
                $types = $this->getTopicListTypes();
                $placeholders = implode(',', array_fill(0, count($types), '?'));
                $topics = Topic::visibleToUserWithPrivacy(null, false)
                    ->whereRaw("COALESCE(type, 'topic') IN ($placeholders)", $types)
                    ->whereNull('deleted_at')
                    ->orderByDesc('last_post_at')
                    ->orderByDesc('id')
                    ->limit(50000)
                    ->get(['id', 'slug', 'updated_at', 'last_post_at']);
                foreach ($topics as $row) {
                    $ts = $row->last_post_at ? $row->last_post_at->getTimestamp() : ($row->updated_at ? $row->updated_at->getTimestamp() : time());
                    $urls[] = [
                        'loc' => $baseUrl . core_url('topic/' . topic_url_path_by_id((int) $row->id)),
                        'lastmod' => date('c', $ts),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ];
                }
            } catch (\Throwable $e) {
                try {
                    $topics = Topic::visibleToUserWithPrivacy(null, false)->whereIn('type', $this->getTopicListTypes())->orderByDesc('last_post_at')->orderByDesc('id')->limit(50000)->get(['id', 'slug', 'updated_at', 'last_post_at']);
                    foreach ($topics as $row) {
                        $ts = $row->last_post_at ? $row->last_post_at->getTimestamp() : ($row->updated_at ? $row->updated_at->getTimestamp() : time());
                        $urls[] = [
                            'loc' => $baseUrl . core_url('topic/' . topic_url_path_by_id((int) $row->id)),
                            'lastmod' => date('c', $ts),
                            'changefreq' => 'weekly',
                            'priority' => '0.6',
                        ];
                    }
                } catch (\Throwable $e2) {
                    // skip
                }
            }

            // Articles (priority 0.7, weekly)
            try {
                $articles = Topic::visibleToUserWithPrivacy(null, false)
                    ->whereRaw("COALESCE(type, 'topic') = 'article'")
                    ->whereNull('deleted_at')
                    ->get(['id']);
                foreach ($articles as $row) {
                    $urls[] = [
                        'loc' => $baseUrl . core_url(function_exists('article_url_path_by_id') ? article_url_path_by_id((int) $row->id) : 'article/' . (int) $row->id),
                        'lastmod' => date('c'),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                }
            } catch (\Throwable $e) {
                try {
                    foreach (Topic::visibleToUserWithPrivacy(null, false)->where('type', 'article')->get(['id']) as $row) {
                        $urls[] = [
                            'loc' => $baseUrl . core_url(function_exists('article_url_path_by_id') ? article_url_path_by_id((int) $row->id) : 'article/' . (int) $row->id),
                            'lastmod' => date('c'),
                            'changefreq' => 'weekly',
                            'priority' => '0.7',
                        ];
                    }
                } catch (\Throwable $e2) {
                    // skip
                }
            }

            // Documentation index + all doc pages (priority 0.65, weekly) — only when documentation is enabled
            if ($this->getSetting('documentation_enabled', '0') === '1') {
                try {
                    $urls[] = [
                        'loc' => $baseUrl . core_url('documentation'),
                        'lastmod' => date('c'),
                        'changefreq' => 'weekly',
                        'priority' => '0.65',
                    ];
                    $docPages = DocPage::with('section')->orderBy('section_id')->orderBy('sort_order')->orderBy('id')->get();
                    foreach ($docPages as $page) {
                        $section = $page->section;
                        $path = $section ? ($section->getPathString() . '/' . $page->slug) : $page->slug;
                        $lastmod = isset($page->updated_at) && $page->updated_at
                            ? (is_object($page->updated_at) ? $page->updated_at->format('c') : date('c', strtotime($page->updated_at)))
                            : date('c');
                        $urls[] = [
                            'loc' => $baseUrl . core_url('documentation/' . $path),
                            'lastmod' => $lastmod,
                            'changefreq' => 'weekly',
                            'priority' => '0.6',
                        ];
                    }
                } catch (\Throwable $e) {
                    // doc_sections / doc_pages may not exist
                }
            }

            // Tag pages: search?tag=slug (priority 0.5, weekly)
            try {
                $tags = Tag::orderBy('use_count', 'desc')->orderBy('name')->get(['slug']);
                foreach ($tags as $row) {
                    if (!empty($row->slug)) {
                        $urls[] = [
                            'loc' => $baseUrl . core_url('search?tag=' . rawurlencode($row->slug)),
                            'lastmod' => date('c'),
                            'changefreq' => 'weekly',
                            'priority' => '0.5',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // tags table may not exist
            }

            // Members list page (priority 0.5, weekly)
            if ($this->getSetting('members_list_enabled', '1') === '1') {
                $urls[] = [
                    'loc' => $baseUrl . core_url('members'),
                    'lastmod' => date('c'),
                    'changefreq' => 'weekly',
                    'priority' => '0.5',
                ];
            }

            // User profiles (priority 0.3, monthly)
            try {
                $users = User::query()->whereNotNull('username')->where('username', '!=', '')->orderBy('id')->limit(10000)->get(['username']);
                foreach ($users as $row) {
                    $urls[] = [
                        'loc' => $baseUrl . core_url('member/' . rawurlencode($row->username ?? '')),
                        'lastmod' => date('c'),
                        'changefreq' => 'monthly',
                        'priority' => '0.3',
                    ];
                }
            } catch (\Throwable $e) {
                // users table may have different structure
            }
        } catch (\Throwable $e) {
            // Minimal sitemap on error
            $urls = [
                ['loc' => $baseUrl . core_url(''), 'lastmod' => date('c'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            $xml .= '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>' . "\n";
            $xml .= '    <priority>' . htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8') . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';

        echo $xml;
        return '';
    }

    /**
     * Generates robots.txt.
     */
    public function robots(): string
    {
        header('Content-Type: text/plain; charset=utf-8');

        $baseUrl = rtrim((string) core_config('app.url', ''), '/');
        if ($baseUrl === '') {
            $baseUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $sitemapUrl = $baseUrl . core_url('sitemap.xml');
        $adminPath = env('ADMIN_PATH', 'admin');

        $customRobots = $this->getSetting('robots_txt_content', '');
        if ($customRobots !== '') {
            $txt = $customRobots;
        } else {
            $txt = "User-agent: *\n";
            $txt .= "Allow: /\n";
            $txt .= "Disallow: /" . $adminPath . "\n";
            $txt .= "Disallow: /login\n";
            $txt .= "Disallow: /register\n";
            $txt .= "Disallow: /profile/\n";
            $txt .= "Disallow: /conversations/\n";
            $txt .= "Disallow: /notifications\n";
            $txt .= "Sitemap: " . $sitemapUrl . "\n";
        }

        echo $txt;
        return '';
    }
}
