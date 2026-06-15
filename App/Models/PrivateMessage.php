<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateMessage extends Model
{
    /** Tabloda updated_at yok; sadece created_at kullanılıyor. */
    public const UPDATED_AT = null;

    protected $table = 'private_messages';

    protected $fillable = ['conversation_id', 'user_id', 'body', 'body_html'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
