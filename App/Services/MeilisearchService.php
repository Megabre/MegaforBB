<?php

declare(strict_types=1);

namespace App\Services;

use Meilisearch\Client;

class MeilisearchService
{
    private Client $client;

    private ?string $lastError = null;

    public function __construct()
    {
        $this->client = new Client(
            env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
            env('MEILISEARCH_KEY', null)
        );
    }

    /** Bağlantı hatası (isAvailable false döndüğünde). */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /** Kullanılan Meilisearch adresi (ayar kontrolü için). */
    public function getHost(): string
    {
        return env('MEILISEARCH_HOST', 'http://127.0.0.1:7700');
    }

    public function indexTopic(\App\Models\Topic $topic): void
    {
        $this->client->index('topics')->addDocuments([
            [
                'id' => $topic->id,
                'title' => $topic->title,
                'slug' => $topic->slug,
                'body' => $topic->posts()->where('is_first_post', 1)->first()?->body ?? '',
                'forum_id' => $topic->forum_id,
                'created_at' => $topic->created_at?->timestamp,
            ]
        ]);
    }

    public function search(string $query, array $options = []): array
    {
        return $this->client->index('topics')->search($query, $options)->getHits();
    }

    /** Arama sonuçları + toplam sayı (sayfalama için). */
    public function searchWithTotal(string $query, array $options = []): array
    {
        $result = $this->client->index('topics')->search($query, $options);
        return [
            'hits' => $result->getHits(),
            'total' => $result->getEstimatedTotalHits() ?? 0,
        ];
    }

    /**
     * Rebuild: toplu konu indeksleme. Her öğe: id, title, slug, body, forum_id, created_at (timestamp).
     * Meilisearch erişilebilir değilse false döner.
     */
    public function indexTopicBatch(array $documents): bool
    {
        if ($documents === []) {
            return true;
        }
        try {
            $this->client->index('topics')->addDocuments($documents);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Rebuild öncesi bağlantı kontrolü. Hata oluşursa getLastError() ile neden alınır. */
    public function isAvailable(): bool
    {
        $this->lastError = null;
        try {
            $this->client->health();
            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
