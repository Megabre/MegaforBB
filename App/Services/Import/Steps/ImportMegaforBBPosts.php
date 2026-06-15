<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBPosts implements ImportStepInterface
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
        $mapper->preload('post');

        $countRow = $sourcePdo->query('SELECT COUNT(*) FROM posts')->fetch(\PDO::FETCH_NUM);
        $result->total = (int) ($countRow[0] ?? 0);

        $insert = $targetPdo->prepare("
            INSERT INTO posts
                (topic_id, user_id, body, body_html, like_count, net_votes, is_first_post,
                 created_at, updated_at, edited_at, edited_by, edit_count, deleted_at, deleted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $offset = 0;
        while (true) {
            $stmt = $sourcePdo->prepare("
                SELECT id, topic_id, user_id, body, body_html, like_count, net_votes, is_first_post,
                       created_at, updated_at, edited_at, edited_by, edit_count, deleted_at, deleted_by
                FROM posts
                ORDER BY id ASC
                LIMIT " . self::BATCH_SIZE . " OFFSET " . $offset . "
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                try {
                    $oldPostId = (int) $row['id'];
                    $oldTopicId = (int) $row['topic_id'];

                    // Adım tekrar çalıştırıldıysa bu mesaj zaten eşlendi; tekrar ekleme (çift import önleme)
                    if ($mapper->get('post', $oldPostId) !== null) {
                        $result->skipped++;
                        continue;
                    }

                    $topicId = $mapper->get('topic', $oldTopicId);
                    if ($topicId === null) {
                        $result->skipped++;
                        continue;
                    }

                    $userId = $mapper->get('user', (int) $row['user_id']);
                    if ($userId === null) {
                        $userId = 1;
                    }
                    $editedBy = null;
                    if (!empty($row['edited_by'])) {
                        $editedBy = $mapper->get('user', (int) $row['edited_by']);
                    }
                    $deletedBy = null;
                    if (!empty($row['deleted_by'])) {
                        $deletedBy = $mapper->get('user', (int) $row['deleted_by']);
                    }

                    $body = $row['body'] ?? '';
                    $bodyHtml = $row['body_html'] ?? $body;

                    $insert->execute([
                        $topicId,
                        $userId,
                        $body,
                        $bodyHtml,
                        (int) ($row['like_count'] ?? 0),
                        (int) ($row['net_votes'] ?? 0),
                        (int) ($row['is_first_post'] ?? 0),
                        $row['created_at'],
                        $row['updated_at'] ?? $row['created_at'],
                        $row['edited_at'] ?? null,
                        $editedBy,
                        (int) ($row['edit_count'] ?? 0),
                        $row['deleted_at'] ?? null,
                        $deletedBy,
                    ]);
                    $newPostId = (int) $targetPdo->lastInsertId();
                    $mapper->add('post', $oldPostId, $newPostId);
                    $result->imported++;
                } catch (\Throwable $e) {
                    $result->errors++;
                    $result->errorMessages[] = "Post #{$row['id']} (topic #{$row['topic_id']}): {$e->getMessage()}";
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

        $st = $targetPdo->prepare("
            SELECT old_id, new_id FROM import_id_map
            WHERE source = 'megaforbb' AND entity_type = 'topic'
        ");
        $st->execute();
        $topicRows = $st->fetchAll(\PDO::FETCH_ASSOC);

        $stFirst = $targetPdo->prepare("
            SELECT old_id, new_id FROM import_id_map
            WHERE source = 'megaforbb' AND entity_type = 'topic_first_post'
        ");
        $stFirst->execute();
        $firstMap = [];
        while ($r = $stFirst->fetch(\PDO::FETCH_ASSOC)) {
            $firstMap[(int) $r['old_id']] = (int) $r['new_id'];
        }

        $stLast = $targetPdo->prepare("
            SELECT old_id, new_id FROM import_id_map
            WHERE source = 'megaforbb' AND entity_type = 'topic_last_post'
        ");
        $stLast->execute();
        $lastMap = [];
        while ($r = $stLast->fetch(\PDO::FETCH_ASSOC)) {
            $lastMap[(int) $r['old_id']] = (int) $r['new_id'];
        }

        $stAccepted = $targetPdo->prepare("
            SELECT old_id, new_id FROM import_id_map
            WHERE source = 'megaforbb' AND entity_type = 'topic_accepted_post'
        ");
        $stAccepted->execute();
        $acceptedMap = [];
        while ($r = $stAccepted->fetch(\PDO::FETCH_ASSOC)) {
            $acceptedMap[(int) $r['old_id']] = (int) $r['new_id'];
        }

        $update = $targetPdo->prepare('UPDATE topics SET first_post_id = ?, last_post_id = ?, accepted_post_id = ? WHERE id = ?');

        foreach ($topicRows as $row) {
            $oldTopicId = (int) $row['old_id'];
            $newTopicId = (int) $row['new_id'];

            $oldFirstPostId = $firstMap[$oldTopicId] ?? null;
            $newFirstPostId = $oldFirstPostId !== null ? $mapper->get('post', $oldFirstPostId) : null;

            $oldLastPostId = $lastMap[$oldTopicId] ?? null;
            $newLastPostId = $oldLastPostId !== null ? $mapper->get('post', $oldLastPostId) : null;

            $oldAcceptedPostId = $acceptedMap[$oldTopicId] ?? null;
            $newAcceptedPostId = $oldAcceptedPostId !== null ? $mapper->get('post', $oldAcceptedPostId) : null;

            $update->execute([$newFirstPostId, $newLastPostId, $newAcceptedPostId, $newTopicId]);
        }
    }
}
