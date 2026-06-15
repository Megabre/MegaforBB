<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'body',
        'badge_type',
        'display_location',
        'send_as_notification',
        'is_dismissible',
        'is_active',
        'show_from',
        'show_until',
        'sort_order',
    ];

    protected $casts = [
        'send_as_notification' => 'boolean',
        'is_dismissible' => 'boolean',
        'is_active' => 'boolean',
        'show_from' => 'datetime',
        'show_until' => 'datetime',
        'sort_order' => 'integer',
    ];

    private const CACHE_KEY = 'active_announcements';
    private const CACHE_TTL = 300;

    public function dismissals()
    {
        return $this->hasMany(AnnouncementDismissal::class, 'announcement_id');
    }

    /** Aktif duyurular (tarih aralığına göre); cache'li. Kullanıcıya özel dismiss bilgisi cache dışında. */
    public static function getCachedActive(): array
    {
        try {
            $list = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                $now = \now()->format('Y-m-d H:i:s');
                return static::where('is_active', 1)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('show_from')->orWhere('show_from', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('show_until')->orWhere('show_until', '>=', $now);
                    })
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get(['id', 'title', 'body', 'badge_type', 'display_location', 'is_dismissible'])
                    ->all();
            });
            return is_array($list) ? $list : [];
        } catch (\Throwable $e) {
            $now = \now()->format('Y-m-d H:i:s');
            return static::where('is_active', 1)
                ->where(function ($q) use ($now) {
                    $q->whereNull('show_from')->orWhere('show_from', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('show_until')->orWhere('show_until', '>=', $now);
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'title', 'body', 'badge_type', 'display_location', 'is_dismissible'])
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
