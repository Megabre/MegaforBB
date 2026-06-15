<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    protected $table = 'forums';

    protected $fillable = [
        'category_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'image_url',
        'sort_order',
        'topic_count',
        'post_count',
        'last_post_id',
        'last_post_at',
        'last_post_user_id',
        'forum_type',
        'allow_new_posts',
        'moderate_new_topics',
        'moderate_new_posts',
        'count_user_posts',
        'include_in_new_posts',
        'indexing_mode',
        'min_tags',
        'default_sort_order',
        'topic_date_limit',
        'topic_prompts',
    ];

    protected $casts = [
        'last_post_at' => 'datetime',
    ];

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'forum_id');
    }

    public function posts(): HasMany
    {
        return $this->hasManyThrough(Post::class, Topic::class);
    }

    public function lastPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'last_post_id');
    }

    public function lastPostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_post_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** Alt forumlar (parent_id = bu forumun id). */
    public function subforums(): HasMany
    {
        return $this->hasMany(Forum::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Üst forum (bu forum alt forum ise). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'parent_id');
    }
}
