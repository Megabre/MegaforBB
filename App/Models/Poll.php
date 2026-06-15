<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $table = 'polls';

    public const UPDATED_AT = null;

    protected $fillable = [
        'topic_id',
        'question',
        'max_votes',
        'allow_change_vote',
        'closes_at',
        'created_at',
    ];

    protected $casts = [
        'max_votes' => 'integer',
        'allow_change_vote' => 'boolean',
        'closes_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class, 'poll_id')->orderBy('sort_order')->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'poll_id');
    }

    /** Anket kapanmış mı? */
    public function isClosed(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isPast();
    }
}
