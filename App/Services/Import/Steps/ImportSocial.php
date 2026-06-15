<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Models\PostEdit;
use App\Models\PostLike;
use App\Models\TopicSubscription;
use App\Models\UserFollow;
use App\Services\Import\BBCodeConverter;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;
use Illuminate\Database\Capsule\Manager as DB;

class ImportSocial implements ImportStepInterface
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
        $this->importConversations($sourcePdo, $targetPdo, $mapper, $result);

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
        if (!$this->tableExists($sourcePdo, 'xf_reaction_content')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("
                SELECT content_id, reaction_user_id, reaction_date
                FROM xf_reaction_content
                WHERE content_type = 'post'
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $result->errors++;
            $result->errorMessages[] = 'Post likes query failed: ' . $e->getMessage();
            return;
        }

        foreach ($rows as $row) {
            $result->total++;
            try {
                $postId = $mapper->get('post', (int) $row['content_id']);
                $userId = $mapper->get('user', (int) $row['reaction_user_id']);
                if (!$postId || !$userId) {
                    $result->skipped++;
                    continue;
                }
                $createdAt = date('Y-m-d H:i:s', (int) $row['reaction_date']);
                PostLike::firstOrCreate(
                    ['post_id' => $postId, 'user_id' => $userId],
                    ['created_at' => $createdAt]
                );
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Like error (post_xf=' . $row['content_id'] . '): ' . $e->getMessage();
            }
        }
    }

    private function importUserFollows(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_user_follow')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("SELECT user_id, follow_user_id, follow_date FROM xf_user_follow")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['user_id']);
                $fid = $mapper->get('user', (int) $row['follow_user_id']);
                if (!$uid || !$fid) {
                    $result->skipped++;
                    continue;
                }
                $createdAt = date('Y-m-d H:i:s', (int) $row['follow_date']);
                UserFollow::firstOrCreate(
                    ['follower_id' => $uid, 'following_id' => $fid],
                    ['created_at' => $createdAt]
                );
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Follow error: ' . $e->getMessage();
            }
        }
    }

    private function importUserBlocks(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_user_ignored')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("SELECT user_id, ignored_user_id FROM xf_user_ignored")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        $stmt = $targetPdo->prepare("INSERT IGNORE INTO user_blocks (user_id, blocked_user_id, created_at) VALUES (?, ?, NOW())");

        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['user_id']);
                $bid = $mapper->get('user', (int) $row['ignored_user_id']);
                if (!$uid || !$bid) {
                    $result->skipped++;
                    continue;
                }
                $stmt->execute([$uid, $bid]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Block error: ' . $e->getMessage();
            }
        }
    }

    private function importUserBans(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_user_ban')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("
                SELECT user_id, ban_user_id, ban_date, end_date, user_reason
                FROM xf_user_ban
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        $stmt = $targetPdo->prepare("INSERT IGNORE INTO user_bans (user_id, admin_id, reason, expires_at, created_at) VALUES (?, ?, ?, ?, ?)");

        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['user_id']);
                if (!$uid) {
                    $result->skipped++;
                    continue;
                }
                $adminId = $mapper->get('user', (int) $row['ban_user_id']);
                if (!$adminId) {
                    $result->skipped++;
                    continue;
                }
                $endDate = ((int) $row['end_date'] > 0) ? date('Y-m-d H:i:s', (int) $row['end_date']) : null;
                $stmt->execute([
                    $uid,
                    $adminId,
                    $row['user_reason'] ?: null,
                    $endDate,
                    date('Y-m-d H:i:s', (int) $row['ban_date']),
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Ban error (user_xf=' . $row['user_id'] . '): ' . $e->getMessage();
            }
        }
    }

    private function importTopicSubscriptions(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_thread_watch')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("SELECT user_id, thread_id FROM xf_thread_watch")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['user_id']);
                $tid = $mapper->get('topic', (int) $row['thread_id']);
                if (!$uid || !$tid) {
                    $result->skipped++;
                    continue;
                }
                TopicSubscription::firstOrCreate(
                    ['user_id' => $uid, 'topic_id' => $tid],
                    ['created_at' => \now()]
                );
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Subscription error: ' . $e->getMessage();
            }
        }
    }

    private function importTopicReads(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_thread_read')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("SELECT user_id, thread_id, thread_read_date FROM xf_thread_read")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['user_id']);
                $tid = $mapper->get('topic', (int) $row['thread_id']);
                if (!$uid || !$tid) {
                    $result->skipped++;
                    continue;
                }
                $readAt = date('Y-m-d H:i:s', (int) $row['thread_read_date']);
                DB::table('topic_reads')->updateOrInsert(
                    ['user_id' => $uid, 'topic_id' => $tid],
                    ['last_read_at' => $readAt]
                );
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Topic read error: ' . $e->getMessage();
            }
        }
    }

    private function importPostEdits(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_edit_history')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("
                SELECT content_id, edit_user_id, edit_date, old_text
                FROM xf_edit_history
                WHERE content_type = 'post'
                ORDER BY edit_date ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($rows as $row) {
            $result->total++;
            try {
                $postId = $mapper->get('post', (int) $row['content_id']);
                if (!$postId) {
                    $result->skipped++;
                    continue;
                }
                $userId = $mapper->get('user', (int) $row['edit_user_id']) ?? 0;
                if ($userId === 0) {
                    $result->skipped++;
                    continue;
                }
                $oldBody = BBCodeConverter::convert($row['old_text'] ?? '');
                $createdAt = date('Y-m-d H:i:s', (int) $row['edit_date']);
                PostEdit::create([
                    'post_id' => $postId,
                    'user_id' => $userId,
                    'old_body' => $oldBody,
                    'created_at' => $createdAt,
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Edit history error (post_xf=' . $row['content_id'] . '): ' . $e->getMessage();
            }
        }
    }

    private function importConversations(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_conversation_master')) {
            return;
        }

        try {
            $convRows = $sourcePdo->query("
                SELECT conversation_id, title, user_id, start_date,
                       last_message_date, last_message_user_id
                FROM xf_conversation_master
                ORDER BY conversation_id ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $result->errors++;
            $result->errorMessages[] = 'Conversations query failed: ' . $e->getMessage();
            return;
        }

        $insertConv = $targetPdo->prepare("
            INSERT INTO conversations (created_at) VALUES (?)
        ");

        foreach ($convRows as $row) {
            $result->total++;
            try {
                $creatorId = $mapper->get('user', (int) $row['user_id']);
                if (!$creatorId) {
                    $result->skipped++;
                    continue;
                }
                $createdAt = date('Y-m-d H:i:s', (int) $row['start_date']);

                $insertConv->execute([$createdAt]);
                $newConvId = (int) $targetPdo->lastInsertId();
                $mapper->add('conversation', (int) $row['conversation_id'], $newConvId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Conversation error (xf=' . $row['conversation_id'] . '): ' . $e->getMessage();
            }
        }

        $mapper->preload('conversation');
        $this->importConversationParticipants($sourcePdo, $targetPdo, $mapper, $result);
        $this->importConversationMessages($sourcePdo, $targetPdo, $mapper, $result);
    }

    private function importConversationParticipants(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_conversation_recipient')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("
                SELECT conversation_id, user_id, recipient_state, last_read_date
                FROM xf_conversation_recipient
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        $stmt = $targetPdo->prepare("
            INSERT IGNORE INTO conversation_user (conversation_id, user_id, last_read_at, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        foreach ($rows as $row) {
            $result->total++;
            try {
                $convId = $mapper->get('conversation', (int) $row['conversation_id']);
                $userId = $mapper->get('user', (int) $row['user_id']);
                if (!$convId || !$userId) {
                    $result->skipped++;
                    continue;
                }
                $lastRead = ((int) $row['last_read_date'] > 0)
                    ? date('Y-m-d H:i:s', (int) $row['last_read_date'])
                    : null;
                $stmt->execute([$convId, $userId, $lastRead]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Participant error: ' . $e->getMessage();
            }
        }
    }

    private function importConversationMessages(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'xf_conversation_message')) {
            return;
        }

        try {
            $rows = $sourcePdo->query("
                SELECT message_id, conversation_id, user_id, message_date, message
                FROM xf_conversation_message
                ORDER BY message_id ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        $stmt = $targetPdo->prepare("
            INSERT INTO private_messages (conversation_id, user_id, body, created_at)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            $result->total++;
            try {
                $convId = $mapper->get('conversation', (int) $row['conversation_id']);
                $userId = $mapper->get('user', (int) $row['user_id']);
                if (!$convId) {
                    $result->skipped++;
                    continue;
                }
                $body = BBCodeConverter::convert($row['message'] ?? '');
                $stmt->execute([
                    $convId,
                    $userId,
                    $body,
                    date('Y-m-d H:i:s', (int) $row['message_date']),
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'PM error (xf_msg=' . $row['message_id'] . '): ' . $e->getMessage();
            }
        }
    }
}
