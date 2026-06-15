<?php

declare(strict_types=1);

/**
 * Planlanan konuları yayınlar (scheduled_publish_at <= now).
 * Kullanıcı banlı, hesabı kapatılmış veya askıda ise konu yayınlanmaz, iptal edilir.
 * public/cron.php tarafından dahil edilir; $basePath ve app() tanımlı olmalı.
 */

use App\Models\Topic;
use App\Events\TopicCreated;
use Illuminate\Database\Capsule\Manager as DB;

$timezone = function_exists('core_config') ? (string) core_config('app.timezone', 'UTC') : 'UTC';
$now = \Carbon\Carbon::now($timezone)->format('Y-m-d H:i:s');
$topics = Topic::with(['user', 'forum'])
    ->where('status', Topic::STATUS_SCHEDULED)
    ->where('scheduled_publish_at', '<=', $now)
    ->orderBy('scheduled_publish_at')
    ->get();

$published = 0;
$cancelled = 0;

foreach ($topics as $topic) {
    $cancel = false;

    if (!$topic->user) {
        $cancel = true;
    } elseif ($topic->user->is_banned) {
        $cancel = true;
    } elseif (!empty($topic->user->closed_at)) {
        $cancel = true;
    } elseif (!empty($topic->user->is_suspended)) {
        $cancel = true;
    } elseif (!$topic->forum) {
        $cancel = true;
    }

    if ($cancel) {
        $topic->status = Topic::STATUS_CANCELLED;
        $topic->scheduled_publish_at = null;
        $topic->save();
        $cancelled++;
        continue;
    }

    try {
        DB::connection()->beginTransaction();

        $topic->status = Topic::STATUS_PUBLISHED;
        $topic->scheduled_publish_at = null;
        $topic->save();

        $forum = $topic->forum;
        $forum->increment('topic_count');
        $forum->increment('post_count');
        $forum->forceFill([
            'last_post_id' => $topic->last_post_id,
            'last_post_user_id' => $topic->last_post_user_id,
            'last_post_at' => $topic->last_post_at,
        ])->save();

        DB::table('forum_stats')->where('id', 1)->update([
            'total_topics' => DB::raw('total_topics + 1'),
            'total_posts' => DB::raw('total_posts + 1'),
        ]);

        $app = function_exists('app') ? app() : null;
        if ($app) {
            $app->cache()->delete('forum_stats');
            $app->cache()->delete('home_categories');
            $app->event()->dispatch(new TopicCreated($topic), TopicCreated::NAME);
            try {
                $app->hooks()->doAction('after_topic_create', $topic, $forum);
            } catch (\Throwable $e) {
                // Eklenti hatası cron akışını bozmasın
            }
        }

        DB::connection()->commit();
        $published++;
    } catch (\Throwable $e) {
        if (DB::connection()->transactionLevel() > 0) {
            DB::connection()->rollBack();
        }
    }
}

$reports[] = '0. Planlanan konular: ' . $published . ' yayinlandi, ' . $cancelled . ' iptal.';
