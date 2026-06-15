<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $table = 'contact_messages';

    // We disable Eloquent's default timestamps because we only use created_at
    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'ip_address',
        'is_read',
        'created_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];
}
