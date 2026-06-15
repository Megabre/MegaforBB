<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $table = 'posts';

    protected $fillable = [
        'topic_id',
        'user_id',
        'body',
        'body_html',
        'like_count',
        'net_votes',
        'is_first_post',
        'url_key',
        'deleted_by',
        'reply_to_id',
    ];

    protected $casts = [
        'is_first_post' => 'boolean',
        'like_count' => 'integer',
        'net_votes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            $enabled = (string) \App\Models\Setting::getValue('spam_control_enabled', '0') === '1';
            if (!$enabled) {
                return;
            }
            $minLen = (int) \App\Models\Setting::getValue('spam_min_post_length', '15');
            if ($minLen <= 0) {
                return;
            }
            $plain = is_string($post->body) ? trim($post->body) : '';
            if (mb_strlen($plain) < $minLen) {
                throw new \RuntimeException(lang('spam.body_too_short', ['min' => $minLen]));
            }
        });
    }

    public function getDeletedByUsernameAttribute(): string
    {
        return $this->deletedByUser->username ?? '';
    }

    public function getTopicTitleAttribute(): string
    {
        return $this->topic->title ?? '';
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deletedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'post_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PostVote::class, 'post_id');
    }

    public function edits(): HasMany
    {
        return $this->hasMany(PostEdit::class, 'post_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PostReport::class, 'post_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }
}
