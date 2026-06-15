<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $table = 'conversations';

    public $timestamps = false;

    protected $fillable = ['created_at', 'url_key'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user', 'conversation_id', 'user_id')
            ->withPivot('last_read_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PrivateMessage::class, 'conversation_id');
    }
}
