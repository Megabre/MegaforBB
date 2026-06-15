<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostCreated;
use App\Services\UserActivityService;

class LogPostActivity
{
    public function onPostCreated(PostCreated $event): void
    {
        $post = $event->post;
        $user = $post->user;
        $topic = $post->topic;

        if ($user && $topic) {
            $activity = new UserActivityService();
            $bodySnippet = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($post->body_html ?? ''))), 0, 70);
            if (mb_strlen($bodySnippet) >= 70) {
                $bodySnippet .= '…';
            }

            $activity->log(
                (int)$user->id,
                UserActivityService::ACTION_POST_CREATED,
                (int)$post->id,
                [
                    'topic_id' => $topic->id,
                    'topic_title' => $topic->title,
                    'body_snippet' => $bodySnippet,
                ]
            );
        }
    }
}
