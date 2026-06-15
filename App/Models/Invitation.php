<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $table = 'invitations';

    protected $fillable = [
        'user_id',
        'code',
        'email',
        'used_at',
        'used_by',
        'expires_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function newUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function isValid(): bool
    {
        if ($this->used_at !== null) {
            return false;
        }
        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }
        return true;
    }
}
