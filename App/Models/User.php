<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasPermissions;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'role_id',
        'locale',
        'avatar_path',
        'cover_photo_path',
        'location',
        'website',
        'bio',
        'signature',
        'first_name',
        'last_name',
        'show_name',
        'birthday',
        'reputation_positive',
        'reputation_negative',
        'is_verified',
        'is_banned',
        'last_activity_at',
        'last_ip',
        'custom_title',
        'warning_points',
        'reward_points',
        'approved_at',
        'email_verified_at',
        'email_verification_token',
        'admin_twofa_question',
        'admin_twofa_answer_hash',
        'available_invites',
        'trust_score',
        'message_count',
        'url_key',
        'is_suspended',
        'suspended_at',
        'closed_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'email_verification_token',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_banned' => 'boolean',
        'is_suspended' => 'boolean',
        'role_id' => 'integer',
        'last_activity_at' => 'datetime',
        'approved_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'user_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user', 'user_id', 'conversation_id')
            ->withPivot('last_read_at');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'user_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_user_id');
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(UserCustomField::class, 'user_id');
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class, 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function topicSubscriptions(): HasMany
    {
        return $this->hasMany(TopicSubscription::class, 'user_id');
    }

    public function reputationsGiven(): HasMany
    {
        return $this->hasMany(UserReputation::class, 'from_user_id');
    }

    public function reputationsReceived(): HasMany
    {
        return $this->hasMany(UserReputation::class, 'to_user_id');
    }

    /** Bu kullanıcının profiline yazılan yorumlar. */
    public function profileComments(): HasMany
    {
        return $this->hasMany(ProfileComment::class, 'user_id');
    }

    /** Bu kullanıcının başka profillere yazdığı yorumlar. */
    public function profileCommentsAuthored(): HasMany
    {
        return $this->hasMany(ProfileComment::class, 'author_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'user_id');
    }

    /** Kayıt olurken kullandığı davetiye (tek). */
    public function usedInvitation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Invitation::class, 'used_by');
    }

    /** Davet eden kullanıcı (usedInvitation->inviter). */
    public function inviterUser(): ?User
    {
        $inv = $this->usedInvitation()->with('inviter')->first();
        return $inv?->inviter;
    }

    /** Bugün doğum günü mü (postbit için). */
    public function getIsBirthdayTodayAttribute(): bool
    {
        $b = $this->getAttribute('birthday');
        if (empty($b)) {
            return false;
        }
        $today = date('m-d');
        if (is_string($b) && strlen($b) >= 10) {
            return (substr($b, 5, 2) . '-' . substr($b, 8, 2)) === $today;
        }
        return false;
    }
}
