<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumStats;
use App\Models\Post;
use App\Models\Topic;
use App\Models\TopicSubscription;
use App\Services\Alerts\UserAlertService;
use Carbon\Carbon;
use Forecor\Core\Application;
use Illuminate\Database\Capsule\Manager as DB;

class TopicService
{
    protected ForumSyncService $forumSyncService;
    protected ?Application $app;

    public function __construct(ForumSyncService $forumSyncService = null, ?Application $app = null)
    {
        $this->forumSyncService = $forumSyncService ?? new ForumSyncService();
        $this->app = $app;
    }
    /**
     * Anti-Bump Kontrolü (Cotonti Özelliği)
     * Kullanıcı son mesajı atan kişiyse ve X dakika geçmediyse yeni mesaj atamaz (veya birleştirilir).
     *
     * @param int $userId Kullanıcı ID
     * @param int $topicId Konu ID
     * @param int $timelimitDakika Kaç dakika içinde? (Varsayılan 60 dk)
     * @return bool True ise bump yasak (hata dönülmeli veya birleştirilmeli), False ise sorun yok.
     */
    public function isDoublePost(int $userId, int $topicId, int $timelimitDakika = 60): bool
    {
        // Konunun son mesajını bul
        $lastPost = Post::where('topic_id', $topicId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastPost) {
            return false;
        }

        // Son mesaj başkasınınsa sorun yok
        if ($lastPost->user_id !== $userId) {
            return false;
        }

        // Süre kontrolü
        $lastPostTime = Carbon::parse($lastPost->created_at);
        $diff = $lastPostTime->diffInMinutes(Carbon::now());

        if ($diff < $timelimitDakika) {
            return true;
        }

        return false;
    }

    /**
     * Konuyu ve bağlı mesajları soft-delete yapar, forum istatistiklerini günceller.
     *
     * @param Topic $topic Silinecek konu modeli
     * @param int $userId Silme işlemini yapan kullanıcı ID
     * @return bool Başarılıysa true, hata olursa Exception fırlatır
     * @throws \Throwable
     */
    public function deleteTopic(Topic $topic, int $userId): bool
    {
        DB::connection()->beginTransaction();

        try {
            $topicId = $topic->id;
            $postCount = Post::where('topic_id', $topicId)->count();

            // soft delete: mark topics and posts as deleted
            Post::where('topic_id', $topicId)->update(['deleted_at' => \now(), 'deleted_by' => $userId]);
            $topic->deleted_at = \now();
            $topic->deleted_by = $userId;
            $topic->save();

            // Forum sayacı ve son mesaj bilgisini güncelle; silinen konu listede "son konu" olarak görünmesin
            $this->forumSyncService->syncForum((int) $topic->forum_id);

            DB::table('forum_stats')->where('id', 1)->update([
                'total_topics' => DB::raw('GREATEST(0, CAST(total_topics AS SIGNED) - 1)'),
                'total_posts' => DB::raw('GREATEST(0, CAST(total_posts AS SIGNED) - ' . (int) $postCount . ')'),
            ]);

            (new UserAlertService())->deleteForTopic((int) $topicId);

            DB::connection()->commit();
            return true;
        } catch (\Throwable $e) {
            DB::connection()->rollBack();
            throw $e;
        }
    }

    /**
     * İki konuyu birleştirir. Kayıtlı mesajlar hedef konuya aktarılır ve sayaçlar güncellenir.
     *
     * @param int $sourceTopicId Kaynak (silinecek) konu ID
     * @param int $targetTopicId Hedef (birleştirilecek) konu ID
     * @return bool
     * @throws \Throwable
     */
    public function mergeTopics(int $sourceTopicId, int $targetTopicId): bool
    {
        $sourceTopic = Topic::find($sourceTopicId);
        $targetTopic = Topic::find($targetTopicId);

        if (!$sourceTopic || !$targetTopic) {
            return false;
        }

        DB::connection()->transaction(function () use ($sourceTopicId, $targetTopicId, $sourceTopic, $targetTopic) {
            $postCount = Post::where('topic_id', $sourceTopicId)->count();
            Post::where('topic_id', $sourceTopicId)->update(['topic_id' => $targetTopicId, 'is_first_post' => 0]);
            Topic::where('id', $sourceTopicId)->delete();

            $targetTopic->increment('reply_count', $postCount);

            Forum::where('id', $sourceTopic->forum_id)->update([
                'topic_count' => DB::raw('GREATEST(0, CAST(topic_count AS SIGNED) - 1)'),
            ]);

            $fs = ForumStats::singleton();
            if ($fs) {
                $fs->decrement('total_topics', 1);
            }

            if ($sourceTopic->forum_id != $targetTopic->forum_id) {
                Forum::where('id', $sourceTopic->forum_id)->update([
                    'post_count' => DB::raw('GREATEST(0, CAST(post_count AS SIGNED) - ' . (int) $postCount . ')'),
                ]);
                Forum::where('id', $targetTopic->forum_id)->update([
                    'post_count' => DB::raw('post_count + ' . (int) $postCount),
                ]);
            }

            (new UserAlertService())->deleteThreadLevelAlerts((int) $sourceTopicId);
        });

        return true;
    }

