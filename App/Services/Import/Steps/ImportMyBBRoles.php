<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMyBBRoles implements ImportStepInterface
{
    /** MyBB gid => MegaforBB role id (1=Admin, 2=Mod, 3=Registered) */
    private const BUILTIN_MAP = [
        2 => 3, // Registered
        3 => 2, // Super Moderators
        4 => 1, // Administrators
        5 => 3, // Awaiting Activation -> Registered
        6 => 2, // Moderators
        // 1 = Guests (skip), 7 = Banned (skip)
    ];

    public function name(): string
    {
        return lang('admin.import.step_roles');
    }

    public function key(): string
    {
        return 'roles';
    }

    public function order(): int
    {
        return 10;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();

        $rows = $sourcePdo->query('SELECT gid, title FROM mybb_usergroups ORDER BY gid ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);
        $result->total = count($rows);

        $insert = $targetPdo->prepare(
            'INSERT INTO roles (name, slug, color, is_staff, sort_order) VALUES (?, ?, NULL, 0, ?)'
        );

        foreach ($rows as $row) {
            $gid = (int) $row['gid'];

            if ($gid === 1) {
                $result->skipped++;
                continue;
            }
            if ($gid === 7) {
                $result->skipped++;
                continue;
            }

            if (isset(self::BUILTIN_MAP[$gid])) {
                $mapper->add('role', $gid, self::BUILTIN_MAP[$gid]);
                $result->imported++;
                continue;
            }

            try {
                $slug = $this->slugify($row['title']);
                $insert->execute([$row['title'], $slug, $gid * 10]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('role', $gid, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Role #{$gid} ({$row['title']}): {$e->getMessage()}";
            }
        }

        return $result;
    }

    private function slugify(string $title): string
    {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = strtr($slug, [
            'ı' => 'i', 'ş' => 's', 'ç' => 'c', 'ğ' => 'g', 'ö' => 'o', 'ü' => 'u',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}
