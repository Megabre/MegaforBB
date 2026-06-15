<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostLiked;
use App\Services\Alerts\UserAlertService;

class SendPostLikedNotification
{
    public function onPostLiked(PostLiked $event): void
    {
        $post = $event->post;
        $postOwnerId = (int) $post->user_id;
        $likerId = (int) $event->userWhoLiked->id;
        if ($postOwnerId === $likerId) {
            return;
        }
        $liker = $event->userWhoLiked;
        $post->loadMissing('topic');
        $topic = $post->topic ?? null;
        $topicTitle = $topic ? ($topic->title ?? '') : '';
        (new UserAlertService())->insert($postOwnerId, 'reaction', [
            'url' => core_url('topic/' . topic_url_path_by_id($post->topic_id)) . '#post-' . (int) $post->id,
            'from_user_id' => $likerId,
            'from_username' => $liker->username ?? '',
            'post_id' => (int) $post->id,
            'topic_id' => (int) $post->topic_id,
            'topic_title' => $topicTitle,
        ]);
    }
}
