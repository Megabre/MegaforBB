<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    /** Tabloda updated_at sütunu yok; sadece created_at kullanılıyor. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'sender_user_id',
        'sender_username',
        'type',
        'content_type',
        'content_id',
        'action',
        'data',
        'read_at',
        'view_at',
        'auto_read',
        'url_key',
    ];

    protected static function booted(): void
    {
        static::creating(function (Notification $n) {
            if (($n->url_key ?? '') === '' && class_exists(\App\Services\SefUrlService::class)) {
                $svc = new \App\Services\SefUrlService();
                if ($svc->getMode() === \App\Services\SefUrlService::MODE_RANDOM) {
                    $n->url_key = $svc->generateUniqueUrlKeyForTable('notifications', 'url_key');
                }
            }
        });
    }

    protected $casts = [
        'read_at' => 'datetime',
        'view_at' => 'datetime',
        'created_at' => 'datetime',
        'auto_read' => 'boolean',
        'content_id' => 'integer',
        'sender_user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
