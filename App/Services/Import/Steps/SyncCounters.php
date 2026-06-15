<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class SyncCounters implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_sync_counters');
    }

    public function key(): string
    {
        return 'sync_counters';
    }

    public function order(): int
    {
        return 80;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();
        $pdo = $targetPdo;

        $ops = [
            'forum_topic_post_count' => "
                UPDATE forums f SET
                    topic_count = (SELECT COUNT(*) FROM topics WHERE forum_id = f.id AND deleted_at IS NULL),
                    post_count  = (SELECT COUNT(*) FROM posts p
                                   INNER JOIN topics t ON t.id = p.topic_id
                                   WHERE t.forum_id = f.id AND p.deleted_at IS NULL AND t.deleted_at IS NULL)
            ",
            'forum_last_post' => "
                UPDATE forums f SET
                    last_post_id = (
                        SELECT p.id FROM posts p INNER JOIN topics t ON t.id = p.topic_id
                        WHERE t.forum_id = f.id AND p.deleted_at IS NULL AND t.deleted_at IS NULL
                        ORDER BY p.created_at DESC LIMIT 1
                    ),
                    last_post_at = (
                        SELECT p.created_at FROM posts p INNER JOIN topics t ON t.id = p.topic_id
                        WHERE t.forum_id = f.id AND p.deleted_at IS NULL AND t.deleted_at IS NULL
                        ORDER BY p.created_at DESC LIMIT 1
                    ),
                    last_post_user_id = (
                        SELECT p.user_id FROM posts p INNER JOIN topics t ON t.id = p.topic_id
                        WHERE t.forum_id = f.id AND p.deleted_at IS NULL AND t.deleted_at IS NULL
                        ORDER BY p.created_at DESC LIMIT 1
                    )
            ",
            'topic_reply_count' => "
                UPDATE topics t SET
                    reply_count = GREATEST(0,
                        (SELECT COUNT(*) FROM posts WHERE topic_id = t.id AND deleted_at IS NULL) - 1
                    )
                WHERE t.deleted_at IS NULL
            ",
            'topic_first_post' => "
                UPDATE topics t SET
                    first_post_id = (SELECT MIN(id) FROM posts WHERE topic_id = t.id AND deleted_at IS NULL)
                WHERE t.first_post_id IS NULL AND t.deleted_at IS NULL
            ",
            'topic_last_post' => "
                UPDATE topics t SET
                    last_post_id = (SELECT id FROM posts WHERE topic_id = t.id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1),
                    last_post_at = (SELECT created_at FROM posts WHERE topic_id = t.id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1),
                    last_post_user_id = (SELECT user_id FROM posts WHERE topic_id = t.id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1)
                WHERE t.deleted_at IS NULL
            ",
            'post_like_count' => "
                UPDATE posts p SET
                    like_count = (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id)
            ",
            'forum_stats_totals' => "
                UPDATE forum_stats SET
                    total_topics  = (SELECT COUNT(*) FROM topics WHERE deleted_at IS NULL),
                    total_posts   = (SELECT COUNT(*) FROM posts WHERE deleted_at IS NULL),
                    total_members = (SELECT COUNT(*) FROM users WHERE is_banned = 0)
                WHERE id = 1
            ",
            'forum_stats_last_member' => "
                UPDATE forum_stats SET
                    last_member_id       = (SELECT id FROM users WHERE is_banned = 0 ORDER BY id DESC LIMIT 1),
                    last_member_username = (SELECT username FROM users WHERE is_banned = 0 ORDER BY id DESC LIMIT 1)
                WHERE id = 1
            ",
            'article_categories' => "
                UPDATE categories c
                INNER JOIN forums f ON f.category_id = c.id
                INNER JOIN topics t ON t.forum_id = f.id AND t.type = 'article' AND (t.deleted_at IS NULL)
                SET c.is_article_category = 1
            ",
        ];

        $result->total = count($ops);

        foreach ($ops as $label => $sql) {
            try {
                $pdo->exec($sql);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = $label . ': ' . $e->getMessage();
            }
        }

        return $result;
    }
}
