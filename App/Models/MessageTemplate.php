<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $table = 'message_templates';

    public $timestamps = true;

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
        'subject',
        'body_html',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