    /**
     * Konuyu başka bir foruma taşır. Kayıtlı sayaçları ve forum verilerini günceller.
     *
     * @param int $topicId Taşınacak konu ID
     * @param int $newForumId Hedef forum ID
     * @return bool
     * @throws \Throwable
     */
    public function moveTopic(int $topicId, int $newForumId): bool
    {
        $topic = Topic::find($topicId);

        if (!$topic || $topic->forum_id == $newForumId || !Forum::where('id', $newForumId)->exists()) {
            return false;
        }

        DB::connection()->transaction(function () use ($topicId, $newForumId, $topic) {
            $postCount = Post::where('topic_id', $topicId)->count();
            $topic->update(['forum_id' => $newForumId]);

            Forum::where('id', $topic->forum_id)->update([
                'topic_count' => DB::raw('GREATEST(0, CAST(topic_count AS SIGNED) - 1)'),
                'post_count' => DB::raw('GREATEST(0, CAST(post_count AS SIGNED) - ' . (int) $postCount . ')'),
            ]);

            Forum::where('id', $newForumId)->update([
                'topic_count' => DB::raw('topic_count + 1'),
                'post_count' => DB::raw('post_count + ' . (int) $postCount),
            ]);
        });

        return true;
    }

    /**
     * Konuya yeni bir cevap ekler. Puan/Sayaç/Abonelik operasyonlarını gerçekleştirir.
     *
     * @param Topic $topic
     * @param \App\Models\User $user
     * @param string $body
     * @param string $bodyHtml
     * @param int|null $replyToId
     * @return Post
     * @throws \Throwable
     */
    public function replyTopic(Topic $topic, \App\Models\User $user, string $body, string $bodyHtml, ?int $replyToId = null): Post
    {
        $post = null;
        $topicId = (int) $topic->id;
        $forumId = (int) $topic->forum_id;
        $userId = (int) $user->id;

        DB::transaction(function () use ($body, $bodyHtml, $topicId, $forumId, $userId, &$post, $replyToId) {
            $now = \now();
            $post = clone Post::create([
                'topic_id' => $topicId,
                'user_id' => $userId,
                'body' => $body,
                'body_html' => $bodyHtml,
                'is_first_post' => false,
                'like_count' => 0,
                'net_votes' => 0,
                'reply_to_id' => $replyToId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (function_exists('sef_service') && sef_service()->getMode() === 'random') {
                $post->url_key = clone sef_service()->generateUniqueUrlKeyForTable('posts', 'url_key');
                $post->save();
            }

            Topic::where('id', $topicId)->increment('reply_count', 1);
            Topic::where('id', $topicId)->update([
                'last_post_at' => $now,
                'last_post_id' => $post->id,
                'last_post_user_id' => $userId,
                'updated_at' => $now,
            ]);

            Forum::where('id', $forumId)->increment('post_count', 1);
            Forum::where('id', $forumId)->update([
                'last_post_at' => $now,
                'last_post_id' => $post->id,
                'last_post_user_id' => $userId,
            ]);

            if (DB::table('forum_stats')->where('id', 1)->exists()) {
                DB::table('forum_stats')->where('id', 1)->increment('total_posts', 1);
            }
        });

        if ($this->app) {
            $this->app->cache()->delete('forum_stats');
            $this->app->cache()->delete('home_categories');

            // Event ve Hook tetiklemeleri
            try {
                $this->app->event()->dispatch(new \App\Events\PostCreated($post), \App\Events\PostCreated::NAME);
                $this->app->hooks()->doAction('after_post_create', $post, $topic, $user);
            } catch (\Throwable $e) {
                // Eklenti hataları ana akışı bozmasın
            }
        }

        $this->handleReplyNotifications($post, $topic, $user);

        return $post;
    }

    /**
     * Mesaj sonrası abonelere ve konu sahibine e-posta gönderimi ve abone ekleme
     */
    protected function handleReplyNotifications(Post $post, Topic $topic, \App\Models\User $replyUser): void
    {
        $topicOwnerId = (int)$topic->user_id;
        $topicTitle = $topic->title ?? '';
        $replyUrl = function_exists('topic_url_path_by_id') && function_exists('core_url')
            ? core_url('topic/' . topic_url_path_by_id((int)$topic->id)) . '#post-' . $post->id
            : '';
        $replyAuthorId = (int)$replyUser->id;

        // Konu sahibine yanıt bildirimi (follow_created_email kapalıysa gönderme; kayıt yoksa UI varsayılanlarıyla uyumlu davran)
        if ($topicOwnerId && $topicOwnerId !== $replyAuthorId) {
            if ($this->topicOwnerWantsCreatedReplyEmail($topicOwnerId)) {
                $this->sendTopicReplyEmail($topicOwnerId, $topicTitle, $replyUser->username, $replyUrl);
            }
        }

        try {
            if ($this->getUserPreference($replyAuthorId, 'follow_interacted_content') === '1') {
                TopicSubscription::firstOrCreate(
                    ['user_id' => $replyAuthorId, 'topic_id' => $topic->id],
                    ['created_at' => \now()]
                );
            }
        } catch (\Throwable $e) {
        }

        try {
            $subscriberIds = TopicSubscription::where('topic_id', $topic->id)
                ->where('user_id', '!=', $replyAuthorId)
                ->where('user_id', '!=', $topicOwnerId)
                ->pluck('user_id')
                ->map(fn ($id) => (int)$id)
                ->toArray();
            $this->sendSubscriberReplyEmails($subscriberIds, (int)$topic->id, $topicTitle, $replyUser->username, $replyUrl);
        } catch (\Throwable $e) {
        }
    }

    private function getUserPreference(int $userId, string $key): string
    {
        try {
            $v = \App\Models\UserPreference::where('user_id', $userId)->where('preference_key', $key)->value('value');
            return $v !== null ? (string) $v : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Konu sahibine "konusuna yanıt" e-postası: açıkça e-postayı kapattıysa gönderilmez;
     * tercih hiç kaydedilmemişse veya oluşturduğu içeriği takip açıksa gönderilir (profil şablonu varsayılanlarıyla uyumlu).
     */
    private function topicOwnerWantsCreatedReplyEmail(int $topicOwnerId): bool
    {
        $emailPref = $this->getUserPreference($topicOwnerId, 'follow_created_email');
        if ($emailPref === '0') {
            return false;
        }
        if ($emailPref === '1') {
            return true;
        }
        $contentPref = $this->getUserPreference($topicOwnerId, 'follow_created_content');

        return $contentPref === '' || $contentPref === '1';
    }

    private function sendTopicReplyEmail(int $toUserId, string $topicTitle, string $fromUsername, string $topicUrl): void
    {
        if (!$this->app) {
            return;
        }
        try {
            $topicUrl = $this->toAbsoluteUrl($topicUrl);
            $u = \App\Models\User::where('id', $toUserId)->whereNotNull('email')->where('email', '!=', '')->first();
            if (!$u) {
                return;
            }
            $mailer = new \App\Services\MailService($this->app);
            $subject = function_exists('lang') ? lang('topic.email_reply_subject', ['title' => mb_substr($topicTitle, 0, 60)]) : 'Yeni Yanıt';
            $bodyHtml = '<p>' . (function_exists('lang') ? lang('topic.email_hello', ['name' => htmlspecialchars($u->username)]) : 'Merhaba ' . htmlspecialchars($u->username)) . '</p>';
            $bodyHtml .= '<p><strong>' . htmlspecialchars($fromUsername) . '</strong> ' . (function_exists('lang') ? lang('topic.email_reply_intro') : 'konunuza yeni bir mesaj yazdı.') . '</p>';
            $bodyHtml .= '<p><a href="' . htmlspecialchars($topicUrl) . '">' . htmlspecialchars($topicTitle) . '</a></p>';
            $mailer->send($u->email, $subject, $bodyHtml, strip_tags($bodyHtml));
        } catch (\Throwable $e) {
        }
    }

    private function sendSubscriberReplyEmails(array $subscriberIds, int $topicId, string $topicTitle, string $fromUsername, string $topicUrl): void
    {
        if (empty($subscriberIds) || !$this->app) {
            return;
        }
        $topicUrl = $this->toAbsoluteUrl($topicUrl);
        $users = \App\Models\User::whereIn('id', array_map('intval', $subscriberIds))->whereNotNull('email')->where('email', '!=', '')->get();
        $mailer = new \App\Services\MailService($this->app);
        foreach ($users as $u) {
            try {
                if ($this->getUserPreference((int) $u->id, 'follow_interacted_email') !== '1') {
                    continue;
                }
                $subject = function_exists('lang') ? lang('topic.email_new_reply_subject', ['title' => mb_substr($topicTitle, 0, 60)]) : 'Yeni Yanıt (Abonelik)';
                $bodyHtml = '<p>' . (function_exists('lang') ? lang('topic.email_hello', ['name' => htmlspecialchars($u->username)]) : 'Merhaba ' . htmlspecialchars($u->username)) . '</p>';
                $bodyHtml .= '<p><strong>' . htmlspecialchars($fromUsername) . '</strong> ' . (function_exists('lang') ? lang('topic.email_subscriber_intro') : 'abone olduğunuz konuya mesaj yazdı.') . '</p>';
                $bodyHtml .= '<p><a href="' . htmlspecialchars($topicUrl) . '">' . htmlspecialchars($topicTitle) . '</a></p>';
                $mailer->send($u->email, $subject, $bodyHtml, strip_tags($bodyHtml));
            } catch (\Throwable $e) {
            }
        }
    }

    private function toAbsoluteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return function_exists('full_site_url') ? full_site_url('') : '/';
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        return function_exists('full_site_url') ? full_site_url(ltrim($url, '/')) : '/' . ltrim($url, '/');
    }

    /**
     * Yeni bir konu/makale oluşturur ve DB üzerindeki ek yükleri (poll, tags, viewers vs.) kapsar.
     */
    public function createTopic(
        \App\Models\Forum $forum,
        \App\Models\User $user,
        string $title,
        string $body,
        string $topicType,
        bool $isQuestion,
        bool $isPrivate,
        bool $isScheduled,
        ?string $scheduledAt,
        int $prefixId,
        array $attachmentIds,
        array $tagIds,
        array $viewerIds,
        array $pollData
    ): Topic {
        $dbType = $topicType === 'article' ? 'article' : ($topicType === 'auction' ? 'auction' : ($isQuestion ? 'question' : 'topic'));
        $pId = $prefixId > 0 ? $prefixId : null;
        $sefService = new \App\Services\TopicUrlService();
        $topicSlug = $sefService->generateSlugFromTitle($title, 0);

        if ($topicSlug === '' || $topicSlug === '-0') {
            $topicSlug = 'topic-' . time();
        }

        $topic = new Topic();

        DB::connection()->beginTransaction();
        try {
            $topic->forum_id = $forum->id;
            $topic->user_id = $user->id;
            $topic->prefix_id = $pId;
            $topic->title = $title;
            $topic->slug = $topicSlug;
            $topic->is_private = $isPrivate ? 1 : 0;
            $topic->last_post_at = \now();

            if ($isScheduled) {
                $topic->scheduled_publish_at = $scheduledAt;
                $topic->status = Topic::STATUS_SCHEDULED;
            }

            $topic->forceFill(['type' => $dbType])->save();

            $topicId = $topic->id;
            $topic->slug = $sefService->generateSlugFromTitle($title, $topicId);

            if ($sefService->getMode() === \App\Services\TopicUrlService::MODE_RANDOM) {
                $topic->url_key = $sefService->generateUniqueUrlKey();
            }
            $topic->save();

            $bodyHtml = function_exists('core_body_to_html') ? core_body_to_html($body) : $body;
            $bodyHtml = function_exists('core_process_mentions') ? core_process_mentions($bodyHtml) : $bodyHtml;
            $bodyHtml = function_exists('core_process_post_refs') ? core_process_post_refs($bodyHtml, $topicId) : $bodyHtml;

            $post = clone new \App\Models\Post();
            $post->forceFill([
                'topic_id' => $topicId,
                'user_id' => $user->id,
                'body' => $body,
                'body_html' => $bodyHtml,
                'is_first_post' => 1
            ])->save();

            $firstPostId = $post->id;

            $topic->forceFill([
                'first_post_id' => $firstPostId,
                'last_post_id' => $firstPostId,
                'last_post_user_id' => $user->id,
                'last_post_at' => \now()
            ])->save();

            if (!$isScheduled) {
                $forum->increment('topic_count');
                $forum->increment('post_count');
                $forum->forceFill([
                    'last_post_id' => $firstPostId,
                    'last_post_user_id' => $user->id,
                    'last_post_at' => \now()
                ])->save();

                DB::table('forum_stats')->where('id', 1)->update([
                    'total_topics' => DB::raw('total_topics + 1'),
                    'total_posts' => DB::raw('total_posts + 1')
                ]);
            }

            if (!empty($attachmentIds)) {
                DB::table('attachments')
                    ->whereIn('id', $attachmentIds)
                    ->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNull('post_id')->orWhere('post_id', 0);
                    })
                    ->update(['post_id' => $firstPostId]);
            }

            // Ankete Dair (Poll)
            if (!empty($pollData['question']) && !empty($pollData['options']) && count($pollData['options']) >= 2) {
                $poll = \App\Models\Poll::create([
                    'topic_id' => $topicId,
                    'question' => mb_substr($pollData['question'], 0, 500),
                    'max_votes' => $pollData['max_votes'],
                    'allow_change_vote' => $pollData['allow_change_vote'],
                    'closes_at' => $pollData['closes_at'],
                    'created_at' => \now(),
                ]);
                $optionsData = [];
                foreach ($pollData['options'] as $i => $text) {
                    $optionsData[] = [
                        'option_text' => mb_substr($text, 0, 500),
                        'vote_count' => 0,
                        'sort_order' => $i,
                    ];
                }
                $poll->options()->createMany($optionsData);
            }

            // Etiketler (Tags)
            if (!empty($tagIds)) {
                $validIds = \App\Models\Tag::whereIn('id', $tagIds)->pluck('id')->toArray();
                if (!empty($validIds)) {
                    $pvt = [];
                    foreach ($validIds as $tid) {
                        $pvt[] = ['topic_id' => $topicId, 'tag_id' => (int) $tid];
                    }
                    DB::table('topic_tags')->insertOrIgnore($pvt);
                    \App\Models\Tag::whereIn('id', $validIds)->increment('use_count');
                }
            }

            // Private Viewers
            if ($isPrivate && !empty($viewerIds)) {
                $validViewers = \App\Models\User::whereIn('id', $viewerIds)->where('is_banned', 0)->pluck('id')->all();
                $topicUrl = function_exists('topic_url_path_by_id') && function_exists('core_url')
                            ? core_url('topic/' . topic_url_path_by_id($topicId))
                            : (function_exists('core_url') ? core_url('topic/' . $topicId) : '/topic/' . $topicId);
                $topicTitle = $topic->title ?? '';
                $fromUsername = $user->username ?? '';
                foreach ($validViewers as $vid) {
                    DB::table('topic_private_viewers')->insertOrIgnore([
                        'topic_id' => $topicId,
                        'user_id' => $vid,
                        'created_at' => \now(),
                    ]);
                    (new UserAlertService())->insert($vid, 'private_topic_added', [
                        'url' => $topicUrl,
                        'from_user_id' => (int) $user->id,
                        'from_username' => $fromUsername,
                        'topic_id' => $topicId,
                        'topic_title' => $topicTitle,
                    ]);
                }
            }

            // Otomatik Takip Etme Özelliği
            try {
                $pref = DB::table('user_preferences')
                    ->where('user_id', $user->id)
                    ->where('preference_key', 'follow_created_content')
                    ->first();
                if ($pref && ($pref->value ?? '') === '1') {
                    DB::table('topic_subscriptions')
                        ->insertOrIgnore(['user_id' => $user->id, 'topic_id' => $topicId, 'created_at' => \now()]);
                }
            } catch (\Throwable $e) {
            }

            DB::connection()->commit();

            // Events ve Cache Temizliği
            if ($this->app && !$isScheduled) {
                try {
                    $this->app->event()->dispatch(new \App\Events\TopicCreated($topic), \App\Events\TopicCreated::NAME);
                    $this->app->hooks()->doAction('after_topic_create', $topic, $forum);
                } catch (\Throwable $e) {
                }
                $this->app->cache()->delete('forum_stats');
                $this->app->cache()->delete('home_categories');
            }

            return $topic;

        } catch (\Throwable $e) {
            DB::connection()->rollBack();
            throw $e;
        }
    }
}
