<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    protected $table = 'profile_views';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = [
        'profile_user_id',
        'viewer_user_id',
        'view_count',
        'first_viewed_at',
        'last_viewed_at',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function profileUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_user_id');
    }
}
