<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPermission extends Model
{
    protected $table = 'content_permissions';

    protected $fillable = [
        'role_id',
        'user_id',
        'permission_id',
        'content_type', // Polymorphic Type
        'content_id',   // Polymorphic ID
        'value',
    ];

    protected $casts = [
        'value' => 'boolean',
    ];

    public function permission(): BelongsTo
    {
        return $this->belongsTo(PermissionDefinition::class, 'permission_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
