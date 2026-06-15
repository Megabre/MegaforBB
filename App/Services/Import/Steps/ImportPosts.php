<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\BBCodeConverter;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportPosts implements ImportStepInterface
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

        $ipMap = $this->buildIpMap($sourcePdo);
        $this->importPostRows($sourcePdo, $targetPdo, $mapper, $ipMap, $result);
        $this->updateTopicPostIds($targetPdo, $mapper);

        return $result;
    }

    private function buildIpMap(\PDO $sourcePdo): array
    {
        $ipMap = [];
        $st = $sourcePdo->query('SELECT ip_id, ip FROM xf_ip');
        while ($st && $row = $st->fetch(\PDO::FETCH_ASSOC)) {
            $converted = @inet_ntop($row['ip']);
            $ipMap[(int) $row['ip_id']] = $converted !== false ? $converted : null;
        }
        return $ipMap;
    }

    private function importPostRows(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $ipMap, StepResult $result): void
    {
        try {
            $countRow = $sourcePdo->query('SELECT COUNT(*) FROM xf_post')->fetch(\PDO::FETCH_NUM);
            $result->total = (int) ($countRow[0] ?? 0);
        } catch (\Throwable $e) {
            $result->errors++;
            $result->errorMessages[] = 'Mesaj sayısı alınamadı (xf_post): ' . $e->getMessage();
            return;
        }

        if ($result->total === 0) {
            $result->errorMessages[] = 'XenForo veritabanında mesaj (xf_post) kaydı yok. Önce konuların import edildiğinden emin olun.';
        }

        $insert = $targetPdo->prepare("
            INSERT INTO posts
                (topic_id, user_id, body, body_html, like_count, is_first_post,
                 created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $offset = 0;
        $batchSize = self::BATCH_SIZE;

        while (true) {
            $limit = $batchSize;
            $selectSql = "
                SELECT post_id, thread_id, user_id, username, post_date, message,
                       ip_id, message_state, position, reaction_score,
                       last_edit_date, last_edit_user_id, edit_count
                FROM xf_post
                ORDER BY post_id ASC
                LIMIT " . (int) $limit . " OFFSET " . (int) $offset . "
            ";
            $stmt = $sourcePdo->query($selectSql);
            $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                try {
                    $postId = (int) $row['post_id'];
                    $threadId = (int) $row['thread_id'];

                    $topicId = $mapper->get('topic', $threadId);
                    if ($topicId === null) {
                        $result->skipped++;
                        continue;
                    }

                    $userId = $mapper->get('user', (int) $row['user_id']);
                    $html = BBCodeConverter::convert($row['message']);
                    $isFirstPost = ((int) $row['position'] === 0) ? 1 : 0;
                    $createdAt = date('Y-m-d H:i:s', (int) $row['post_date']);
                    $likeCount = max(0, (int) $row['reaction_score']);

                    $insert->execute([
                        $topicId,
                        $userId,
                        $html,
                        $html,
                        $likeCount,
                        $isFirstPost,
                        $createdAt,
                        $createdAt,
                    ]);

                    $newPostId = (int) $targetPdo->lastInsertId();
                    $mapper->add('post', $postId, $newPostId);
                    $result->imported++;
                } catch (\Throwable $e) {
                    $result->errors++;
                    $result->errorMessages[] = "Post #{$row['post_id']} (thread #{$row['thread_id']}): {$e->getMessage()}";
                }
            }

            $offset += $batchSize;
        }
    }

    private function updateTopicPostIds(\PDO $targetPdo, IdMapper $mapper): void
    {
        $mapper->preload('post');
        $mapper->preload('topic_first_post');
        $mapper->preload('topic_last_post');

        $source = $mapper->getSource();
        $stTopic = $targetPdo->prepare("
            SELECT old_id, new_id FROM import_id_map
            WHERE source = ? AND entity_type = 'topic'
        ");
        $stTopic->execute([$source]);
        $topicRows = $stTopic->fetchAll(\PDO::FETCH_ASSOC);

        $update = $targetPdo->prepare('UPDATE topics SET first_post_id = ?, last_post_id = ? WHERE id = ?');

        foreach ($topicRows as $row) {
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
