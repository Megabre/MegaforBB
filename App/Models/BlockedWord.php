<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedWord extends Model
{
    protected $table = 'blocked_words';

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = ['word', 'replacement', 'is_regex'];

    protected $casts = [
        'is_regex' => 'boolean',
    ];
}
