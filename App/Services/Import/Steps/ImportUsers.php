<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Models\Setting;
use App\Models\User;
use App\Services\Import\BBCodeConverter;
use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportUsers implements ImportStepInterface
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
            SELECT u.user_id, u.username, u.email, u.custom_title, u.user_group_id,
                   u.secondary_group_ids, u.register_date, u.last_activity,
                   u.is_banned, u.is_moderator, u.is_admin, u.is_staff,
                   u.user_state, u.message_count, u.reaction_score, u.warning_points,
                   u.avatar_date,
                   up.location, up.website, up.about, up.signature,
                   up.dob_day, up.dob_month, up.dob_year, up.banner_date,
                   ua.data as auth_data
            FROM xf_user u
            LEFT JOIN xf_user_profile up ON up.user_id = u.user_id
            LEFT JOIN xf_user_authenticate ua ON ua.user_id = u.user_id
            ORDER BY u.user_id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total = count($rows);

        $mergeMode = ($options['mode'] ?? '') === 'merge';

        foreach ($rows as $row) {
            try {
                $xfUserId = (int) $row['user_id'];

                $newUserId = null;

                if ($mergeMode) {
                    $existing = User::where('email', $row['email'])->orWhere('username', $row['username'])->first();
                    if ($existing) {
                        $newUserId = (int) $existing->id;
                        $mapper->add('user', $xfUserId, $newUserId);
                        $this->importUserAvatarAndCover($newUserId, (int) $row['user_id'], $row, $options);
                        $result->skipped++;
                        continue;
                    }
                }

                $passwordHash = $this->extractPasswordHash($row['auth_data']);
                $roleId = $this->determineRoleId($row, $mapper);
                $createdAt = $this->timestampToDatetime((int) $row['register_date']);
                $lastActivity = $this->timestampToDatetime((int) $row['last_activity']);
                $birthday = $this->buildBirthday((int) $row['dob_year'], (int) $row['dob_month'], (int) $row['dob_day']);
                $isVerified = $row['user_state'] === 'valid' ? 1 : 0;

                $bio = !empty($row['about']) ? BBCodeConverter::convert($row['about']) : null;
                $signature = !empty($row['signature']) ? mb_substr(BBCodeConverter::convert($row['signature']), 0, 500) : null;

                $user = User::create([
                    'username' => $row['username'],
                    'email' => $row['email'],
                    'password_hash' => $passwordHash,
                    'custom_title' => $row['custom_title'] ?: null,
                    'role_id' => $roleId,
                    'locale' => 'tr',
                    'location' => $row['location'] ?: null,
                    'website' => $row['website'] ?: null,
                    'bio' => $bio,
                    'signature' => $signature,
                    'birthday' => $birthday,
                    'is_verified' => $isVerified,
                    'is_banned' => (int) $row['is_banned'],
                    'warning_points' => (int) $row['warning_points'],
                    'reputation_positive' => (int) $row['reaction_score'],
                    'last_activity_at' => $lastActivity,
                    'created_at' => $createdAt,
                    'updated_at' => \now(),
                    'email_verified_at' => $isVerified ? $createdAt : null,
                ]);

                $newUserId = (int) $user->id;
                $mapper->add('user', $xfUserId, $newUserId);
                $this->importUserAvatarAndCover($newUserId, (int) $row['user_id'], $row, $options);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "User #{$row['user_id']} ({$row['username']}): {$e->getMessage()}";
            }
        }

        return $result;
    }

    private function extractPasswordHash(?string $authData): string
    {
        if ($authData === null || $authData === '') {
            return $this->randomBcrypt();
        }

        $unserialized = @unserialize($authData);
        if (is_array($unserialized) && isset($unserialized['hash'])) {
            return $unserialized['hash'];
        }

        if (is_object($unserialized) && isset($unserialized->hash)) {
            return $unserialized->hash;
        }

        $decoded = @unserialize(@hex2bin($authData));
        if (is_array($decoded) && isset($decoded['hash'])) {
            return $decoded['hash'];
        }

        return $this->randomBcrypt();
    }

    private function randomBcrypt(): string
    {
        return password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    }

    private function determineRoleId(array $row, IdMapper $mapper): int
    {
        if ((int) $row['is_admin'] === 1) {
            return 1;
        }
        if ((int) $row['is_moderator'] === 1) {
            return 2;
        }
        return $mapper->get('role', (int) $row['user_group_id']) ?? 3;
    }

    private function timestampToDatetime(int $timestamp): ?string
    {
        if ($timestamp <= 0) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function buildBirthday(int $year, int $month, int $day): ?string
    {
        if ($year > 0 && $month > 0 && $day > 0) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
        return null;
    }

    /**
     * XenForo: data/avatars/{s,m,l,h,o}/{floor(user_id/1000)}/{user_id}.jpg
     *          data/profile_banners/{size}/{floor(user_id/1000)}/{user_id}.jpg
     * Copy to MegaforBB uploads/avatars and uploads/covers when xenforo_data_path is set.
     */
    private function importUserAvatarAndCover(int $newUserId, int $xfUserId, array $row, array $options): void
    {
        $dataPath = isset($options['xenforo_data_path'])
            ? rtrim(str_replace('\\', '/', (string) $options['xenforo_data_path']), '/')
            : '';
        if ($dataPath === '' || !is_dir($dataPath)) {
            return;
        }

        $baseDir = dirname(__DIR__, 4);
        $localRoot = trim(str_replace('\\', '/', (string) Setting::getValue('storage_local_path', 'uploads')), '/');
        if (!in_array($localRoot, ['uploads', 'Content/storage/uploads'], true)) {
            $localRoot = 'uploads';
        }
        $uploadsBaseDir = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $localRoot);
        $group = (int) floor($xfUserId / 1000);
        $updates = [];

        if (!empty($row['avatar_date']) && (int) $row['avatar_date'] > 0) {
            $avatarSizes = ['l', 'm', 's', 'h', 'o'];
            $srcPath = null;
            foreach ($avatarSizes as $size) {
                $p = $dataPath . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $group . DIRECTORY_SEPARATOR . $xfUserId . '.jpg';
                if (is_file($p)) {
                    $srcPath = $p;
                    break;
                }
            }
            if ($srcPath !== null) {
                $subDir = date('Y') . '/' . date('m');
                $destDir = $uploadsBaseDir . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subDir);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                if (is_dir($destDir) && is_writable($destDir)) {
                    $ext = pathinfo($srcPath, PATHINFO_EXTENSION) ?: 'jpg';
                    $destName = 'u' . $newUserId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destPath = $destDir . DIRECTORY_SEPARATOR . $destName;
                    if (@copy($srcPath, $destPath)) {
                        $updates['avatar_path'] = 'uploads/avatars/' . $subDir . '/' . $destName;
                    }
                }
            }
        }

        if (!empty($row['banner_date']) && (int) $row['banner_date'] > 0) {
            $bannerSizes = ['l', 'm', 's'];
            $srcPath = null;
            foreach ($bannerSizes as $size) {
                $p = $dataPath . DIRECTORY_SEPARATOR . 'profile_banners' . DIRECTORY_SEPARATOR . $size . DIRECTORY_SEPARATOR . $group . DIRECTORY_SEPARATOR . $xfUserId . '.jpg';
                if (is_file($p)) {
                    $srcPath = $p;
                    break;
                }
            }
            if ($srcPath !== null && \Illuminate\Database\Capsule\Manager::schema()->hasColumn('users', 'cover_photo_path')) {
                $subDir = date('Y') . '/' . date('m');
                $destDir = $uploadsBaseDir . DIRECTORY_SEPARATOR . 'covers' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $subDir);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0755, true);
                }
                if (is_dir($destDir) && is_writable($destDir)) {
                    $ext = pathinfo($srcPath, PATHINFO_EXTENSION) ?: 'jpg';
                    $destName = 'u' . $newUserId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destPath = $destDir . DIRECTORY_SEPARATOR . $destName;
                    if (@copy($srcPath, $destPath)) {
                        $updates['cover_photo_path'] = 'uploads/covers/' . $subDir . '/' . $destName;
                    }
                }
            }
        }

        if ($updates !== []) {
            $updates['updated_at'] = date('Y-m-d H:i:s');
            User::where('id', $newUserId)->update($updates);
        }
    }
}
