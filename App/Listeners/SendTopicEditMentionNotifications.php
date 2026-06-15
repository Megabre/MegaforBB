<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TopicEdited;
use App\Models\UserPreference;
use App\Services\Alerts\UserAlertService;

class SendTopicEditMentionNotifications
{
    public function onTopicEdited(TopicEdited $event): void
    {
        $editorId = (int) $event->editor->id;
        $mentionedIds = core_extract_mentioned_user_ids($event->body);
        $replyUrl = topic_url($event->topic);
        $payload = [
            'url' => $replyUrl,
            'from_user_id' => $editorId,
            'from_username' => $event->editor->username ?? '',
            'topic_id' => (int) $event->topic->id,
            'topic_title' => $event->title,
        ];
        foreach ($mentionedIds as $uid) {
            if ($uid !== $editorId && $this->prefOn($uid, 'notif_mention')) {
                $this->createNotification($uid, 'mention', $payload);
            }
        }
    }

    private function createNotification(int $toUserId, string $type, array $data): void
    {
        (new UserAlertService())->insert($toUserId, $type, $data);
    }

    private function prefOn(int $userId, string $key): bool
    {
        $v = UserPreference::where('user_id', $userId)->where('preference_key', $key)->value('value');

        return $v === null || (string) $v === '1' || (string) $v === '';
    }
}
