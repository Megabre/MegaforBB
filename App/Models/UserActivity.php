<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    protected $table = 'user_activities';

    // We disable Eloquent's default timestamps because we only use created_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action_type',
        'item_id',
        'details',
        'created_at'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'item_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
