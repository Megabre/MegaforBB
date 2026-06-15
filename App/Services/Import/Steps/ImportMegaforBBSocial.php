<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBSocial implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_social_pm');
    }

    public function key(): string
    {
        return 'social';
    }

    public function order(): int
    {
        return 70;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();
        $mapper->preload('user');
        $mapper->preload('post');
        $mapper->preload('topic');

        $this->importPostLikes($sourcePdo, $targetPdo, $mapper, $result);
        $this->importUserFollows($sourcePdo, $targetPdo, $mapper, $result);
        $this->importUserBlocks($sourcePdo, $targetPdo, $mapper, $result);
        $this->importUserBans($sourcePdo, $targetPdo, $mapper, $result);
        $this->importTopicSubscriptions($sourcePdo, $targetPdo, $mapper, $result);
        $this->importTopicReads($sourcePdo, $targetPdo, $mapper, $result);
        $this->importPostEdits($sourcePdo, $targetPdo, $mapper, $result);
        $this->importConversationsAndMessages($sourcePdo, $targetPdo, $mapper, $result);

        return $result;
    }

    private function tableExists(\PDO $pdo, string $table): bool
    {
        try {
            $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function importPostLikes(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'post_likes')) {
            return;
        }
        $rows = $sourcePdo->query("SELECT id, post_id, user_id, created_at FROM post_likes")->fetchAll(\PDO::FETCH_ASSOC);
        $insert = $targetPdo->prepare("INSERT IGNORE INTO post_likes (post_id, user_id, created_at) VALUES (?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            $postId = $mapper->get('post', (int) $row['post_id']);
            $userId = $mapper->get('user', (int) $row['user_id']);
            if ($postId === null || $userId === null) {
                $result->skipped++;
                continue;
            }
            try {
                $insert->execute([$postId, $userId, $row['created_at'] ?? date('Y-m-d H:i:s')]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importUserFollows(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'user_follows')) {
            return;
        }
        $rows = $sourcePdo->query("SELECT follower_id, following_id, created_at FROM user_follows")->fetchAll(\PDO::FETCH_ASSOC);
        $insert = $targetPdo->prepare("INSERT IGNORE INTO user_follows (follower_id, following_id, created_at) VALUES (?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            $fid = $mapper->get('user', (int) $row['follower_id']);
            $foid = $mapper->get('user', (int) $row['following_id']);
            if ($fid === null || $foid === null) {
                $result->skipped++;
                continue;
            }
            try {
                $insert->execute([$fid, $foid, $row['created_at'] ?? date('Y-m-d H:i:s')]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importUserBlocks(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'user_blocks')) {
            return;
        }
        $rows = $sourcePdo->query("SELECT user_id, blocked_user_id, created_at FROM user_blocks")->fetchAll(\PDO::FETCH_ASSOC);
        $insert = $targetPdo->prepare("INSERT IGNORE INTO user_blocks (user_id, blocked_user_id, created_at) VALUES (?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            $uid = $mapper->get('user', (int) $row['user_id']);
            $bid = $mapper->get('user', (int) $row['blocked_user_id']);
            if ($uid === null || $bid === null) {
                $result->skipped++;
                continue;
            }
            try {
                $insert->execute([$uid, $bid, $row['created_at'] ?? date('Y-m-d H:i:s')]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importUserBans(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'user_bans')) {
            return;
        }
        $rows = $sourcePdo->query("SELECT user_id, admin_id, reason, expires_at, created_at FROM user_bans")->fetchAll(\PDO::FETCH_ASSOC);
        $insert = $targetPdo->prepare("INSERT IGNORE INTO user_bans (user_id, admin_id, reason, expires_at, created_at) VALUES (?, ?, ?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            $uid = $mapper->get('user', (int) $row['user_id']);
            $adminId = $mapper->get('user', (int) $row['admin_id']);
            if ($uid === null || $adminId === null) {
                $result->skipped++;
                continue;
            }
            try {
                $insert->execute([
                    $uid,
                    $adminId,
                    $row['reason'] ?? null,
                    $row['expires_at'] ?? null,
                    $row['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importTopicSubscriptions(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'topic_subscriptions')) {
            return;
        }
        $rows = $sourcePdo->query("SELECT topic_id, user_id, created_at FROM topic_subscriptions")->fetchAll(\PDO::FETCH_ASSOC);
        $insert = $targetPdo->prepare("INSERT IGNORE INTO topic_subscriptions (topic_id, user_id, created_at) VALUES (?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            $tid = $mapper->get('topic', (int) $row['topic_id']);
            $uid = $mapper->get('user', (int) $row['user_id']);
            if ($tid === null || $uid === null) {
                $result->skipped++;
                continue;
            }
            try {
                $insert->execute([$tid, $uid, $row['created_at'] ?? date('Y-m-d H:i:s')]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importTopicReads(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'topic_reads')) {
            return;
        }
        $rows = $sourcePdo->query("SELECT user_id, topic_id, last_read_at FROM topic_reads")->fetchAll(\PDO::FETCH_ASSOC);
        $insert = $targetPdo->prepare("INSERT IGNORE INTO topic_reads (user_id, topic_id, last_read_at) VALUES (?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            $uid = $mapper->get('user', (int) $row['user_id']);
            $tid = $mapper->get('topic', (int) $row['topic_id']);
            if ($uid === null || $tid === null) {
                $result->skipped++;
                continue;
            }
            try {
                $insert->execute([$uid, $tid, $row['last_read_at'] ?? date('Y-m-d H:i:s')]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importPostEdits(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'post_edits')) {
            return;
        }
        $rows = $sourcePdo->query("SELECT post_id, user_id, old_body, edit_reason, created_at FROM post_edits")->fetchAll(\PDO::FETCH_ASSOC);
        $insert = $targetPdo->prepare("INSERT INTO post_edits (post_id, user_id, old_body, edit_reason, created_at) VALUES (?, ?, ?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            $postId = $mapper->get('post', (int) $row['post_id']);
            $userId = $mapper->get('user', (int) $row['user_id']);
            if ($postId === null || $userId === null) {
                $result->skipped++;
                continue;
            }
            try {
                $insert->execute([
                    $postId,
                    $userId,
                    $row['old_body'] ?? '',
                    $row['edit_reason'] ?? null,
                    $row['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importConversationsAndMessages(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'conversations')) {
            return;
        }
        $convRows = $sourcePdo->query("SELECT id, created_at FROM conversations ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $convInsert = $targetPdo->prepare("INSERT INTO conversations (created_at) VALUES (?)");
        foreach ($convRows as $row) {
            $result->total++;
            $oldConvId = (int) $row['id'];
            try {
                $convInsert->execute([$row['created_at'] ?? date('Y-m-d H:i:s')]);
                $newConvId = (int) $targetPdo->lastInsertId();
                $mapper->add('conversation', $oldConvId, $newConvId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }

        if ($this->tableExists($sourcePdo, 'conversation_user')) {
            $rows = $sourcePdo->query("SELECT conversation_id, user_id, last_read_at, created_at FROM conversation_user")->fetchAll(\PDO::FETCH_ASSOC);
            $insert = $targetPdo->prepare("INSERT IGNORE INTO conversation_user (conversation_id, user_id, last_read_at, created_at) VALUES (?, ?, ?, ?)");
            foreach ($rows as $row) {
                $result->total++;
                $convId = $mapper->get('conversation', (int) $row['conversation_id']);
                $uid = $mapper->get('user', (int) $row['user_id']);
                if ($convId === null || $uid === null) {
                    $result->skipped++;
                    continue;
                }
                try {
                    $insert->execute([
                        $convId,
                        $uid,
                        $row['last_read_at'] ?? null,
                        $row['created_at'] ?? date('Y-m-d H:i:s'),
                    ]);
                    $result->imported++;
                } catch (\Throwable $e) {
                    $result->errors++;
                }
            }
        }

        if ($this->tableExists($sourcePdo, 'private_messages')) {
            $rows = $sourcePdo->query("SELECT conversation_id, user_id, body, body_html, created_at FROM private_messages")->fetchAll(\PDO::FETCH_ASSOC);
            $insert = $targetPdo->prepare("INSERT INTO private_messages (conversation_id, user_id, body, body_html, created_at) VALUES (?, ?, ?, ?, ?)");
            foreach ($rows as $row) {
                $result->total++;
                $convId = $mapper->get('conversation', (int) $row['conversation_id']);
                $uid = $mapper->get('user', (int) $row['user_id']);
                if ($convId === null || $uid === null) {
                    $result->skipped++;
                    continue;
                }
                try {
                    $insert->execute([
                        $convId,
                        $uid,
                        $row['body'] ?? '',
                        $row['body_html'] ?? $row['body'] ?? '',
                        $row['created_at'] ?? date('Y-m-d H:i:s'),
                    ]);
                    $result->imported++;
                } catch (\Throwable $e) {
                    $result->errors++;
                }
            }
        }
    }
}
