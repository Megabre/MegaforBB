<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileComment extends Model
{
    protected $table = 'profile_comments';

    protected $fillable = ['user_id', 'author_id', 'body', 'body_html', 'created_at'];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /** Profil sahibi (yorumun yazıldığı kullanıcı). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Yorumu yazan kullanıcı. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
