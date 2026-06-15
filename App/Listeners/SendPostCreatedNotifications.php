<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PostCreated;
use App\Models\Post;
use App\Services\Alerts\UserAlertService;
use App\Models\TopicSubscription;
use App\Models\UserPreference;

class SendPostCreatedNotifications
{
    public function onPostCreated(PostCreated $event): void
    {
        $post = $event->post;
        try {
            $post->loadMissing(['topic', 'user']);
            $topic = $post->topic;
            if (!$topic) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $replyAuthorId = (int) $post->user_id;
        $topicOwnerId = (int) $topic->user_id;
        $replyUrl = topic_url($topic, '#post-' . (int) $post->id);
        $topicTitle = $topic->title ?? '';
        $author = $post->user ?? null;
        $fromUserId = $author ? (int) $author->id : 0;
        $fromUsername = $author ? ($author->username ?? '') : '';

        $payload = [
            'url' => $replyUrl,
            'from_user_id' => $fromUserId,
            'from_username' => $fromUsername,
            'topic_id' => (int) $topic->id,
            'topic_title' => $topicTitle,
            'post_id' => (int) $post->id,
        ];

        if ($topicOwnerId && $topicOwnerId !== $replyAuthorId) {
            $this->createNotification($topicOwnerId, 'reply', $payload);
        }

        try {
            $subscriberIds = TopicSubscription::where('topic_id', $topic->id)
                ->where('user_id', '!=', $replyAuthorId)
                ->where('user_id', '!=', $topicOwnerId)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            foreach ($subscriberIds as $uid) {
                if ($this->getUserPreference($uid, 'notif_followed_topic_reply', '1') === '1') {
                    $this->createNotification($uid, 'subscribed_topic_reply', $payload);
                }
            }
        } catch (\Throwable $e) {
        }

        foreach ($this->extractQuotedUserIds($post, $replyAuthorId) as $uid) {
            if ($this->getUserPreference($uid, 'notif_quote', '1') === '1') {
                $this->createNotification($uid, 'quote', $payload);
            }
        }

        $mentionedIds = core_extract_mentioned_user_ids($post->body ?? '');
        foreach ($mentionedIds as $uid) {
            if ($uid !== $replyAuthorId && $this->getUserPreference($uid, 'notif_mention', '1') === '1') {
                $this->createNotification($uid, 'mention', $payload);
            }
        }
    }

    private function createNotification(int $toUserId, string $type, array $data): void
    {
        (new UserAlertService())->insert($toUserId, $type, $data);
    }

    private function extractQuotedUserIds(Post $post, int $replyAuthorId): array
    {
        $quotedPostIds = $this->extractQuotedPostIds((string) ($post->body ?? ''), (string) ($post->body_html ?? ''));
        if (empty($quotedPostIds)) {
            return [];
        }

        $userIds = [];

        try {
            $quotedPosts = Post::query()
                ->whereIn('id', $quotedPostIds)
                ->whereNull('deleted_at')
                ->get(['id', 'user_id']);

            foreach ($quotedPosts as $quotedPost) {
                $quotedUserId = (int) $quotedPost->user_id;
                if ($quotedUserId > 0 && $quotedUserId !== $replyAuthorId) {
                    $userIds[$quotedUserId] = true;
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return array_keys($userIds);
    }

    private function extractQuotedPostIds(string $body, string $bodyHtml): array
    {
        $ids = [];

        if (preg_match_all('/\[quote[^\]]*\bpost=(?:"(\d+)"|\'(\d+)\'|(\d+))/i', $body, $bodyMatches, PREG_SET_ORDER)) {
            foreach ($bodyMatches as $match) {
                $id = (int) ($match[1] !== '' ? $match[1] : ($match[2] !== '' ? $match[2] : $match[3]));
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }

        if (preg_match_all('/<blockquote\b[^>]*\bdata-post=(?:"(\d+)"|\'(\d+)\'|(\d+))/i', $bodyHtml, $htmlMatches, PREG_SET_ORDER)) {
            foreach ($htmlMatches as $match) {
                $id = (int) ($match[1] !== '' ? $match[1] : ($match[2] !== '' ? $match[2] : $match[3]));
                if ($id > 0) {
                    $ids[$id] = true;
                }
            }
        }

        return array_keys($ids);
    }

    private function getUserPreference(int $userId, string $key, string $default = '1'): string
    {
        $v = UserPreference::where('user_id', $userId)->where('preference_key', $key)->value('value');
        return $v !== null ? (string) $v : $default;
    }
}
