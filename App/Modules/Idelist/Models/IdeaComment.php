<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdeaComment extends Model
{
    use SoftDeletes;

    protected $table = 'idea_comments';

    protected $fillable = [
        'idea_id',
        'user_id',
        'body',
        'is_admin_note',
    ];

    protected $casts = [
        'is_admin_note' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
