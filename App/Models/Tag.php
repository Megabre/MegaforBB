<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'tags';

    public const UPDATED_AT = 'updated_at';
    public const CREATED_AT = 'created_at';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'use_count',
    ];

    protected $casts = [
        'use_count' => 'integer',
    ];
}
