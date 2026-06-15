<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'languages';

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_active',
        'is_default',
        'direction',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
    ];

    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LanguageLine::class, 'locale', 'code');
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public static function getActiveLocales(): array
    {
        return static::where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name', 'native_name', 'direction'])
            ->toArray();
    }
}
