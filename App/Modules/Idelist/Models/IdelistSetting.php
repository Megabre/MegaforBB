<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Models;

use Illuminate\Database\Eloquent\Model;

class IdelistSetting extends Model
{
    protected $table = 'idelist_settings';

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, string $default = ''): string
    {
        try {
            if (!self::tableExists()) {
                return $default;
            }
            $row = static::query()->where('key', $key)->first(['value']);
            return $row ? (string) ($row->value ?? $default) : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * @param list<string> $keys
     * @return array<string,string>
     */
    public static function getMany(array $keys, string $default = ''): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $default;
        }
        try {
            if (!self::tableExists() || $keys === []) {
                return $result;
            }
            $rows = static::query()->whereIn('key', $keys)->get(['key', 'value']);
            foreach ($rows as $row) {
                $key = (string) ($row->key ?? '');
                if ($key === '') {
                    continue;
                }
                $result[$key] = (string) ($row->value ?? $default);
            }
        } catch (\Throwable $e) {
            // Sessiz fallback; migration sürecinde hata yükseltmeyiz.
        }

        return $result;
    }

    public static function setValue(string $key, string $value): void
    {
        try {
            if (!self::tableExists()) {
                return;
            }
            $row = static::query()->where('key', $key)->first();
            if ($row) {
                $row->value = $value;
                $row->save();
                return;
            }

            static::query()->create(['key' => $key, 'value' => $value]);
        } catch (\Throwable $e) {
            // Migration öncesi/DB geçiş anında sessiz fallback.
        }
    }

    /**
     * @param array<string,string> $pairs
     */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            self::setValue((string) $key, (string) $value);
        }
    }

    public static function isEnabled(bool $default = true): bool
    {
        $fallback = $default ? '1' : '0';

        return self::getValue('module_enabled', $fallback) === '1';
    }

    private static function tableExists(): bool
    {
        try {
            $conn = (new static())->getConnection();
            $schema = $conn->getSchemaBuilder();
            return $schema->hasTable((new static())->getTable());
        } catch (\Throwable $e) {
            return false;
        }
    }
}
