<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TopicCreated;
use App\Services\WebhookService;

/**
 * topic.created event'inde WebhookService ile Discord/Telegram'a bildirim gönderir.
 */
final class SendTopicCreatedWebhook
{
    public function onTopicCreated(TopicCreated $event): void
    {
        try {
            WebhookService::notifyTopicCreated($event->topic);
        } catch (\Throwable $e) {
            error_log('WebhookService topic.created: ' . $e->getMessage());
        }
    }
}
