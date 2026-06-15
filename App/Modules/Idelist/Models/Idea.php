<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Idea extends Model
{
    use SoftDeletes;

    protected $table = 'ideas';

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'status',
        'completion_note',
        'completion_url',
        'vote_count',
        'views_count',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'vote_count' => 'integer',
        'views_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::observe(\App\Modules\Idelist\Observers\IdeaObserver::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IdeaCategory::class, 'category_id');
    }

    public function statusDefinition(): BelongsTo
    {
        return $this->belongsTo(IdeaStatusDefinition::class, 'status', 'slug');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(IdeaVote::class, 'idea_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IdeaComment::class, 'idea_id');
    }

    public function lastComment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(IdeaComment::class, 'idea_id')->latestOfMany();
    }
}
