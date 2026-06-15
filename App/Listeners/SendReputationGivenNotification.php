<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ReputationGiven;
use App\Services\Alerts\UserAlertService;
use App\Models\Post;

class SendReputationGivenNotification
{
    public function onReputationGiven(ReputationGiven $event): void
    {
        $toUserId = (int) $event->toUser->id;
        $from = $event->fromUser;
        if ($toUserId <= 0 || $toUserId === (int) $from->id) {
            return;
        }
        $url = core_url('member/' . rawurlencode($event->toUser->username ?? ''));
        $topicTitle = '';
        $topicId = null;

        if ($event->postId !== null) {
            $post = Post::with('topic')->find($event->postId);
            if ($post && $post->topic) {
                $topicId = (int) $post->topic_id;
                $topicTitle = $post->topic->title ?? '';
                $url = core_url('topic/' . topic_url_path_by_id($topicId)) . '#post-' . (int) $post->id;
            }
        }

        $data = [
            'url' => $url,
            'from_user_id' => (int) $from->id,
            'from_username' => $from->username ?? '',
            'value' => $event->value,
            'topic_id' => $topicId,
            'topic_title' => $topicTitle,
        ];
        if ($event->postId !== null) {
            $data['post_id'] = (int) $event->postId;
        }
        (new UserAlertService())->insert($toUserId, 'reputation', $data);
    }
}
