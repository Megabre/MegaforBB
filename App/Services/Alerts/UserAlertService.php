<?php

declare(strict_types=1);

namespace App\Services\Alerts;

use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserPreference;

/**
 * XenForo XF\Repository\UserAlert ile aynı rol: tek giriş noktası, engel/tercih kontrolü,
 * gönderen + içerik türü (content_type/content_id/action) normalizasyonu.
 */
final class UserAlertService
{
    /** @var array<string, string|null> legacy type => user_preferences.preference_key (null = her zaman) */
    private const PREFERENCE_BY_TYPE = [
        'subscribed_topic_reply' => 'notif_followed_topic_reply',
        'quote' => 'notif_quote',
        'mention' => 'notif_mention',
        'reaction' => 'notif_reaction',
        'reply' => null,
        'reputation' => null,
        'report' => null,
        'private_topic_added' => null,
        'announcement' => null,
        'pm_undeliverable' => null,
    ];

    public function insert(int $receiverId, string $type, array $data, bool $skipPreferenceCheck = false): bool
    {
        $receiverId = (int) $receiverId;
        if ($receiverId <= 0 || $type === '') {
            return false;
        }

        $receiver = User::query()->find($receiverId);
        if (!$receiver || (int) ($receiver->is_banned ?? 0) === 1) {
            return false;
        }

        $senderId = (int) ($data['from_user_id'] ?? 0);
        $senderName = trim((string) ($data['from_username'] ?? ''));

        if ($senderId > 0 && $senderId === $receiverId) {
            return false;
        }

        if ($senderId > 0 && $this->isMutuallyBlocked($receiverId, $senderId)) {
            return false;
        }

        if (!$skipPreferenceCheck && !$this->userReceivesAlert($receiverId, $type)) {
            return false;
        }

        $norm = $this->deriveNormalized($type, $data, $receiverId, $senderId);

        try {
            Notification::create([
                'user_id' => $receiverId,
                'sender_user_id' => $senderId > 0 ? $senderId : null,
                'sender_username' => $senderName !== '' ? mb_substr($senderName, 0, 100) : null,
                'type' => $type,
                'content_type' => $norm['content_type'],
                'content_id' => $norm['content_id'],
                'action' => $norm['action'],
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'auto_read' => 0,
            ]);

            return true;
        } catch (\Throwable $e) {
            error_log('[UserAlertService] insert failed: ' . $e->getMessage() . ' receiver=' . $receiverId . ' type=' . $type);

            return false;
        }
    }

    /**
     * İçerik silindiğinde ilgili uyarıları kaldırır (XF fastDeleteAlertsForContent benzeri).
     */
    public function deleteForContent(string $contentType, int $contentId): int
    {
        if ($contentType === '' || $contentId <= 0) {
            return 0;
        }

        try {
            return (int) Notification::query()
                ->where('content_type', $contentType)
                ->where('content_id', $contentId)
                ->delete();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Konu silindiğinde bu konuya bağlı tüm uyarıları kaldırır (mesaj + konu daveti).
     */
    public function deleteForTopic(int $topicId): int
    {
        if ($topicId <= 0) {
            return 0;
        }

        $n = 0;
        try {
            $n += (int) Notification::query()
                ->where('content_type', 'thread')
                ->where('content_id', $topicId)
                ->delete();
            $ids = Post::withTrashed()->where('topic_id', $topicId)->pluck('id');
            foreach ($ids as $pid) {
                $n += $this->deleteForContent('post', (int) $pid);
            }
        } catch (\Throwable $e) {
            return $n;
        }

        return $n;
    }

    /** Birleştirmede kaynak konu yok olunca sadece konu düzeyindeki uyarılar (ör. özel konu daveti). */
    public function deleteThreadLevelAlerts(int $topicId): int
    {
        if ($topicId <= 0) {
            return 0;
        }

        try {
            return (int) Notification::query()
                ->where('content_type', 'thread')
                ->where('content_id', $topicId)
                ->delete();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function userReceivesAlert(int $receiverId, string $type): bool
    {
        $key = self::PREFERENCE_BY_TYPE[$type] ?? null;
        if ($key === null) {
            return true;
        }

        $v = UserPreference::query()
            ->where('user_id', $receiverId)
            ->where('preference_key', $key)
            ->value('value');

        return ($v === null || (string) $v === '1' || (string) $v === '');
    }

    private function isMutuallyBlocked(int $receiverId, int $senderId): bool
    {
        if ($senderId <= 0) {
            return false;
        }

        return UserBlock::query()
            ->where(function ($q) use ($receiverId, $senderId): void {
                $q->where(function ($q2) use ($receiverId, $senderId): void {
                    $q2->where('user_id', $receiverId)->where('blocked_user_id', $senderId);
                })->orWhere(function ($q2) use ($receiverId, $senderId): void {
                    $q2->where('user_id', $senderId)->where('blocked_user_id', $receiverId);
                });
            })
            ->exists();
    }

    /**
     * @return array{content_type: ?string, content_id: ?int, action: ?string}
     */
    private function deriveNormalized(string $type, array $data, int $receiverId, int $senderId): array
    {
        $topicId = (int) ($data['topic_id'] ?? 0);
        $postId = (int) ($data['post_id'] ?? 0);
        $annId = (int) ($data['announcement_id'] ?? 0);

        return match ($type) {
            'reaction' => ['content_type' => 'post', 'content_id' => $postId > 0 ? $postId : null, 'action' => 'reaction'],
            'reply', 'subscribed_topic_reply' => [
                'content_type' => 'post',
                'content_id' => $postId > 0 ? $postId : ($topicId > 0 ? $topicId : null),
                'action' => $type === 'reply' ? 'reply' : 'insert',
            ],
            'quote', 'mention' => [
                'content_type' => 'post',
                'content_id' => $postId > 0 ? $postId : ($topicId > 0 ? $topicId : null),
                'action' => $type,
            ],
            'report' => ['content_type' => 'post', 'content_id' => $postId > 0 ? $postId : null, 'action' => 'report'],
            'reputation' => [
                'content_type' => $postId > 0 ? 'post' : 'user',
                'content_id' => $postId > 0 ? $postId : $receiverId,
                'action' => 'reputation',
            ],
            'private_topic_added' => ['content_type' => 'thread', 'content_id' => $topicId > 0 ? $topicId : null, 'action' => 'invite'],
            'announcement' => ['content_type' => 'announcement', 'content_id' => $annId > 0 ? $annId : null, 'action' => 'publish'],
            'pm_undeliverable' => ['content_type' => 'system', 'content_id' => null, 'action' => 'undeliverable'],
            default => ['content_type' => 'system', 'content_id' => null, 'action' => $type],
        };
    }
}
