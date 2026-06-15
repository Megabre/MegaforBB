<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RssFeedImportLog extends Model
{
    protected $table = 'rss_feed_import_logs';

    public $timestamps = false;

    protected $fillable = [
        'rss_feed_source_id',
        'unique_entry_id',
        'topic_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(RssFeedSource::class, 'rss_feed_source_id');
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }
}
