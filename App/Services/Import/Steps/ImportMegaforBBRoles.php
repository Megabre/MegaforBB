<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBRoles implements ImportStepInterface
{
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

        $rows = $sourcePdo->query('SELECT id, name, slug, color, is_staff, sort_order FROM roles ORDER BY id ASC')
            ->fetchAll(\PDO::FETCH_ASSOC);
        $result->total = count($rows);

        $findBySlug = $targetPdo->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
        $insert = $targetPdo->prepare(
            'INSERT INTO roles (name, slug, color, is_staff, sort_order) VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($rows as $row) {
            try {
                $oldId = (int) $row['id'];

                // Hedefte aynı slug ile rol varsa sadece eşleme yap (merge/clean fark etmez; aynı sistemde çakışma olmasın)
                $findBySlug->execute([$row['slug']]);
                $existing = $findBySlug->fetch(\PDO::FETCH_ASSOC);
                if ($existing) {
                    $mapper->add('role', $oldId, (int) $existing['id']);
                    $result->skipped++;
                    continue;
                }

                $insert->execute([
                    $row['name'],
                    $row['slug'],
                    $row['color'] ?? null,
                    (int) $row['is_staff'],
                    (int) $row['sort_order'],
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('role', $oldId, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Role #{$row['id']} ({$row['name']}): {$e->getMessage()}";
            }
        }

        return $result;
    }
}
