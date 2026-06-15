<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportMyBBForums implements ImportStepInterface
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
            SELECT fid, name, description, disporder
            FROM mybb_forums
            WHERE type = 'c'
            ORDER BY disporder ASC, fid ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $now = date('Y-m-d H:i:s');
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $insert = $targetPdo->prepare(
            'INSERT INTO categories (name, slug, description, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $findBySlug = $targetPdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');

        foreach ($rows as $row) {
            try {
                $fid = (int) $row['fid'];
                $slug = $this->slugify($row['name']);

                if ($mergeMode) {
                    $findBySlug->execute([$slug]);
                    $existing = $findBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $mapper->add('category', $fid, (int) $existing['id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $insert->execute([
                    $row['name'],
                    $slug,
                    $row['description'] ?: null,
                    (int) $row['disporder'],
                    $now,
                    $now,
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('category', $fid, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Category #{$row['fid']} ({$row['name']}): {$e->getMessage()}";
            }
        }
    }

    private function importForums(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result, array $options): void
    {
        $rows = $sourcePdo->query("
            SELECT fid, name, description, pid, disporder, threads, posts, lastpost, lastposteruid, lastposttid, open, active
            FROM mybb_forums
            WHERE type = 'f'
            ORDER BY disporder ASC, fid ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $result->total += count($rows);
        $now = date('Y-m-d H:i:s');
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $insert = $targetPdo->prepare("
            INSERT INTO forums
                (category_id, parent_id, name, slug, description, sort_order, topic_count, post_count, last_post_at, last_post_user_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $findForumBySlug = $targetPdo->prepare('SELECT id FROM forums WHERE category_id = ? AND slug = ? LIMIT 1');
        $getCategoryFromForum = $targetPdo->prepare('SELECT category_id FROM forums WHERE id = ? LIMIT 1');

        foreach ($rows as $row) {
            try {
                $fid = (int) $row['fid'];
                $pid = (int) $row['pid'];

                $categoryId = $mapper->get('category', $pid);
                $parentId = null;
                if ($categoryId === null) {
                    $parentForumId = $mapper->get('forum', $pid);
                    if ($parentForumId !== null) {
                        $getCategoryFromForum->execute([$parentForumId]);
                        $fr = $getCategoryFromForum->fetch(\PDO::FETCH_ASSOC);
                        $categoryId = $fr ? (int) $fr['category_id'] : null;
                        $parentId = $parentForumId;
                    }
                }

                if ($categoryId === null) {
                    $result->errors++;
                    $result->errorMessages[] = "Forum #{$fid} ({$row['name']}): no category for parent #{$pid}";
                    continue;
                }

                $slug = $this->slugify($row['name']);
                if ($mergeMode) {
                    $findForumBySlug->execute([$categoryId, $slug]);
                    $existing = $findForumBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $mapper->add('forum', $fid, (int) $existing['id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $lastPostAt = (int) $row['lastpost'] > 0 ? date('Y-m-d H:i:s', (int) $row['lastpost']) : null;
                $lastPostUserId = (int) $row['lastposteruid'] > 0 ? $mapper->get('user', (int) $row['lastposteruid']) : null;

                $insert->execute([
                    $categoryId,
                    $parentId,
                    $row['name'],
                    $slug,
                    $row['description'] ?: null,
                    (int) $row['disporder'],
                    (int) $row['threads'],
                    (int) $row['posts'],
                    $lastPostAt,
                    $lastPostUserId,
                    $now,
                    $now,
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('forum', $fid, $newId);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Forum #{$row['fid']} ({$row['name']}): {$e->getMessage()}";
            }
        }
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
