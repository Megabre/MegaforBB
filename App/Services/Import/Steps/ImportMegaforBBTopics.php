<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBTopics implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_topics');
    }

    public function key(): string
    {
        return 'topics';
    }

    public function order(): int
    {
        return 40;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();
        $mapper->preload('forum');
        $mapper->preload('user');

        $rows = $sourcePdo->query("
            SELECT id, moved_to_topic_id, forum_id, user_id, prefix_id, title, slug, type,
                   is_sticky, is_locked, is_private, is_solved, accepted_post_id,
                   reply_count, view_count, first_post_id, last_post_id, last_post_at, last_post_user_id,
                   created_at, updated_at, deleted_at, deleted_by
            FROM topics
            ORDER BY id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total = count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $findBySlug = $targetPdo->prepare('SELECT id FROM topics WHERE forum_id = ? AND slug = ? LIMIT 1');

        $insert = $targetPdo->prepare("
            INSERT INTO topics
                (forum_id, user_id, prefix_id, title, slug, type, is_sticky, is_locked, is_private, is_solved,
                 accepted_post_id, reply_count, view_count, first_post_id, last_post_id, last_post_at, last_post_user_id,
                 created_at, updated_at, deleted_at, deleted_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $oldId = (int) $row['id'];
                $forumId = $mapper->get('forum', (int) $row['forum_id']);
                if ($forumId === null) {
                    $result->errors++;
                    $result->errorMessages[] = "Topic #{$oldId}: forum not mapped";
                    continue;
                }
                $userId = $mapper->get('user', (int) $row['user_id']);
                if ($userId === null) {
                    $userId = 1;
                }
                $prefixId = null;
                if (!empty($row['prefix_id'])) {
                    $prefixId = $mapper->get('prefix', (int) $row['prefix_id']);
                }
                $lastPostUserId = null;
                if (!empty($row['last_post_user_id'])) {
                    $lastPostUserId = $mapper->get('user', (int) $row['last_post_user_id']);
                }
                $deletedBy = null;
                if (!empty($row['deleted_by'])) {
                    $deletedBy = $mapper->get('user', (int) $row['deleted_by']);
                }
                $acceptedPostId = null;
                if (!empty($row['accepted_post_id'])) {
                    $mapper->add('topic_accepted_post', $oldId, (int) $row['accepted_post_id']);
                }

                if ($mergeMode) {
                    $findBySlug->execute([$forumId, $row['slug']]);
                    $existing = $findBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $mapper->add('topic', $oldId, (int) $existing['id']);
                        $mapper->add('topic_first_post', $oldId, (int) $row['first_post_id']);
                        $mapper->add('topic_last_post', $oldId, (int) $row['last_post_id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $insert->execute([
                    $forumId,
                    $userId,
                    $prefixId,
                    $row['title'],
                    $row['slug'],
                    $row['type'] ?? 'topic',
                    (int) ($row['is_sticky'] ?? 0),
                    (int) ($row['is_locked'] ?? 0),
                    (int) ($row['is_private'] ?? 0),
                    (int) ($row['is_solved'] ?? 0),
                    $acceptedPostId,
                    (int) ($row['reply_count'] ?? 0),
                    (int) ($row['view_count'] ?? 0),
                    null,
                    null,
                    $row['last_post_at'] ?? null,
                    $lastPostUserId,
                    $row['created_at'],
                    $row['updated_at'] ?? $row['created_at'],
                    $row['deleted_at'] ?? null,
                    $deletedBy,
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('topic', $oldId, $newId);
                $mapper->add('topic_first_post', $oldId, (int) $row['first_post_id']);
                $mapper->add('topic_last_post', $oldId, (int) $row['last_post_id']);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Topic #{$row['id']} ({$row['title']}): {$e->getMessage()}";
            }
        }

        return $result;
    }
}
