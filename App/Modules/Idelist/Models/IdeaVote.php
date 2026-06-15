<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaVote extends Model
{
    protected $table = 'idea_votes';

    public const UPDATED_AT = null;

    protected $fillable = [
        'idea_id',
        'user_id',
        'value',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
