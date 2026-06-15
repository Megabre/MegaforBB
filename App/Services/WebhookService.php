<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Topic;

/**
 * Konu/mesaj olaylarında harici webhook'lara (Discord, Telegram) POST atar.
 * URL'ler .env ile: WEBHOOK_DISCORD_URL, WEBHOOK_TELEGRAM_BOT_TOKEN, WEBHOOK_TELEGRAM_CHAT_ID
 * veya tek URL: WEBHOOK_TELEGRAM_URL (örn. Incoming Webhook / bot sendMessage URL).
 */
final class WebhookService
{
    /**
     * Yeni konu açıldığında çağrılır. Discord ve/veya Telegram'a bildirim gönderir.
     */
    public static function notifyTopicCreated(Topic $topic): void
    {
        $topic->loadMissing('user');
        $author = $topic->user ? $topic->user->username : '?';
        $title = $topic->title ?? '';
        $path = function_exists('topic_url_path') ? 'topic/' . topic_url_path($topic) : '';
        $url = $path !== '' && function_exists('full_site_url') ? full_site_url($path) : '';
        $text = sprintf("Yeni konu: %s\nYazar: %s\n%s", $title, $author, $url ?: '');

        self::sendDiscord($text);
        self::sendTelegram($text);
    }

    /**
     * Moderatör: X kişisi banlandı. Discord/Telegram'a log.
     * @param int[] $userIds Banlanan kullanıcı id'leri
     */
    public static function notifyUserBanned(array $userIds, string $adminUsername): void
    {
        if (empty($userIds)) {
            return;
        }
        $names = \App\Models\User::whereIn('id', $userIds)->pluck('username')->all();
        $list = implode(', ', array_map('trim', $names));
        $text = sprintf("⚠️ Moderatör: Ban\nBanlayan: %s\nBanlanan: %s", $adminUsername, mb_substr($list, 0, 500));
        self::sendDiscord($text);
        self::sendTelegram($text);
    }

    /**
     * Moderatör: Konu silindi / çöpe taşındı. Discord/Telegram'a log.
     */
    public static function notifyTopicDeleted(Topic $topic, ?int $deletedByUserId = null): void
    {
        $topic->loadMissing(['user', 'forum']);
        $title = $topic->title ?? '?';
        $forumName = $topic->forum ? $topic->forum->name : '?';
        $actor = $deletedByUserId ? (\App\Models\User::find($deletedByUserId)?->username ?? (string) $deletedByUserId) : '?';
        $text = sprintf("🗑️ Moderatör: Konu silindi\nKonu: %s\nForum: %s\nSilen: %s", $title, $forumName, $actor);
        self::sendDiscord($text);
        self::sendTelegram($text);
    }

    /**
     * Kritik sistem hatası (DB çökmesi vb.). Sadece Telegram'a "🔴 Kritik" etiketiyle gönderilir.
     */
    public static function notifyCriticalError(string $message, array $context = []): void
    {
        $line = "🔴 Kritik: " . mb_substr($message, 0, 3500);
        if (!empty($context)) {
            $line .= "\n" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_INLINE_ARRAY);
        }
        self::sendTelegram($line);
    }

    private static function sendDiscord(string $content): void
    {
        $url = env('WEBHOOK_DISCORD_URL', '');
        if ($url === '') {
            return;
        }
        $payload = ['content' => mb_substr($content, 0, 2000)];
        self::post($url, $payload);
    }

    private static function sendTelegram(string $text): void
    {
        $token = env('WEBHOOK_TELEGRAM_BOT_TOKEN', '');
        $chatId = env('WEBHOOK_TELEGRAM_CHAT_ID', '');
        if ($token !== '' && $chatId !== '') {
            $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $token);
            self::post($url, ['chat_id' => $chatId, 'text' => mb_substr($text, 0, 4096)]);
            return;
        }
        $singleUrl = env('WEBHOOK_TELEGRAM_URL', '');
        if ($singleUrl !== '') {
            self::post($singleUrl, ['text' => mb_substr($text, 0, 4096)]);
        }
    }

    private static function post(string $url, array $payload): void
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 5,
            ],
        ];
        $ctx = stream_context_create($opts);
        @file_get_contents($url, false, $ctx);
    }
}
