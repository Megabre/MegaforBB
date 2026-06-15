<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documentation page under a section. Core feature — not a plugin.
 */
class DocPage extends Model
{
    protected $table = 'doc_pages';

    protected $fillable = ['section_id', 'title', 'slug', 'content', 'sort_order'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(DocSection::class, 'section_id');
    }

    /** Slug from title if empty. */
    public static function slugFromTitle(string $title): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', trim($title));
        $slug = trim($slug, '-');
        return $slug === '' ? 'page' : strtolower($slug);
    }
}
