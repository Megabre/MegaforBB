<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TopicDeleted;
use App\Services\WebhookService;

/**
 * topic.deleted event'inde WebhookService ile Discord/Telegram'a moderasyon logu gönderir.
 */
final class SendTopicDeletedWebhook
{
    public function onTopicDeleted(TopicDeleted $event): void
    {
        try {
            WebhookService::notifyTopicDeleted($event->topic, $event->deletedByUserId);
        } catch (\Throwable $e) {
            error_log('WebhookService topic.deleted: ' . $e->getMessage());
        }
    }
}
