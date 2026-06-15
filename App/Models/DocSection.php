<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Documentation section (tree: parent/children). Core feature — not a plugin.
 */
class DocSection extends Model
{
    protected $table = 'doc_sections';

    protected $fillable = ['parent_id', 'title', 'slug', 'sort_order'];

    protected $casts = [
        'parent_id' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocSection::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DocSection::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(DocPage::class, 'section_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Slug path from root to this section (for URL). */
    public function getPathSlugs(): array
    {
        $slugs = [];
        $current = $this;
        while ($current) {
            array_unshift($slugs, $current->slug);
            $current = $current->parent;
        }
        return $slugs;
    }

    /** Full path string for URL (e.g. "getting-started/installation"). */
    public function getPathString(): string
    {
        return implode('/', $this->getPathSlugs());
    }

    /** Resolve section by path segments (slug list from root to leaf). */
    public static function resolveByPath(array $slugs): ?self
    {
        if ($slugs === []) {
            return null;
        }
        $parentId = null;
        foreach ($slugs as $slug) {
            $q = static::where('slug', $slug)->where(
                $parentId === null ? fn ($q) => $q->whereNull('parent_id') : fn ($q) => $q->where('parent_id', $parentId)
            );
            $section = $q->first();
            if (!$section) {
                return null;
            }
            $parentId = $section->id;
        }
        return $section ?? null;
    }

    /** Root sections (for building tree). */
    public static function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Slug from title if empty. */
    public static function slugFromTitle(string $title): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', trim($title));
        $slug = trim($slug, '-');
        return $slug === '' ? 'section' : strtolower($slug);
    }

    /** Check slug unique among siblings (same parent). */
    public static function slugExistsForSibling(?int $parentId, string $slug, ?int $excludeId = null): bool
    {
        $q = static::where('slug', $slug);
        if ($parentId === null) {
            $q->whereNull('parent_id');
        } else {
            $q->where('parent_id', $parentId);
        }
        if ($excludeId !== null) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->exists();
    }
}
