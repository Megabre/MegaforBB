<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedUsername extends Model
{
    protected $table = 'blocked_usernames';

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = ['pattern', 'is_regex'];

    protected $casts = [
        'is_regex' => 'boolean',
    ];
}
