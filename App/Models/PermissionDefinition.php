<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionDefinition extends Model
{
    protected $table = 'permission_definitions';

    protected $fillable = [
        'key',
        'group',
        'description',
        'default_value',
    ];

    protected $casts = [
        'default_value' => 'boolean',
    ];
}
