<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageLine extends Model
{
    protected $table = 'language_lines';

    protected $fillable = [
        'locale',
        'group',
        'key',
        'value',
    ];

    public static function getTranslationsForLocale(string $locale): array
    {
        return static::where('locale', $locale)
            ->get(['group', 'key', 'value'])
            ->mapWithKeys(function ($line) {
                $fullKey = $line->group . '.' . $line->key;
                return [$fullKey => $line->value];
            })
            ->toArray();
    }
}
