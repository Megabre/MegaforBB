<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tek satırlık forum özet istatistikleri (total_topics, total_posts, total_members).
 */
class ForumStats extends Model
{
    protected $table = 'forum_stats';

    protected $primaryKey = 'id';

    public $timestamps = true;

    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'total_topics',
        'total_posts',
        'total_members',
        'last_member_id',
        'last_member_username',
        'record_online_users',
        'record_online_date',
    ];

    protected $casts = [
        'total_topics' => 'integer',
        'total_posts' => 'integer',
        'total_members' => 'integer',
        'last_member_id' => 'integer',
        'record_online_users' => 'integer',
        'updated_at' => 'datetime',
        'record_online_date' => 'datetime',
    ];

    /** Varsayılan tek kayıt (id=1). */
    public static function singleton(): ?self
    {
        return static::find(1);
    }
}
