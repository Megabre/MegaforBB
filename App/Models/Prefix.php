<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prefix extends Model
{
    protected $table = 'topic_prefixes';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'css_class',
        'icon_class',
        'badge_bg',
        'badge_text',
        'sort_order',
        'category_id',
    ];

    protected $casts = [
        'category_id' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
