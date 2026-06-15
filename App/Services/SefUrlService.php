<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\User;

/**
 * Tüm sistem SEF URL: tek ayar (sef_url_mode) ile konu, makale, mesaj, özel mesaj, bildirim, ek, üye.
 * id | slug | random
 */
class SefUrlService
{
    public const MODE_ID = 'id';
    public const MODE_SLUG = 'slug';
    public const MODE_RANDOM = 'random';

    private const URL_KEY_LENGTH = 24;
    private const URL_KEY_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /** Tüm sistem için tek mod (konu, makale, mesaj, konuşma, bildirim, ek, üye). */
    public function getMode(): string
    {
        $mode = (string) Setting::getValue('sef_url_mode', Setting::getValue('sef_topic_url_mode', self::MODE_ID));
        return in_array($mode, [self::MODE_ID, self::MODE_SLUG, self::MODE_RANDOM], true) ? $mode : self::MODE_ID;
    }

    // ---------- Topic (zaten TopicUrlService'te; burada tekrar getMode ile uyum) ----------
    // Topic/Article path ve resolve TopicUrlService'te kalsın; sadece getMode burada merkezi.

    // ---------- Post ----------
    public function pathForPost($post): string
    {
        $id = (int) ($post->id ?? 0);
        $urlKey = $post->url_key ?? null;
        $mode = $this->getMode();
        if ($mode === self::MODE_RANDOM && $urlKey !== null && $urlKey !== '') {
            return $urlKey;
        }
        return (string) $id;
    }

    public function pathForPostId(int $postId): string
    {
        $post = Post::find($postId);
        return $post !== null ? $this->pathForPost($post) : (string) $postId;
    }

