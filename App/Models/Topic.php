<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use SoftDeletes;

    protected $table = 'topics';

    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'forum_id',
        'user_id',
        'prefix_id',
        'title',
        'slug',
        'url_key',
        'type',
        'is_sticky',
        'is_locked',
        'is_solved',
        'is_private',
        'moved_to_topic_id',
        'reply_count',
        'view_count',
        'first_post_id',
        'last_post_id',
        'last_post_at',
        'last_post_user_id',
        'scheduled_publish_at',
        'status',
        'deleted_by',
    ];

    protected $casts = [
        'is_sticky' => 'boolean',
        'is_locked' => 'boolean',
        'is_solved' => 'boolean',
        'is_private' => 'boolean',
        'last_post_at' => 'datetime',
        'scheduled_publish_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /** Sadece yayınlanmış (ve eski veride status olmayan) konuları listele; planlanmış/iptal hariç. */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('status')->orWhere('status', self::STATUS_PUBLISHED);
        });
    }

    /** Gizlilik dahil, kullanıcıya görünür konular (private + scheduled). */
    public function scopeVisibleToUserWithPrivacy($q, ?int $uid, bool $isStaff = false)
    {
        if ($isStaff) {
            return $q->where('status', self::STATUS_PUBLISHED);
        }
        return $q->where(function ($x) use ($uid) {
            $x->where('is_private', 0) // Herkese açık konular
              ->orWhere('user_id', $uid) // Konu sahibi
              ->orWhereExists(function ($s) use ($uid) {
                  // Özel davetli izleyiciler
                  $s->selectRaw(1)->from('topic_private_viewers')
                    ->whereColumn('topic_private_viewers.topic_id', 'topics.id')
                    ->where('topic_private_viewers.user_id', $uid ?? 0);
              });
        })->where('status', self::STATUS_PUBLISHED); // Sadece yayında olanlar
    }

    public function scopeVisibleToUser($query, ?int $userId, bool $isStaff = false)
    {
        if ($userId === null || $userId === 0) {
            return $query->visibleToUserWithPrivacy($userId, $isStaff);
        }
        return $query->where(function ($q) use ($userId, $isStaff) {
            $q->visibleToUserWithPrivacy($userId, $isStaff)
                ->orWhere(function ($q2) use ($userId) {
                    $q2->where('status', self::STATUS_SCHEDULED)->where('user_id', $userId);
                });
        });
    }

    public function getDeletedByUsernameAttribute(): string
    {
        return $this->deletedByUser->username ?? '';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'topic_id');
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function lastPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'last_post_id');
    }

    public function prefix(): BelongsTo
    {
        return $this->belongsTo(Prefix::class, 'prefix_id');
    }

    public function lastPostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_post_user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TopicSubscription::class, 'topic_id');
    }

    public function topicReads(): HasMany
    {
        return $this->hasMany(TopicRead::class, 'topic_id');
    }

    public function poll(): HasOne
    {
        return $this->hasOne(Poll::class, 'topic_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'topic_tags', 'topic_id', 'tag_id');
    }

    /** Gizli konuda konuyu görebilecek ek kullanıcılar. */
    public function privateViewers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'topic_private_viewers', 'topic_id', 'user_id');
    }
}
