<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Forum ve Konu istatistiklerini senkronize eder (Cotonti Sync Özelliği).
 */
class ForumSyncService
{
    /**
     * Konu istatistiklerini (cevap sayısı, son mesaj vb.) yeniden hesaplar.
     */
    public function syncTopic(int $topicId): void
    {
        $posts = Post::where('topic_id', $topicId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'user_id', 'created_at']);

        if ($posts->isEmpty()) {
            // Konu boşsa silinmeli veya sıfırlanmalı? Genelde silinmez, 0 yaparız.
            Topic::where('id', $topicId)->update([
                'reply_count' => 0,
                'first_post_id' => null,
                'last_post_id' => null,
                'last_post_at' => null,
                'last_post_user_id' => null,
            ]);
            return;
        }

        $firstPost = $posts->first();
        $lastPost = $posts->last();
        $replyCount = $posts->count() - 1; // İlk mesaj hariç

        Topic::where('id', $topicId)->update([
            'reply_count' => max(0, $replyCount),
            'first_post_id' => $firstPost->id,
            'last_post_id' => $lastPost->id,
            'last_post_at' => $lastPost->created_at,
            'last_post_user_id' => $lastPost->user_id,
        ]);
    }

    /**
     * Forum istatistiklerini (konu sayısı, mesaj sayısı, son mesaj) yeniden hesaplar.
     */
    public function syncForum(int $forumId): void
    {
        // 1. İstatistikleri topla
        // is_private, moved_to_topic_id olanları saymalı mıyız?
        // Genelde moved_to olanlar sayılmaz. Private olanlar sayılabilir ama son mesajda gizlenmeli.
        // Basitlik için hepsini sayıyoruz (yetki kontrolü view katmanında).

        $topicCount = Topic::published()->where('forum_id', $forumId)
            ->whereNull('moved_to_topic_id') // Taşınanları sayma
            ->count();

        // Post sayısı: Bu forumdaki konuların reply_count + 1 (ilk mesaj) toplamı
        $postCount = Topic::published()->where('forum_id', $forumId)
            ->whereNull('moved_to_topic_id')
            ->sum(DB::raw('reply_count + 1'));

        // 2. Son mesajı bul
        $lastTopic = Topic::published()->where('forum_id', $forumId)
            ->whereNull('moved_to_topic_id')
            ->where('is_private', 0) // Özel konuların son mesajı genelde gizlenir
            ->orderBy('last_post_at', 'desc')
            ->first();

        Forum::where('id', $forumId)->update([
            'topic_count' => $topicCount,
            'post_count' => $postCount ?? 0,
            'last_post_id' => $lastTopic ? $lastTopic->last_post_id : null,
            'last_post_at' => $lastTopic ? $lastTopic->last_post_at : null,
            'last_post_user_id' => $lastTopic ? $lastTopic->last_post_user_id : null,
        ]);
    }

    /**
     * Tüm forumları senkronize eder.
     */
    public function syncAll(): void
    {
        $forums = Forum::pluck('id');
        foreach ($forums as $id) {
            $this->syncForum($id);
        }
    }
}