    public function resolvePostId(string $identifier): ?int
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        if (ctype_digit($identifier)) {
            $id = (int) $identifier;
            return $id > 0 ? $id : null;
        }
        $row = Post::query()->where('url_key', $identifier)->first(['id']);
        return $row !== null ? (int) $row->id : null;
    }

    public function ensurePostUrlKey(Post $post): void
    {
        if ($post->url_key !== null && $post->url_key !== '') {
            return;
        }
        $post->url_key = $this->generateUniqueUrlKeyForTable('posts', 'url_key');
        $post->save();
    }

    // ---------- Conversation ----------
    public function pathForConversation($conv): string
    {
        $id = (int) ($conv->id ?? 0);
        $urlKey = $conv->url_key ?? null;
        if ($this->getMode() === self::MODE_RANDOM && $urlKey !== null && $urlKey !== '') {
            return $urlKey;
        }
        return (string) $id;
    }

    public function pathForConversationId(int $convId): string
    {
        $conv = \Illuminate\Database\Capsule\Manager::table('conversations')->where('id', $convId)->first(['id', 'url_key']);
        if (!$conv) {
            return (string) $convId;
        }
        return $this->getMode() === self::MODE_RANDOM && !empty($conv->url_key) ? $conv->url_key : (string) $convId;
    }

    public function resolveConversationId(string $identifier): ?int
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        if (ctype_digit($identifier)) {
            $id = (int) $identifier;
            return $id > 0 ? $id : null;
        }
        $row = \Illuminate\Database\Capsule\Manager::table('conversations')->where('url_key', $identifier)->first(['id']);
        return $row ? (int) $row->id : null;
    }

    public function ensureConversationUrlKey(object $conv): void
    {
        if (!empty($conv->url_key)) {
            return;
        }
        $key = $this->generateUniqueUrlKeyForTable('conversations', 'url_key');
        \Illuminate\Database\Capsule\Manager::table('conversations')->where('id', $conv->id)->update(['url_key' => $key]);
        $conv->url_key = $key;
    }

    // ---------- Notification ----------
    public function pathForNotification($n): string
    {
        $id = (int) ($n->id ?? 0);
        $urlKey = $n->url_key ?? null;
        if ($this->getMode() === self::MODE_RANDOM && $urlKey !== null && $urlKey !== '') {
            return $urlKey;
        }
        return (string) $id;
    }

    public function resolveNotificationId(string $identifier): ?int
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        if (ctype_digit($identifier)) {
            $id = (int) $identifier;
            return $id > 0 ? $id : null;
        }
        $row = \Illuminate\Database\Capsule\Manager::table('notifications')->where('url_key', $identifier)->first(['id']);
        return $row ? (int) $row->id : null;
    }

    // ---------- Attachment ----------
    public function pathForAttachment($att): string
    {
        $id = (int) ($att->id ?? 0);
        $urlKey = $att->url_key ?? null;
        if ($this->getMode() === self::MODE_RANDOM && $urlKey !== null && $urlKey !== '') {
            return $urlKey;
        }
        return (string) $id;
    }

    public function resolveAttachmentId(string $identifier): ?int
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        if (ctype_digit($identifier)) {
            $id = (int) $identifier;
            return $id > 0 ? $id : null;
        }
        $row = \Illuminate\Database\Capsule\Manager::table('attachments')->where('url_key', $identifier)->first(['id']);
        return $row ? (int) $row->id : null;
    }

    // ---------- Member (User) ----------
    public function pathForMember($user): string
    {
        $id = (int) ($user->id ?? 0);
        $username = $user->username ?? '';
        $urlKey = $user->url_key ?? null;
        switch ($this->getMode()) {
            case self::MODE_SLUG:
                return $username !== '' ? $username : (string) $id;
            case self::MODE_RANDOM:
                if ($urlKey !== null && $urlKey !== '') {
                    return $urlKey;
                }
                return (string) $id;
            default:
                return (string) $id;
        }
    }

    /** Member URL'den kullanıcı bulur; identifier username, id veya url_key olabilir. */
    public function resolveMemberIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        if (ctype_digit($identifier)) {
            $u = User::find((int) $identifier);
            return $u;
        }
        $u = User::query()->where('username', $identifier)->orWhere('url_key', $identifier)->first();
        return $u;
    }

    public function ensureUserUrlKey(User $user): void
    {
        if ($user->url_key !== null && $user->url_key !== '') {
            return;
        }
        $user->url_key = $this->generateUniqueUrlKeyForTable('users', 'url_key');
        $user->save();
    }

    // ---------- Article (topic type=article) ----------
    /**
     * Makale için tam URL path: articles/kategori-slug/makale-slug veya (eski) article/identifier
     */
    public function pathForArticleId(int $topicId): string
    {
        $topic = Topic::with('forum.category')->find($topicId);
        if ($topic === null) {
            return 'article/' . $topicId;
        }
        $topicPath = (new TopicUrlService())->pathForTopic($topic);
        $category = $topic->forum->category ?? null;
        if ($category !== null && !empty($category->is_article_category)) {
            return 'articles/' . $category->slug . '/' . $topicPath;
        }
        return 'article/' . $topicPath;
    }

    public function resolveArticleId(string $identifier): ?int
    {
        return (new TopicUrlService())->resolveTopicId($identifier);
    }

    // ---------- Yardımcı: benzersiz url_key ----------
    public function generateUniqueUrlKeyForTable(string $table, string $column = 'url_key'): string
    {
        $attempts = 0;
        do {
            $key = $this->generateUrlKey();
            $exists = \Illuminate\Database\Capsule\Manager::table($table)->where($column, $key)->exists();
            if (!$exists) {
                return $key;
            }
            $attempts++;
        } while ($attempts < 20);
        return $this->generateUrlKey() . bin2hex(random_bytes(2));
    }

    public function generateUrlKey(): string
    {
        $max = strlen(self::URL_KEY_CHARS) - 1;
        $key = '';
        for ($i = 0; $i < self::URL_KEY_LENGTH; $i++) {
            $key .= self::URL_KEY_CHARS[random_int(0, $max)];
        }
        return $key;
    }

    /**
     * Tüm URL'leri mevcut SEF moduna göre günceller (slug doldurur veya url_key atar).
     * Eski /topic/5 gibi linkler yeni formata dönüştürülür.
     * @return array{topics: int, posts: int, conversations: int, notifications: int, attachments: int, users: int}
     */
    public function rebuildAllUrls(): array
    {
        $topicSvc = new TopicUrlService();
        $mode = $this->getMode();
        $counts = ['topics' => 0, 'posts' => 0, 'conversations' => 0, 'notifications' => 0, 'attachments' => 0, 'users' => 0];

        if ($mode === self::MODE_SLUG) {
            Topic::orderBy('id')->chunk(300, function ($topics) use ($topicSvc, &$counts) {
                foreach ($topics as $topic) {
                    $title = (string) ($topic->title ?? '');
                    $id = (int) $topic->id;
                    $slug = $topicSvc->generateSlugFromTitle($title !== '' ? $title : 'topic', $id);
                    if (($topic->slug ?? '') !== $slug) {
                        $topic->slug = $slug;
                        $topic->save();
                        $counts['topics']++;
                    }
                }
            });
            return $counts;
        }

        if ($mode === self::MODE_RANDOM) {
            Topic::whereNull('url_key')->orWhere('url_key', '')->orderBy('id')->chunk(300, function ($topics) use (&$counts) {
                foreach ($topics as $topic) {
                    $topic->url_key = $this->generateUniqueUrlKeyForTable('topics', 'url_key');
                    $topic->save();
                    $counts['topics']++;
                }
            });
            Post::whereNull('url_key')->orWhere('url_key', '')->orderBy('id')->chunk(300, function ($posts) use (&$counts) {
                foreach ($posts as $post) {
                    $post->url_key = $this->generateUniqueUrlKeyForTable('posts', 'url_key');
                    $post->save();
                    $counts['posts']++;
                }
            });
            \Illuminate\Database\Capsule\Manager::table('conversations')->whereNull('url_key')->orWhere('url_key', '')->orderBy('id')->chunk(300, function ($rows) use (&$counts) {
                foreach ($rows as $row) {
                    \Illuminate\Database\Capsule\Manager::table('conversations')->where('id', $row->id)->update(['url_key' => $this->generateUniqueUrlKeyForTable('conversations', 'url_key')]);
                    $counts['conversations']++;
                }
            });
            \Illuminate\Database\Capsule\Manager::table('notifications')->whereNull('url_key')->orWhere('url_key', '')->orderBy('id')->chunk(300, function ($rows) use (&$counts) {
                foreach ($rows as $row) {
                    \Illuminate\Database\Capsule\Manager::table('notifications')->where('id', $row->id)->update(['url_key' => $this->generateUniqueUrlKeyForTable('notifications', 'url_key')]);
                    $counts['notifications']++;
                }
            });
            \Illuminate\Database\Capsule\Manager::table('attachments')->whereNull('url_key')->orWhere('url_key', '')->orderBy('id')->chunk(300, function ($rows) use (&$counts) {
                foreach ($rows as $row) {
                    \Illuminate\Database\Capsule\Manager::table('attachments')->where('id', $row->id)->update(['url_key' => $this->generateUniqueUrlKeyForTable('attachments', 'url_key')]);
                    $counts['attachments']++;
                }
            });
            User::whereNull('url_key')->orWhere('url_key', '')->orderBy('id')->chunk(300, function ($users) use (&$counts) {
                foreach ($users as $user) {
                    $user->url_key = $this->generateUniqueUrlKeyForTable('users', 'url_key');
                    $user->save();
                    $counts['users']++;
                }
            });
        }

        return $counts;
    }
}
