<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RssFeedSource extends Model
{
    protected $table = 'rss_feed_sources';

    protected $fillable = [
        'title',
        'url',
        'forum_id',
        'user_id',
        'prefix_id',
        'frequency_minutes',
        'is_active',
        'title_template',
        'body_template',
        'last_fetch_at',
        'last_success_at',
        'last_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_fetch_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(RssFeedImportLog::class, 'rss_feed_source_id');
    }
}
