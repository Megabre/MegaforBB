<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gizli konularda konuyu görebilecek ek kullanıcılar (yazar ve yetkililer her zaman görebilir).
 */
class TopicPrivateViewer extends Model
{
    protected $table = 'topic_private_viewers';

    public $incrementing = false;

    protected $fillable = ['topic_id', 'user_id'];

    protected $casts = [
        'topic_id' => 'integer',
        'user_id' => 'integer',
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
