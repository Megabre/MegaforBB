<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\BBCodeConverter;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMyBBPosts implements ImportStepInterface
{
    private const BATCH_SIZE = 500;

    public function name(): string
    {
        return lang('admin.import.step_posts');
    }

    public function key(): string
    {
        return 'posts';
    }

    public function order(): int
    {
        return 50;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();
        $mapper->preload('topic');
        $mapper->preload('user');

        $countRow = $sourcePdo->query('SELECT COUNT(*) FROM mybb_posts')->fetch(\PDO::FETCH_NUM);
        $result->total = (int) ($countRow[0] ?? 0);

        $insert = $targetPdo->prepare("
            INSERT INTO posts
                (topic_id, user_id, body, body_html, like_count, is_first_post,
                 created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $offset = 0;
        while (true) {
            $stmt = $sourcePdo->query(
                "
                SELECT pid, tid, uid, username, dateline, message, visible, replyto
                FROM mybb_posts
                ORDER BY pid ASC
                LIMIT " . self::BATCH_SIZE . " OFFSET " . $offset
            );
            $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                try {
                    $pid = (int) $row['pid'];
                    $tid = (int) $row['tid'];
                    $topicId = $mapper->get('topic', $tid);
                    if ($topicId === null) {
                        $result->skipped++;
                        continue;
                    }
                    $userId = $mapper->get('user', (int) $row['uid']);
                    if ($userId === null) {
                        $result->skipped++;
                        continue;
                    }
                    $html = BBCodeConverter::convertMyCode($row['message']);
                    $firstPostId = $mapper->get('topic_first_post', $tid);
                    $isFirstPost = ($firstPostId !== null && (int) $row['pid'] === $firstPostId) ? 1 : 0;
                    $createdAt = date('Y-m-d H:i:s', (int) $row['dateline']);

                    $insert->execute([
                        $topicId,
                        $userId,
                        $html,
                        $html,
                        0,
                        $isFirstPost,
                        $createdAt,
                        $createdAt,
                    ]);
                    $newPostId = (int) $targetPdo->lastInsertId();
                    $mapper->add('post', $pid, $newPostId);
                    $result->imported++;
                } catch (\Throwable $e) {
                    $result->errors++;
                    $result->errorMessages[] = "Post #{$row['pid']} (thread #{$row['tid']}): {$e->getMessage()}";
                }
            }
            $offset += self::BATCH_SIZE;
        }

        $this->updateTopicPostIds($targetPdo, $mapper);
        return $result;
    }

    private function updateTopicPostIds(\PDO $targetPdo, IdMapper $mapper): void
    {
        $mapper->preload('post');
        $mapper->preload('topic_first_post');
        $mapper->preload('topic_last_post');

        $st = $targetPdo->query("
            SELECT old_id, new_id FROM import_id_map
            WHERE entity_type = 'topic'
        ");
        if (!$st) {
            return;
        }
        $update = $targetPdo->prepare('UPDATE topics SET first_post_id = ?, last_post_id = ? WHERE id = ?');
        while ($row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $oldTopicId = (int) $row['old_id'];
            $newTopicId = (int) $row['new_id'];
            $oldFirstPostId = $mapper->get('topic_first_post', $oldTopicId);
            $newFirstPostId = $oldFirstPostId !== null ? $mapper->get('post', $oldFirstPostId) : null;
            $oldLastPostId = $mapper->get('topic_last_post', $oldTopicId);
            $newLastPostId = $oldLastPostId !== null ? $mapper->get('post', $oldLastPostId) : null;
            $update->execute([$newFirstPostId, $newLastPostId, $newTopicId]);
        }
    }
}
