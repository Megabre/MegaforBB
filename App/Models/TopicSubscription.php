<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicSubscription extends Model
{
    protected $table = 'topic_subscriptions';

    protected $fillable = ['topic_id', 'user_id', 'created_at'];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
