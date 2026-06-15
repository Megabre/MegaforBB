<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\Topic;

/**
 * SEF (Search Engine Friendly) konu URL'leri için mod ve çözümleme.
 * Ayarlar: sef_topic_url_mode = 'id' | 'slug' | 'random'
 */
class TopicUrlService
{
    public const MODE_ID = 'id';
    public const MODE_SLUG = 'slug';
    public const MODE_RANDOM = 'random';

    private const URL_KEY_LENGTH = 24;
    private const URL_KEY_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /** Tüm sistem tek SEF modu (sef_url_mode); yoksa eski sef_topic_url_mode. */
    public function getMode(): string
    {
        $mode = (string) Setting::getValue('sef_url_mode', Setting::getValue('sef_topic_url_mode', self::MODE_ID));
        return in_array($mode, [self::MODE_ID, self::MODE_SLUG, self::MODE_RANDOM], true) ? $mode : self::MODE_ID;
    }

    /**
     * Topic id ile URL segment'i döndürür (topic yoksa id döner).
     */
    public function pathForTopicId(int $topicId): string
    {
        $topic = Topic::find($topicId);
        return $topic !== null ? $this->pathForTopic($topic) : (string) $topicId;
    }

    /**
     * Konu için URL segment'ini döndürür (topic/{segment}).
     * @param Topic|object $topic id, slug, url_key içeren model veya stdClass
     */
    public function pathForTopic($topic): string
    {
        $id = (int) ($topic->id ?? 0);
        $slug = $topic->slug ?? '';
        $urlKey = $topic->url_key ?? null;

        switch ($this->getMode()) {
            case self::MODE_SLUG:
                return $slug !== '' ? $slug : (string) $id;
            case self::MODE_RANDOM:
                if ($urlKey !== null && $urlKey !== '') {
                    return $urlKey;
                }
                return (string) $id;
            default:
                return (string) $id;
        }
    }

    /**
     * URL segment'inden (identifier) topic id çözümler.
     * Önce sayı mı diye bakar (id modu veya geriye uyumluluk), sonra slug, sonra url_key.
     */
    public function resolveTopicId(string $identifier): ?int
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        if (ctype_digit($identifier)) {
            $id = (int) $identifier;
            return $id > 0 ? $id : null;
        }
        $topic = Topic::query()
            ->where(function ($q) use ($identifier) {
                $q->where('slug', $identifier)->orWhere('url_key', $identifier);
            })
            ->first(['id']);
        return $topic !== null ? (int) $topic->id : null;
    }

    /**
     * Yeni konu için slug üretir (başlıktan, Türkçe uyumlu). Format: slug-id (benzersizlik için).
     */
    public function generateSlugFromTitle(string $title, int $topicId): string
    {
        $base = \Forecor\Core\Str::slug($title);
        if ($base === '') {
            $base = 'topic';
        }
        return $base . '-' . $topicId;
    }

    /**
     * Benzersiz 24 karakterlik url_key üretir (a-z0-9).
     */
    public function generateUrlKey(): string
    {
        $max = strlen(self::URL_KEY_CHARS) - 1;
        $key = '';
        for ($i = 0; $i < self::URL_KEY_LENGTH; $i++) {
            $key .= self::URL_KEY_CHARS[random_int(0, $max)];
        }
        return $key;
    }

    /**
     * Veritabanında olmayan benzersiz url_key döndürür.
     */
    public function generateUniqueUrlKey(): string
    {
        $attempts = 0;
        do {
            $key = $this->generateUrlKey();
            $exists = Topic::where('url_key', $key)->exists();
            if (!$exists) {
                return $key;
            }
            $attempts++;
        } while ($attempts < 20);
        return $this->generateUrlKey() . bin2hex(random_bytes(2));
    }

    /** Tablo + sütun için benzersiz url_key (diğer entity'ler için). */
    public function generateUniqueUrlKeyForTable(string $table, string $column = 'url_key'): string
    {
        $attempts = 0;
        do {
            $key = $this->generateUrlKey();
            $exists = \Illuminate\Database\Capsule\Manager::table($table)->where($column, $key)->exists();
            if (!$exists) {
                return $key;
            }
            $attempts++;
        } while ($attempts < 20);
        return $this->generateUrlKey() . bin2hex(random_bytes(2));
    }

    /**
     * Konunun url_key'i yoksa (random modda) oluşturur ve kaydeder (lazy backfill).
     */
    public function ensureUrlKey(Topic $topic): void
    {
        if ($topic->url_key !== null && $topic->url_key !== '') {
            return;
        }
        $topic->url_key = $this->generateUniqueUrlKey();
        $topic->save();
    }
}
