<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMyBBTopics implements ImportStepInterface
{
    private const TURKISH_MAP = [
        'ı' => 'i', 'ş' => 's', 'ç' => 'c', 'ğ' => 'g', 'ö' => 'o', 'ü' => 'u',
        'İ' => 'i', 'Ş' => 's', 'Ç' => 'c', 'Ğ' => 'g', 'Ö' => 'o', 'Ü' => 'u',
    ];

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
            SELECT tid, fid, subject, uid, username, dateline, firstpost, lastpost,
                   lastposter, lastposteruid, replies, views, closed, sticky, visible
            FROM mybb_threads
            ORDER BY tid ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total = count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $insert = $targetPdo->prepare("
            INSERT INTO topics
                (forum_id, user_id, title, slug, type, is_sticky, is_locked,
                 reply_count, view_count, first_post_id, last_post_id,
                 last_post_at, last_post_user_id, created_at, updated_at, deleted_at)
            VALUES (?, ?, ?, ?, 'topic', ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?)
        ");
        $findTopicBySlug = $targetPdo->prepare('SELECT id FROM topics WHERE forum_id = ? AND slug = ? LIMIT 1');

        foreach ($rows as $row) {
            try {
                $tid = (int) $row['tid'];
                $fid = (int) $row['fid'];
                $forumId = $mapper->get('forum', $fid);
                if ($forumId === null) {
                    $result->skipped++;
                    continue;
                }

                $slug = $this->slugify($row['subject'], $tid);
                if ($mergeMode) {
                    $findTopicBySlug->execute([$forumId, $slug]);
                    $existing = $findTopicBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $existingId = (int) $existing['id'];
                        $mapper->add('topic', $tid, $existingId);
                        $mapper->add('topic_first_post', $tid, (int) $row['firstpost']);
                        $mapper->add('topic_last_post', $tid, (int) $row['lastpost']);
                        $result->skipped++;
                        continue;
                    }
                }

                $userId = $mapper->get('user', (int) $row['uid']) ?? 0;
                $isSticky = (int) $row['sticky'];
                $isLocked = ($row['closed'] !== '' && $row['closed'] !== '0') ? 1 : 0;
                $createdAt = date('Y-m-d H:i:s', (int) $row['dateline']);
                $lastPostAt = (int) $row['lastpost'] > 0
                    ? date('Y-m-d H:i:s', (int) $row['lastpost'])
                    : $createdAt;
                $lastPostUserId = (int) $row['lastposteruid'] > 0
                    ? $mapper->get('user', (int) $row['lastposteruid'])
                    : null;
                $deletedAt = ((int) $row['visible'] === 0) ? $createdAt : null;

                $insert->execute([
                    $forumId,
                    $userId,
                    $row['subject'],
                    $slug,
                    $isSticky,
                    $isLocked,
                    (int) $row['replies'],
                    (int) $row['views'],
                    $lastPostAt,
                    $lastPostUserId,
                    $createdAt,
                    $createdAt,
                    $deletedAt,
                ]);
                $newTopicId = (int) $targetPdo->lastInsertId();
                $mapper->add('topic', $tid, $newTopicId);
                $mapper->add('topic_first_post', $tid, (int) $row['firstpost']);
                $mapper->add('topic_last_post', $tid, (int) $row['lastpost']);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Topic #{$row['tid']} ({$row['subject']}): {$e->getMessage()}";
            }
        }

        return $result;
    }

    private function slugify(string $title, int $tid): string
    {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = strtr($slug, self::TURKISH_MAP);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug !== '' ? $slug : 'topic-' . $tid;
    }
}
