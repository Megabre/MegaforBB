<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdeaCategory extends Model
{
    protected $table = 'idea_categories';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'sort_order',
    ];

    public function ideas(): HasMany
    {
        return $this->hasMany(Idea::class, 'category_id');
    }
}
