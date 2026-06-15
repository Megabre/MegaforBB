<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPermission extends Model
{
    protected $table = 'group_permissions';
    public $timestamps = false; // Only updated when admin changes settings - or use timestamps if schema has created_at

    // Schema has created_at/updated_at
    // But I will check schema again. Yes, migration SQL says created_at/updated_at.

    protected $fillable = [
        'role_id',
        'permission_id',
        'value',
    ];

    protected $casts = [
        'value' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(PermissionDefinition::class, 'permission_id');
    }
}
