<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserFieldDefinition extends Model
{
    protected $table = 'user_field_definitions';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'field_key',
        'field_type',
        'field_options',
        'is_required',
        'show_on_registration',
        'show_on_profile',
        'show_in_postbit',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'show_on_registration' => 'boolean',
        'show_on_profile' => 'boolean',
        'show_in_postbit' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(UserCustomField::class, 'field_key', 'field_key');
    }
}
