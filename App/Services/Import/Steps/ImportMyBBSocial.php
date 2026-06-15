<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Models\PostLike;
use App\Models\TopicSubscription;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;
use Illuminate\Database\Capsule\Manager as DB;

class ImportMyBBSocial implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_social');
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
        $mapper->preload('topic');
        $mapper->preload('post');

        $this->importUserBans($sourcePdo, $targetPdo, $mapper, $result);
        $this->importUserFollows($sourcePdo, $targetPdo, $mapper, $result);
        $this->importTopicSubscriptions($sourcePdo, $targetPdo, $mapper, $result);
        $this->importTopicReads($sourcePdo, $targetPdo, $mapper, $result);
        $this->importPostLikes($sourcePdo, $targetPdo, $mapper, $result);

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

    private function importUserBans(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'mybb_banned')) {
            return;
        }
        try {
            $rows = $sourcePdo->query("SELECT uid, admin, dateline, bantime, reason, lifted FROM mybb_banned")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        $stmt = $targetPdo->prepare("INSERT IGNORE INTO user_bans (user_id, admin_id, reason, expires_at, created_at) VALUES (?, ?, ?, ?, ?)");
        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['uid']);
                if (!$uid) {
                    $result->skipped++;
                    continue;
                }
                $adminId = $mapper->get('user', (int) $row['admin']);
                if (!$adminId) {
                    $result->skipped++;
                    continue;
                }
                $expiresAt = (int) $row['lifted'] > 0 ? date('Y-m-d H:i:s', (int) $row['lifted']) : null;
                $stmt->execute([
                    $uid,
                    $adminId,
                    $row['reason'] ?: null,
                    $expiresAt,
                    date('Y-m-d H:i:s', (int) $row['dateline']),
                ]);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = 'Ban error (uid=' . $row['uid'] . '): ' . $e->getMessage();
            }
        }
    }

    /**
     * Parse MyBB buddylist (e.g. "1,2,3" or "1**$%%$2**$%%$3")
     */
    private function parseBuddylist(string $buddylist): array
    {
        if (trim($buddylist) === '') {
            return [];
        }
        if (strpos($buddylist, '**$%%$') !== false) {
            $parts = explode('**$%%$', $buddylist);
        } else {
            $parts = preg_split('/\s*,\s*/', $buddylist);
        }
        $uids = [];
        foreach ($parts as $p) {
            $p = (int) trim($p, " \t\n\r\0\x0B$");
            if ($p > 0) {
                $uids[] = $p;
            }
        }
        return $uids;
    }

    private function importUserFollows(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        try {
            $rows = $sourcePdo->query("SELECT uid, buddylist FROM mybb_users WHERE uid > 0 AND buddylist != '' AND buddylist IS NOT NULL")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }

        $stmt = $targetPdo->prepare("INSERT IGNORE INTO user_follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())");
        foreach ($rows as $row) {
            $followerId = $mapper->get('user', (int) $row['uid']);
            if (!$followerId) {
                continue;
            }
            $buddies = $this->parseBuddylist($row['buddylist']);
            foreach ($buddies as $buddyUid) {
                $result->total++;
                $followingId = $mapper->get('user', $buddyUid);
                if (!$followingId || $followingId == $followerId) {
                    $result->skipped++;
                    continue;
                }
                try {
                    $stmt->execute([$followerId, $followingId]);
                    $result->imported++;
                } catch (\Throwable $e) {
                    $result->errors++;
                }
            }
        }
    }

    private function importTopicSubscriptions(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'mybb_threadsubscriptions')) {
            return;
        }
        try {
            $rows = $sourcePdo->query("SELECT uid, tid, dateline FROM mybb_threadsubscriptions")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }
        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['uid']);
                $tid = $mapper->get('topic', (int) $row['tid']);
                if (!$uid || !$tid) {
                    $result->skipped++;
                    continue;
                }
                $createdAt = date('Y-m-d H:i:s', (int) $row['dateline']);
                TopicSubscription::firstOrCreate(
                    ['user_id' => $uid, 'topic_id' => $tid],
                    ['created_at' => $createdAt]
                );
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importTopicReads(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'mybb_threadsread')) {
            return;
        }
        try {
            $rows = $sourcePdo->query("SELECT uid, tid, dateline FROM mybb_threadsread")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }
        foreach ($rows as $row) {
            $result->total++;
            try {
                $uid = $mapper->get('user', (int) $row['uid']);
                $tid = $mapper->get('topic', (int) $row['tid']);
                if (!$uid || !$tid) {
                    $result->skipped++;
                    continue;
                }
                $readAt = date('Y-m-d H:i:s', (int) $row['dateline']);
                DB::table('topic_reads')->updateOrInsert(
                    ['user_id' => $uid, 'topic_id' => $tid],
                    ['last_read_at' => $readAt]
                );
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
            }
        }
    }

    private function importPostLikes(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result): void
    {
        if (!$this->tableExists($sourcePdo, 'mybb_reputation')) {
            return;
        }
        try {
            $rows = $sourcePdo->query("SELECT pid, adduid, dateline FROM mybb_reputation WHERE pid > 0 AND reputation > 0")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return;
        }
        foreach ($rows as $row) {
            $result->total++;
            try {
                $postId = $mapper->get('post', (int) $row['pid']);
                $userId = $mapper->get('user', (int) $row['adduid']);
                if (!$postId || !$userId) {
                    $result->skipped++;
                    continue;
                }
                $createdAt = date('Y-m-d H:i:s', (int) $row['dateline']);
                PostLike::firstOrCreate(
                    ['post_id' => $postId, 'user_id' => $userId],
                    ['created_at' => $createdAt]
                );
                $result->imported++;
            } catch (\Throwable $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $result->skipped++;
                } else {
                    $result->errors++;
                }
            }
        }
    }
}
