<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostReported;
use App\Services\Alerts\UserAlertService;

class SendPostReportedNotification
{
    public function onPostReported(PostReported $event): void
    {
        $post = $event->post;
        $postAuthorId = (int) $post->user_id;
        $reporterId = (int) $event->reporter->id;
        if (!$postAuthorId || $postAuthorId === $reporterId) {
            return;
        }
        (new UserAlertService())->insert($postAuthorId, 'report', [
            'url' => core_url('topic/' . topic_url_path_by_id($post->topic_id)) . '#post-' . (int) $post->id,
            'from_user_id' => $reporterId,
            'from_username' => $event->reporter->username ?? '',
            'post_id' => (int) $post->id,
            'topic_id' => (int) $post->topic_id,
        ]);
    }
}
