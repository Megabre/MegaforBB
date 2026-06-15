<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    protected $table = 'poll_options';

    public $timestamps = false;

    protected $fillable = [
        'poll_id',
        'option_text',
        'vote_count',
        'sort_order',
    ];

    protected $casts = [
        'vote_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class, 'poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'option_id');
    }
}
