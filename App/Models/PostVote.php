<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostVote extends Model
{
    protected $table = 'post_votes';

    public $timestamps = false; // Missing timestamp columns in DB

    protected $fillable = ['post_id', 'user_id', 'value', 'created_at'];

    protected $casts = [
        'value' => 'integer',
        'created_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
