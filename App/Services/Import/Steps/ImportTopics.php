<?php

declare(strict_types=1);

namespace App\Services\Import\Steps;

use App\Services\Import\IdMapper;
use App\Services\Import\ImportStepInterface;
use App\Services\Import\StepResult;

class ImportTopics implements ImportStepInterface
{
    private const TURKISH_MAP = [
        'ı' => 'i', 'ş' => 's', 'ç' => 'c', 'ğ' => 'g', 'ö' => 'o', 'ü' => 'u',
        'İ' => 'i', 'Ş' => 's', 'Ç' => 'c', 'Ğ' => 'g', 'Ö' => 'o', 'Ü' => 'u',
    ];

    public function name(): string
    {
        return lang('admin.import.step_topics');
    }

    public function key(): string
    {
        return 'topics';
    }

    public function order(): int
    {
        return 40;
    }

    public function run(\PDO $sourcePdo, \PDO $targetPdo, IdMapper $mapper, array $options = []): StepResult
    {
        $result = new StepResult();

        $mapper->preload('forum');
        $mapper->preload('user');

        try {
            $rows = $sourcePdo->query("
                SELECT thread_id, node_id, title, reply_count, view_count, user_id, username,
                       post_date, sticky, discussion_state, discussion_open, discussion_type,
                       first_post_id, last_post_date, last_post_id, last_post_user_id, last_post_username,
                       prefix_id
                FROM xf_thread
                ORDER BY thread_id ASC
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $result->errors++;
            $result->errorMessages[] = 'Konu listesi alınamadı: ' . $e->getMessage() . ' (xf_thread tablosu ve sütun adlarını kontrol edin)';
            return $result;
        }

        if (empty($rows)) {
            $result->errorMessages[] = 'XenForo veritabanında konu (xf_thread) kaydı yok veya tablo boş. Önce forumların import edildiğinden emin olun.';
            return $result;
        }

        $result->total = count($rows);
        $mergeMode = ($options['mode'] ?? '') === 'merge';
        $insert = $targetPdo->prepare("
            INSERT INTO topics
                (forum_id, user_id, title, slug, type, is_sticky, is_locked,
                 reply_count, view_count, first_post_id, last_post_id,
                 last_post_at, last_post_user_id, created_at, updated_at, deleted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $findTopicBySlug = $targetPdo->prepare('SELECT id FROM topics WHERE forum_id = ? AND slug = ? LIMIT 1');

        foreach ($rows as $row) {
            try {
                $threadId = (int) $row['thread_id'];
                $nodeId = (int) $row['node_id'];

                $forumId = $mapper->get('forum', $nodeId);
                if ($forumId === null) {
                    $result->errors++;
                    $result->errorMessages[] = "Konu #{$threadId} (node_id={$nodeId}): bu node için forum eşlemesi yok. Önce \"Forumlar\" adımını çalıştırın; node_id bir kategori ise konular foruma taşınmalı.";
                    continue;
                }

                $slug = $this->slugify($row['title'], $threadId);

                if ($mergeMode) {
                    $findTopicBySlug->execute([$forumId, $slug]);
                    $existing = $findTopicBySlug->fetch(\PDO::FETCH_ASSOC);
                    if ($existing) {
                        $existingId = (int) $existing['id'];
                        $mapper->add('topic', $threadId, $existingId);
                        $mapper->add('topic_first_post', $threadId, (int) $row['first_post_id']);
                        $mapper->add('topic_last_post', $threadId, (int) $row['last_post_id']);
                        $result->skipped++;
                        continue;
                    }
                }

                $userId = $mapper->get('user', (int) $row['user_id']) ?? 0;
                $type = $row['discussion_type'] === 'article' ? 'article' : 'topic';
                $isSticky = (int) $row['sticky'];
                $isLocked = ((int) $row['discussion_open'] === 0) ? 1 : 0;
                $createdAt = date('Y-m-d H:i:s', (int) $row['post_date']);
                $lastPostAt = (int) $row['last_post_date'] > 0
                    ? date('Y-m-d H:i:s', (int) $row['last_post_date'])
                    : $createdAt;
                $lastPostUserId = (int) $row['last_post_user_id'] > 0
                    ? $mapper->get('user', (int) $row['last_post_user_id'])
                    : null;
                $deletedAt = $row['discussion_state'] === 'deleted'
                    ? date('Y-m-d H:i:s', (int) $row['post_date'])
                    : null;

                $insert->execute([
                    $forumId,
                    $userId,
                    $row['title'],
                    $slug,
                    $type,
                    $isSticky,
                    $isLocked,
                    (int) $row['reply_count'],
                    (int) $row['view_count'],
                    null,
                    null,
                    $lastPostAt,
                    $lastPostUserId,
                    $createdAt,
                    $createdAt,
                    $deletedAt,
                ]);

                $newTopicId = (int) $targetPdo->lastInsertId();
                $mapper->add('topic', $threadId, $newTopicId);
                $mapper->add('topic_first_post', $threadId, (int) $row['first_post_id']);
                $mapper->add('topic_last_post', $threadId, (int) $row['last_post_id']);
                $result->imported++;
            } catch (\Throwable $e) {
                $result->errors++;
                $result->errorMessages[] = "Thread #{$row['thread_id']} ({$row['title']}): {$e->getMessage()}";
            }
        }

        $this->markArticleCategoriesFromTopics($targetPdo, $result);

        return $result;
    }

    /**
     * İçinde en az bir makale (type='article') olan forumların kategorilerini makale kategorisi olarak işaretler.
     * Böylece /articles sayfası ve ArticleController bu kategorileri kullanır.
     */
    private function markArticleCategoriesFromTopics(\PDO $targetPdo, StepResult $result): void
    {
        try {
            $targetPdo->exec("
                UPDATE categories c
                INNER JOIN forums f ON f.category_id = c.id
                INNER JOIN topics t ON t.forum_id = f.id AND t.type = 'article' AND (t.deleted_at IS NULL)
                SET c.is_article_category = 1
            ");
        } catch (\Throwable $e) {
            $result->errorMessages[] = 'Makale kategorileri işaretlenirken hata: ' . $e->getMessage();
        }
    }

    private function slugify(string $title, int $threadId): string
    {
        $slug = mb_strtolower($title, 'UTF-8');
        $slug = strtr($slug, self::TURKISH_MAP);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = preg_replace('/-{2,}/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = mb_substr($slug, 0, 200);

        if ($slug === '') {
            $slug = 'topic-' . $threadId;
        }

        return $slug;
    }
}
