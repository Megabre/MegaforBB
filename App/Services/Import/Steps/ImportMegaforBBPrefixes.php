<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBPrefixes implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_prefixes');
    }

    public function key(): string
    {
        return 'prefixes';
    }

    public function order(): int
    {
        return 32;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();
        $mapper->preload('category');

        $rows = $sourcePdo->query("
            SELECT id, name, slug, css_class, sort_order, category_id
            FROM topic_prefixes
            ORDER BY sort_order ASC, id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total = count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $findBySlug = $targetPdo->prepare('SELECT id FROM topic_prefixes WHERE slug = ? LIMIT 1');
        $insert = $targetPdo->prepare("
            INSERT INTO topic_prefixes (name, slug, css_class, sort_order, category_id)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $oldId = (int) $row['id'];
                $categoryId = null;
                if (!empty($row['category_id'])) {
                    $categoryId = $mapper->get('category', (int) $row['category_id']);
                }

                if ($mergeMode) {
                    $findBySlug->execute([$row['slug']]);
                    $existing = $findBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $mapper->add('prefix', $oldId, (int) $existing['id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $insert->execute([
                    $row['name'],
                    $row['slug'],
                    $row['css_class'] ?? null,
                    (int) ($row['sort_order'] ?? 0),
                    $categoryId,
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('prefix', $oldId, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Prefix #{$row['id']} ({$row['name']}): {$e->getMessage()}";
            }
        }

        return $result;
    }
}
