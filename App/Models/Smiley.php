<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Smiley extends Model
{
    protected $table = 'smileys';

    /** Tabloda updated_at yok, sadece created_at kullanılıyor. */
    public const UPDATED_AT = null;

    protected $fillable = ['code', 'unicode_char', 'image_path', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];
}
