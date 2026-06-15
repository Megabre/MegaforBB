<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TopicCreated;
use App\Services\UserActivityService;

class LogTopicActivity
{
    public function onTopicCreated(TopicCreated $event): void
    {
        $topic = $event->topic;
        $user = $topic->user;

        if ($user) {
            $activity = new UserActivityService();
            $activity->log(
                (int)$user->id,
                UserActivityService::ACTION_TOPIC_CREATED,
                (int)$topic->id,
                [
                    'title' => $topic->title,
                    'slug' => $topic->slug,
                    'forum_id' => $topic->forum_id,
                    'forum_name' => $topic->forum->name ?? ''
                ]
            );
        }
    }
}
