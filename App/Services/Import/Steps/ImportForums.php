<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportForums implements ImportStepInterface
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
        $mapper->preload('user');

        $this->importCategories($sourcePdo, $targetPdo, $mapper, $result, $options);
        $this->importForumNodes($sourcePdo, $targetPdo, $mapper, $result, $options);

        return $result;
    }

    private function importCategories(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result, array $options = []): void
    {
        try {
            $rows = $sourcePdo->query("
                SELECT n.node_id, n.title, n.description, n.node_name, n.display_order
                FROM xf_node n
                INNER JOIN xf_category c ON c.node_id = n.node_id
                ORDER BY n.display_order ASC, n.node_id ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $result->errors++;
            $result->errorMessages[] = 'Kategori listesi alınamadı: ' . $e->getMessage() . ' (xf_node, xf_category tablolarını kontrol edin)';
            return;
        }

        if (empty($rows)) {
            $result->errorMessages[] = 'XenForo veritabanında kategori (xf_category) kaydı bulunamadı.';
        }

        $articleCategoryNodeIds = $this->getArticleCategoryNodeIds($sourcePdo);

        $result->total += count($rows);
        $now = date('Y-m-d H:i:s');
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $insert = $targetPdo->prepare(
            'INSERT INTO categories (name, slug, description, sort_order, is_article_category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $findBySlug = $targetPdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
        $updateArticleCategory = $targetPdo->prepare('UPDATE categories SET is_article_category = 1 WHERE id = ?');

        foreach ($rows as $row) {
            try {
                $slug = !empty($row['node_name']) ? $row['node_name'] : $this->slugify($row['title']);
                $nodeId = (int) $row['node_id'];
                $isArticleCategory = isset($articleCategoryNodeIds[$nodeId]) ? 1 : 0;

                if ($mergeMode) {
                    $findBySlug->execute([$slug]);
                    $existing = $findBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $existingId = (int) $existing['id'];
                        $mapper->add('category', $nodeId, $existingId);
                        $mapper->add('node', $nodeId, $existingId, ['type' => 'category']);
                        if ($isArticleCategory) {
                            try {
                                $updateArticleCategory->execute([$existingId]);
                            } catch (\Throwable $e) {
                            }
                        }
                        $result->skipped++;
                        continue;
                    }
                }

                $insert->execute([
                    $row['title'],
                    $slug,
                    $row['description'] ?: null,
                    (int) $row['display_order'],
                    $isArticleCategory,
                    $now,
                    $now,
                ]);
                $newId = (int) $targetPdo->lastInsertId();
                $mapper->add('category', $nodeId, $newId);
                $mapper->add('node', $nodeId, $newId, ['type' => 'category']);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Category node#{$row['node_id']} ({$row['title']}): {$e->getMessage()}";
            }
        }
    }

    private function importForumNodes(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, StepResult $result, array $options = []): void
    {
        try {
            $rows = $sourcePdo->query("
                SELECT n.node_id, n.title, n.description, n.node_name, n.display_order,
                       n.parent_node_id, COALESCE(n.depth, 1) AS depth,
                       f.forum_type_id, f.allow_posting, f.discussion_count, f.message_count
                FROM xf_node n
                INNER JOIN xf_forum f ON f.node_id = n.node_id
                ORDER BY COALESCE(n.depth, 1) ASC, n.display_order ASC, n.node_id ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            try {
                $rows = $sourcePdo->query("
                    SELECT n.node_id, n.title, n.description, n.node_name, n.display_order,
                           n.parent_node_id, n.depth,
                           f.forum_type_id, f.allow_posting, f.discussion_count, f.message_count
                    FROM xf_node n
                    INNER JOIN xf_forum f ON f.node_id = n.node_id
                    ORDER BY n.lft ASC
                ")->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e2) {
                $result->errors++;
                $result->errorMessages[] = 'Forum listesi alınamadı: ' . $e->getMessage() . ' (lft denemesi: ' . $e2->getMessage() . ')';
                return;
            }
        }

        if (empty($rows)) {
            $result->errorMessages[] = 'XenForo veritabanında forum (xf_node/xf_forum) kaydı bulunamadı. Bağlantı ve tablo adlarını kontrol edin.';
            return;
        }

        $result->total += count($rows);
        $now = date('Y-m-d H:i:s');
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $insert = $targetPdo->prepare("
            INSERT INTO forums
                (category_id, parent_id, name, slug, forum_type, description,
                 sort_order, allow_new_posts, topic_count, post_count, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $lookupCategoryFromForum = $targetPdo->prepare('SELECT category_id FROM forums WHERE id = ? LIMIT 1');
        $findForumBySlugAndParent = $targetPdo->prepare('
            SELECT id FROM forums WHERE category_id = ? AND slug = ? AND (COALESCE(parent_id, 0) = COALESCE(?, 0)) LIMIT 1
        ');
        $slugExistsInCategoryParent = $targetPdo->prepare('
            SELECT 1 FROM forums WHERE category_id = ? AND (COALESCE(parent_id, 0) = COALESCE(?, 0)) AND slug = ? LIMIT 1
        ');

        foreach ($rows as $row) {
            try {
                $nodeId = (int) $row['node_id'];
                $parentNodeId = (int) $row['parent_node_id'];

                $categoryId = $mapper->get('category', $parentNodeId);
                $parentId = null;

                if ($categoryId === null) {
                    $parentId = $mapper->get('forum', $parentNodeId);
                    if ($parentId !== null) {
                        $lookupCategoryFromForum->execute([$parentId]);
                        $catRow = $lookupCategoryFromForum->fetch(\PDO::FETCH_ASSOC);
                        $categoryId = $catRow ? (int) $catRow['category_id'] : null;
                    }
                }

                if ($categoryId === null) {
                    $categoryId = $this->findAncestorCategory($sourcePdo, $targetPdo, $mapper, $parentNodeId);
                }

                if ($categoryId === null) {
                    $result->errors++;
                    $result->errorMessages[] = "Forum node#{$nodeId} ({$row['title']}): üst kategori bulunamadı (parent_node_id={$parentNodeId}). Önce kategorilerin import edildiğinden emin olun.";
                    continue;
                }

                $baseSlug = !empty($row['node_name']) ? trim($row['node_name']) : $this->slugify($row['title']);
                $baseSlug = preg_replace('/[^a-z0-9\-]/', '-', mb_strtolower($baseSlug));
                $baseSlug = trim($baseSlug, '-') ?: 'forum-' . $nodeId;
                $slug = $baseSlug;

                if ($mergeMode) {
                    $findForumBySlugAndParent->execute([$categoryId, $slug, $parentId]);
                    $existing = $findForumBySlugAndParent->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $existingId = (int) $existing['id'];
                        $mapper->add('forum', $nodeId, $existingId);
                        $mapper->add('node', $nodeId, $existingId, ['type' => 'forum']);
                        $result->skipped++;
                        continue;
                    }
                }

                $slugExistsInCategoryParent->execute([$categoryId, $parentId, $slug]);
                if ($slugExistsInCategoryParent->fetch()) {
                    $slug = $slug . '-' . $nodeId;
                }

                $forumType = $row['forum_type_id'] ?? 'discussion';
                $allowPosts = isset($row['allow_posting']) ? (int) $row['allow_posting'] : 1;

                $insert->execute([
                    $categoryId,
                    $parentId,
                    $row['title'],
                    $slug,
                    $forumType,
                    $row['description'] ?: null,
                    (int) $row['display_order'],
                    $allowPosts,
                    (int) $row['discussion_count'],
                    (int) $row['message_count'],
                    $now,
                    $now,
                ]);

                $newForumId = (int) $targetPdo->lastInsertId();
                $mapper->add('forum', $nodeId, $newForumId);
                $mapper->add('node', $nodeId, $newForumId, ['type' => 'forum']);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Forum node#{$row['node_id']} ({$row['title']}): {$e->getMessage()}";
            }
        }
    }

    /**
     * XenForo'da discussion_type='article' olan konuların bulunduğu forumların
     * üst kategorilerini döndürür. Bu kategoriler bizde "makale kategorisi" olarak işaretlenir.
     * @return array<int, true> category node_id => true
     */
    private function getArticleCategoryNodeIds(\PDO $sourcePdo): array
    {
        $out = [];
        try {
            $forumNodeIds = $sourcePdo->query(
                "SELECT DISTINCT node_id FROM xf_thread WHERE discussion_type = 'article'"
            )->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            return $out;
        }
        if (empty($forumNodeIds)) {
            return $out;
        }
        $categoryIds = [];
        try {
            $st = $sourcePdo->query('SELECT node_id FROM xf_category');
            while ($row = $st->fetch(\PDO::FETCH_NUM)) {
                $categoryIds[(int) $row[0]] = true;
            }
        } catch (\Throwable $e) {
            return $out;
        }
        $getParent = $sourcePdo->prepare('SELECT parent_node_id FROM xf_node WHERE node_id = ? LIMIT 1');
        foreach ($forumNodeIds as $nodeId) {
            $current = (int) $nodeId;
            $visited = [];
            while ($current > 0 && !isset($visited[$current])) {
                $visited[$current] = true;
                if (isset($categoryIds[$current])) {
                    $out[$current] = true;
                    break;
                }
                $getParent->execute([$current]);
                $row = $getParent->fetch(\PDO::FETCH_NUM);
                if (!$row || (int) $row[0] === 0) {
                    break;
                }
                $current = (int) $row[0];
            }
        }
        return $out;
    }

    private function findAncestorCategory(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, int $nodeId): ?int
    {
        $stmtSource = $sourcePdo->prepare('SELECT parent_node_id FROM xf_node WHERE node_id = ? LIMIT 1');
        $visited = [];

        while ($nodeId > 0 && !isset($visited[$nodeId])) {
            $visited[$nodeId] = true;

            $catId = $mapper->get('category', $nodeId);
            if ($catId !== null) {
                return $catId;
            }

            $forumId = $mapper->get('forum', $nodeId);
            if ($forumId !== null) {
                $lookup = $targetPdo->prepare('SELECT category_id FROM forums WHERE id = ? LIMIT 1');
                $lookup->execute([$forumId]);
                $row = $lookup->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    return (int) $row['category_id'];
                }
            }

            $stmtSource->execute([$nodeId]);
            $parent = $stmtSource->fetch(\PDO::FETCH_ASSOC);
            if (!$parent) {
                break;
            }
            $nodeId = (int) $parent['parent_node_id'];
        }

        return null;
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
