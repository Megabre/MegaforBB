<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DocPage;
use App\Models\DocSection;

/**
 * Core documentation (Docusaurus-style). When documentation_enabled is off, no doc data is loaded.
 * Supports path-based URLs: /documentation, /documentation/{path} (path = section-path or section-path/page-slug).
 */
class DocumentationController extends BaseController
{
    public function index(): string
    {
        if ($this->getSetting('documentation_enabled', '0') !== '1') {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        $tree = $this->buildSectionTree();
        $docTitle = $this->getSetting('documentation_title', 'Documentation');

        // Redirect to first available section's first page
        $first = $this->findFirstSectionWithPage($tree);
        if ($first) {
            [$section, $page] = $first;
            $path = $section->getPathString() . '/' . $page->slug;
            $this->redirect(core_url('documentation/' . $path));
            return '';
        }

        $data = [
            'pageTitle' => $docTitle,
            'docTitle' => $docTitle,
            'sectionTree' => $tree,
            'currentSection' => null,
            'currentPage' => null,
            'content' => '',
            'hero_visible' => false,
        ];
        return $this->layout('documentation/index', $data, false);
    }

    /**
     * Path-based: path can be "section", "section/page", "section/subsection", "section/subsection/page", etc.
     */
    public function showByPath(string $path): string
    {
        if ($this->getSetting('documentation_enabled', '0') !== '1') {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        $path = rawurldecode(trim($path, '/'));
        $segments = $path === '' ? [] : explode('/', $path);

        if ($segments === []) {
            return $this->index();
        }

        $docTitle = $this->getSetting('documentation_title', 'Documentation');
        $tree = $this->buildSectionTree();

        // Try: last segment is page slug (section = path[0..n-2], page = path[n-1])
        if (count($segments) >= 2) {
            $sectionSlugs = array_slice($segments, 0, -1);
            $pageSlug = $segments[count($segments) - 1];
            $section = DocSection::resolveByPath($sectionSlugs);
            if ($section) {
                $page = DocPage::where('section_id', $section->id)->where('slug', $pageSlug)->first();
                if ($page) {
                    $data = [
                        'pageTitle' => $page->title . ' - ' . $docTitle,
                        'docTitle' => $docTitle,
                        'sectionTree' => $tree,
                        'currentSection' => $section,
                        'currentPage' => $page,
                        'content' => $page->content,
                        'breadcrumb' => $this->breadcrumbForSection($section, $page),
                        'hero_visible' => false,
                    ];
                    return $this->layout('documentation/show', $data, false);
                }
            }
        }

        // Try: full path is section path -> redirect to first page of that section
        $section = DocSection::resolveByPath($segments);
        if ($section) {
            $first = $section->pages->first();
            if ($first) {
                $this->redirect(core_url('documentation/' . $section->getPathString() . '/' . $first->slug));
                return '';
            }
            // Section has no pages: show section landing (empty or list children)
            $data = [
                'pageTitle' => $section->title . ' - ' . $docTitle,
                'docTitle' => $docTitle,
                'sectionTree' => $tree,
                'currentSection' => $section,
                'currentPage' => null,
                'content' => '',
                'breadcrumb' => $this->breadcrumbForSection($section, null),
                'hero_visible' => false,
            ];
            return $this->layout('documentation/show', $data, false);
        }

        http_response_code(404);
        return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
    }

    /** Build nested tree: [{ section, children: [...], pages: [...] }, ...] */
    private function buildSectionTree(): array
    {
        $all = DocSection::with('pages')->orderBy('sort_order')->orderBy('id')->get();
        $byParent = [];
        foreach ($all as $s) {
            $pid = $s->parent_id;
            if (!isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $s;
        }
        $build = function ($parentId) use (&$build, $byParent) {
            $list = $byParent[$parentId] ?? [];
            $out = [];
            foreach ($list as $s) {
                $out[] = [
                    'section' => $s,
                    'path' => $s->getPathString(),
                    'children' => $build($s->id),
                    'pages' => $s->pages ? $s->pages->all() : [],
                ];
            }
            return $out;
        };
        return $build(null);
    }

    private function findFirstSectionWithPage(array $tree): ?array
    {
        foreach ($tree as $node) {
            $pages = $node['pages'] ?? [];
            if (!empty($pages)) {
                return [$node['section'], $pages[0]];
            }
            $found = $this->findFirstSectionWithPage($node['children'] ?? []);
            if ($found) {
                return $found;
            }
        }
        return null;
    }

    private function breadcrumbForSection(DocSection $section, ?DocPage $page): array
    {
        $items = [];
        $current = $section;
        while ($current) {
            array_unshift($items, ['title' => $current->title, 'path' => $current->getPathString()]);
            $current = $current->parent;
        }
        if ($page) {
            $items[] = ['title' => $page->title, 'path' => $section->getPathString() . '/' . $page->slug];
        }
        return $items;
    }
}
