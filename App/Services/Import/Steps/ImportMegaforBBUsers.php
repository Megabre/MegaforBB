<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Models\User;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBUsers implements ImportStepInterface
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
            SELECT id, username, custom_title, email, password_hash, role_id, approved_at, locale,
                   avatar_path, cover_photo_path, reputation_positive, reputation_negative,
                   location, website, bio, signature, first_name, last_name, show_name,
                   birthday, is_verified, is_banned, warning_points, reward_points,
                   last_activity_at, created_at, updated_at, email_verified_at
            FROM users
            ORDER BY id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total = count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';

        foreach ($rows as $row) {
            try {
                $oldId = (int) $row['id'];
                $roleId = $mapper->get('role', (int) $row['role_id']);
                if ($roleId === null) {
                    $roleId = 3;
                }

                if ($mergeMode) {
                    $existing = User::where('email', $row['email'])->orWhere('username', $row['username'])->first();
                    if ($existing) {
                        $mapper->add('user', $oldId, (int) $existing->id);
                        $result->skipped++;
                        continue;
                    }
                }

                $user = User::create([
                    'username' => $row['username'],
                    'custom_title' => $row['custom_title'] ?? null,
                    'email' => $row['email'],
                    'password_hash' => $row['password_hash'],
                    'role_id' => $roleId,
                    'approved_at' => $row['approved_at'] ?? null,
                    'locale' => $row['locale'] ?? 'tr',
                    'avatar_path' => $row['avatar_path'] ?? null,
                    'cover_photo_path' => $row['cover_photo_path'] ?? null,
                    'reputation_positive' => (int) ($row['reputation_positive'] ?? 0),
                    'reputation_negative' => (int) ($row['reputation_negative'] ?? 0),
                    'location' => $row['location'] ?? null,
                    'website' => $row['website'] ?? null,
                    'bio' => $row['bio'] ?? null,
                    'signature' => $row['signature'] ?? null,
                    'first_name' => $row['first_name'] ?? null,
                    'last_name' => $row['last_name'] ?? null,
                    'show_name' => (int) ($row['show_name'] ?? 0),
                    'birthday' => $row['birthday'] ?? null,
                    'is_verified' => (int) ($row['is_verified'] ?? 0),
                    'is_banned' => (int) ($row['is_banned'] ?? 0),
                    'warning_points' => (int) ($row['warning_points'] ?? 0),
                    'reward_points' => (int) ($row['reward_points'] ?? 0),
                    'last_activity_at' => $row['last_activity_at'] ?? null,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'] ?? $row['created_at'],
                    'email_verified_at' => $row['email_verified_at'] ?? null,
                ]);

                $mapper->add('user', $oldId, (int) $user->id);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "User #{$row['id']} ({$row['username']}): {$e->getMessage()}";
            }
        }

        return $result;
    }
}
