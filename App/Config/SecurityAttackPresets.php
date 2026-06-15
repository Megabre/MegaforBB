<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Saldırı modu açıkken uygulanacak agresif güvenlik değerleri.
 * Tüm kurallar sıkılaştırılır: rate limit düşer, cooldown/engel süreleri artar, captcha zorunlu.
 */
class SecurityAttackPresets
{
    /** Saldırı modunda override edilecek ayar anahtarları => değerler (string). */
    private const PRESETS = [
        'security_enabled' => '1',
        'security_global_rate_enabled' => '1',
        'security_global_rate_per_minute' => '60',
        'security_global_rate_block_minutes' => '15',
        'security_headers_enabled' => '1',
        'security_hsts_enabled' => '1',
        'security_suspicious_blocks_threshold' => '2',
        'security_suspicious_block_minutes' => '4320', // 72 saat
        'security_cooldown_reply' => '120',
        'security_cooldown_new_topic' => '60',
        'security_cooldown_edit_post' => '30',
        'security_cooldown_edit_topic' => '30',
        'security_cooldown_login' => '60',
        'security_cooldown_register' => '120',
        'security_cooldown_send_pm' => '60',
        'security_cooldown_report' => '120',
        'security_cooldown_like' => '15',
        'security_violations_before_block' => '3',
        'security_violation_window_minutes' => '3',
        'security_block_duration_minutes' => '60',
        'captcha_on_login' => '1',
        'captcha_on_register' => '1',
    ];

    /** Belirtilen anahtar saldırı modu preset'inde var mı? */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::PRESETS);
    }

    /** Preset değerini döndürür; yoksa null. */
    public static function get(string $key): ?string
    {
        $v = self::PRESETS[$key] ?? null;
        return $v === null ? null : (string) $v;
    }
}
