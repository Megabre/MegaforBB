<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Ayar key-value yönetimi. Tüm okuma/yazma bu model üzerinden yapılır;
 * ileride Redis/cache kullanımı için sadece getValue/setValue içine cache katmanı eklemen yeterli.
 */
class Setting extends Model
{
    protected $table = 'settings';

    public $timestamps = false;

    protected $fillable = ['key', 'value', 'group'];

    private const CACHE_PREFIX = 'setting:';
    private const CACHE_TTL_SECONDS = 300;

    /**
     * Key ile değer oku (doğrudan DB).
     */
    public static function getValue(string $key, $default = null)
    {
        $row = static::where('key', $key)->first(['value']);
        return $row !== null ? $row->value : $default;
    }

    /**
     * Key ile değer oku; cache varsa önce cache'e bak (Redis/file vs. config'e göre).
     * Cache invalidation: setValue çağrıldığında ilgili key silinir.
     */
    public static function getCached(string $key, $default = null, ?int $ttlSeconds = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $ttl = $ttlSeconds ?? self::CACHE_TTL_SECONDS;
        try {
            return Cache::remember($cacheKey, $ttl, fn () => self::getValue($key, $default));
        } catch (\Throwable $e) {
            return self::getValue($key, $default);
        }
    }

    /**
     * Key ile var mı kontrol et.
     */
    public static function hasKey(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Key ile değer yaz (yoksa ekle, varsa güncelle). Yazma sonrası cache key invalidate edilir.
     */
    public static function setValue(string $key, string $value, string $group = 'forum'): void
    {
        $exists = static::where('key', $key)->exists();
        if ($exists) {
            static::where('key', $key)->update(['value' => $value]);
        } else {
            static::create(['key' => $key, 'value' => $value, 'group' => $group]);
        }
        try {
            Cache::forget(self::CACHE_PREFIX . $key);
        } catch (\Throwable $e) {
        }
    }
}
