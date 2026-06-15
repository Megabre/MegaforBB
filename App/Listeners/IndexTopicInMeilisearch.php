<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TopicCreated;
use App\Services\MeilisearchService;

class IndexTopicInMeilisearch
{
    public function onTopicCreated(TopicCreated $event): void
    {
        $topic = $event->topic;
        try {
            $meili = new MeilisearchService();
            $meili->indexTopic($topic);
        } catch (\Throwable $e) {
            // Meilisearch ayakta değilse hata fırlatmayalım
            error_log('Meilisearch index error: ' . $e->getMessage());
        }
    }
}
