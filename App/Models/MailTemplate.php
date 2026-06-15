<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailTemplate extends Model
{
    protected $table = 'mail_templates';

    public $timestamps = true;

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'template_key',
        'name',
        'subject',
        'body_html',
        'body_text',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
