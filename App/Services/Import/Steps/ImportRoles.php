<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportRoles implements ImportStepInterface
{
    private const BUILTIN_MAP = [
        2 => 3,
        3 => 1,
        4 => 2,
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

        $rows = $sourcePdo->query('SELECT user_group_id, title, user_title FROM xf_user_group')->fetchAll(\PDO::FETCH_ASSOC);
        $result->total = count($rows);

        $insert = $targetPdo->prepare(
            'INSERT INTO roles (name, slug, color, is_staff, sort_order) VALUES (?, ?, NULL, 0, ?)'
        );

        foreach ($rows as $row) {
            $xfId = (int) $row['user_group_id'];

            if ($xfId === 1) {
                $result->skipped++;
                continue;
            }

            if (isset(self::BUILTIN_MAP[$xfId])) {
                $mapper->add('role', $xfId, self::BUILTIN_MAP[$xfId]);
                $result->imported++;
                continue;
            }

            try {
                $slug = $this->slugify($row['title']);
                $insert->execute([$row['title'], $slug, $xfId * 10]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('role', $xfId, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Role #{$xfId} ({$row['title']}): {$e->getMessage()}";
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
