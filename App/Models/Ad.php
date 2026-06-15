<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Ad extends Model
{
    protected $table = 'ads';

    public $timestamps = false;

    protected $fillable = ['position_key', 'name', 'html_content', 'enabled', 'sort_order'];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    private const CACHE_KEY = 'ads_by_position';
    private const CACHE_TTL = 300;

    /** Pozisyona göre reklam HTML'leri: [ position_key => [ html, ... ], ... ]. Cache'li. */
    public static function getCachedByPosition(): array
    {
        try {
            $out = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                $rows = static::where('enabled', 1)
                    ->orderBy('position_key')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['position_key', 'html_content']);
                $result = [];
                foreach ($rows as $row) {
                    $result[$row->position_key][] = $row->html_content ?? '';
                }
                return $result;
            });
            return is_array($out) ? $out : [];
        } catch (\Throwable $e) {
            $rows = static::where('enabled', 1)
                ->orderBy('position_key')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['position_key', 'html_content']);
            $result = [];
            foreach ($rows as $row) {
                $result[$row->position_key][] = $row->html_content ?? '';
            }
            return $result;
        }
    }

    public static function clearCache(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
        }
    }
}
