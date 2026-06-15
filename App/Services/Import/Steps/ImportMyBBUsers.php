<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\BBCodeConverter;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMyBBUsers implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_users');
    }

    public function key(): string
    {
        return 'users';
    }

    public function order(): int
    {
        return 20;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();
        $mapper->preload('role');

        $rows = $sourcePdo->query("
            SELECT uid, username, email, password, salt, usergroup, additionalgroups, displaygroup,
                   usertitle, regdate, lastactive, lastvisit, lastpost, website, signature,
                   postnum, threadnum, reputation, hideemail, invisible
            FROM mybb_users
            WHERE uid > 0
            ORDER BY uid ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total = count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $dupeCheck = $targetPdo->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
        $insert = $targetPdo->prepare("
            INSERT INTO users
                (username, email, password_hash, custom_title, role_id, locale,
                 location, website, bio, signature, birthday,
                 is_verified, is_banned, warning_points, reputation_positive,
                 last_activity_at, created_at, updated_at, email_verified_at)
            VALUES (?, ?, ?, ?, ?, 'tr', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $uid = (int) $row['uid'];

                if ($mergeMode) {
                    $dupeCheck->execute([$row['email'], $row['username']]);
                    $existing = $dupeCheck->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $mapper->add('user', $uid, (int) $existing['id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $passwordHash = $this->randomBcrypt();
                $roleId = $this->determineRoleId((int) $row['usergroup'], $mapper);
                $createdAt = $this->timestampToDatetime((int) $row['regdate']);
                $lastActivity = $this->timestampToDatetime((int) $row['lastactive']);
                $isBanned = ($row['usergroup'] == 7) ? 1 : 0;
                $signature = !empty($row['signature'])
                    ? mb_substr(BBCodeConverter::convertMyCode($row['signature']), 0, 500)
                    : null;

                $insert->execute([
                    $row['username'],
                    $row['email'],
                    $passwordHash,
                    $row['usertitle'] ?: null,
                    $roleId,
                    null,
                    $row['website'] ?: null,
                    null,
                    $signature,
                    null,
                    1,
                    $isBanned,
                    0,
                    max(0, (int) $row['reputation']),
                    $lastActivity,
                    $createdAt,
                    date('Y-m-d H:i:s'),
                    $createdAt,
                ]);

                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('user', $uid, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "User #{$row['uid']} ({$row['username']}): {$e->getMessage()}";
            }
        }

        return $result;
    }

    private function determineRoleId(int $usergroup, IdMapper $mapper): int
    {
        if ($usergroup === 4) {
            return 1;
        }
        if (in_array($usergroup, [3, 6], true)) {
            return 2;
        }
        return $mapper->get('role', $usergroup) ?? 3;
    }

    private function randomBcrypt(): string
    {
        return password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    }

    private function timestampToDatetime(int $timestamp): ?string
    {
        if ($timestamp <= 0) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
}
