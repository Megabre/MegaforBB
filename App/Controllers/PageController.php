<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Page;

/**
 * Statik sayfalar (Kurallar vb.)
 */
class PageController extends BaseController
{
    public function show(string $slug): string
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->first(['id', 'slug', 'title', 'body']);
        if (!$page) {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }
        return $this->layout('page', [
            'page' => $page,
            'pageTitle' => $page->title,
        ], false);
    }
}
