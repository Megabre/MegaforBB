<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMegaforBBForums implements ImportStepInterface
{
    public function name(): string
    {
        return lang('admin.import.step_forums');
    }

    public function key(): string
    {
        return 'forums';
    }

    public function order(): int
    {
        return 30;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();

        $this->importCategories($sourcePdo, $targetPdo, $mapper, $result, $options);
        $this->importForums($sourcePdo, $targetPdo, $mapper, $result, $options);

        return $result;
    }

    private function importCategories(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result, array $options): void
    {
        $rows = $sourcePdo->query("
            SELECT id, name, slug, description, icon, color, sort_order, created_at, updated_at
            FROM categories
            ORDER BY sort_order ASC, id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $findBySlug = $targetPdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
        $insert = $targetPdo->prepare("
            INSERT INTO categories (name, slug, description, icon, color, sort_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $oldId = (int) $row['id'];

                if ($mergeMode) {
                    $findBySlug->execute([$row['slug']]);
                    $existing = $findBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $mapper->add('category', $oldId, (int) $existing['id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $insert->execute([
                    $row['name'],
                    $row['slug'],
                    $row['description'] ?? null,
                    $row['icon'] ?? null,
                    $row['color'] ?? '#cccccc',
                    (int) ($row['sort_order'] ?? 0),
                    $row['created_at'] ?? date('Y-m-d H:i:s'),
                    $row['updated_at'] ?? date('Y-m-d H:i:s'),
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('category', $oldId, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Category #{$row['id']} ({$row['name']}): {$e->getMessage()}";
            }
        }
    }

    private function importForums(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result, array $options): void
    {
        $rows = $sourcePdo->query("
            SELECT id, category_id, parent_id, name, slug, forum_type, description, image_url, icon,
                   sort_order, topic_count, post_count, last_post_id, last_post_at, last_post_user_id,
                   created_at, updated_at, allow_new_posts, moderate_new_topics, moderate_new_posts,
                   count_user_posts, include_in_new_posts, indexing_mode, min_tags,
                   default_sort_order, topic_date_limit, topic_prompts
            FROM forums
            ORDER BY sort_order ASC, id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $mapper->preload('category');
        $mapper->preload('forum');
        $findBySlug = $targetPdo->prepare('SELECT id FROM forums WHERE category_id = ? AND slug = ? LIMIT 1');

        $insert = $targetPdo->prepare("
            INSERT INTO forums
                (category_id, parent_id, name, slug, forum_type, description, image_url, icon,
                 sort_order, topic_count, post_count, created_at, updated_at,
                 allow_new_posts, moderate_new_topics, moderate_new_posts, count_user_posts,
                 include_in_new_posts, indexing_mode, min_tags, default_sort_order, topic_date_limit, topic_prompts)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            try {
                $oldId = (int) $row['id'];
                $categoryId = $mapper->get('category', (int) $row['category_id']);
                if ($categoryId === null) {
                    $result->errors++;
                    $result->errorMessages[] = "Forum #{$oldId} ({$row['name']}): category not mapped";
                    continue;
                }
                $parentId = null;
                if (!empty($row['parent_id'])) {
                    $parentId = $mapper->get('forum', (int) $row['parent_id']);
                }

                if ($mergeMode) {
                    $findBySlug->execute([$categoryId, $row['slug']]);
                    $existing = $findBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $mapper->add('forum', $oldId, (int) $existing['id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $insert->execute([
                    $categoryId,
                    $parentId,
                    $row['name'],
                    $row['slug'],
                    $row['forum_type'] ?? 'discussion',
                    $row['description'] ?? null,
                    $row['image_url'] ?? null,
                    $row['icon'] ?? null,
                    (int) ($row['sort_order'] ?? 0),
                    $row['created_at'] ?? date('Y-m-d H:i:s'),
                    $row['updated_at'] ?? date('Y-m-d H:i:s'),
                    (int) ($row['allow_new_posts'] ?? 1),
                    (int) ($row['moderate_new_topics'] ?? 0),
                    (int) ($row['moderate_new_posts'] ?? 0),
                    (int) ($row['count_user_posts'] ?? 1),
                    (int) ($row['include_in_new_posts'] ?? 1),
                    (int) ($row['indexing_mode'] ?? 1),
                    (int) ($row['min_tags'] ?? 0),
                    $row['default_sort_order'] ?? 'last_post_desc',
                    (int) ($row['topic_date_limit'] ?? 0),
                    $row['topic_prompts'] ?? null,
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('forum', $oldId, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Forum #{$row['id']} ({$row['name']}): {$e->getMessage()}";
            }
        }
    }
}
