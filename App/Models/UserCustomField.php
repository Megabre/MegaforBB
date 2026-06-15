<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCustomField extends Model
{
    protected $table = 'user_custom_fields';

    public $timestamps = false;

    protected $fillable = ['user_id', 'field_key', 'field_value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
