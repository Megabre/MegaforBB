<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'sort_order',
        'is_article_category',
    ];

    protected $casts = [
        'is_article_category' => 'boolean',
    ];

    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class, 'category_id')->orderBy('sort_order', 'asc');
    }

    /** Sadece üst seviye forumlar (alt forumlar hariç; admin listesi ve sıralama için). */
    public function forumsTopLevel(): HasMany
    {
        return $this->hasMany(Forum::class, 'category_id')->whereNull('parent_id')->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    /** Sadece forum listesinde gösterilecek kategoriler (makale kategorisi değil). */
    public function scopeForForumList($query)
    {
        if (self::hasArticleCategoryColumn($query)) {
            return $query->where('is_article_category', 0);
        }
        return $query;
    }

    /** Sadece makale kategorileri. */
    public function scopeArticleCategories($query)
    {
        if (self::hasArticleCategoryColumn($query)) {
            return $query->where('is_article_category', 1);
        }
        return $query;
    }

    /** Facade kullanmadan sütun varlığını kontrol et. */
    private static function hasArticleCategoryColumn($query): bool
    {
        $table = $query->getModel()->getTable();
        $conn = $query->getModel()->getConnection();
        if (!\in_array($conn->getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }
        $result = $conn->select(
            "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'is_article_category' LIMIT 1",
            [$table]
        );
        return !empty($result);
    }
}
