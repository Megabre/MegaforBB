<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SidebarWidget extends Model
{
    protected $table = 'sidebar_widgets';

    public $timestamps = false;

    protected $fillable = ['type', 'title', 'content', 'sort_order', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    private const CACHE_KEY = 'sidebar_widgets';
    private const CACHE_TTL = 300;

    /** Aktif widget listesi (cache'li). Admin tarafında create/update/delete/reorder sonrası clearCache çağrılmalı. */
    public static function getCachedList(): array
    {
        try {
            $list = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                return static::where('enabled', 1)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id', 'type', 'title', 'content'])
                    ->all();
            });
            return is_array($list) ? $list : [];
        } catch (\Throwable $e) {
            return static::where('enabled', 1)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'type', 'title', 'content'])
                ->all();
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
