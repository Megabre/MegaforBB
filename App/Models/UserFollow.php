<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFollow extends Model
{
    protected $table = 'user_follows';

    public $timestamps = true;

    protected $fillable = ['follower_id', 'following_id'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
