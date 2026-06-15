<?php

declare(strict_types=1);

/**
 * Planla özelliği: Konuları belirli tarih/saatte otomatik yayınlama.
 * - scheduled_publish_at: planlanan yayın zamanı (NULL = normal konu)
 * - status: 'published' | 'scheduled' | 'cancelled' (varsayılan published)
 */
return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'topics' AND COLUMN_NAME = 'scheduled_publish_at'");
        if ($st && (int)$st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE topics ADD COLUMN scheduled_publish_at DATETIME DEFAULT NULL AFTER last_post_user_id");
        }
        $st2 = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'topics' AND COLUMN_NAME = 'status'");
        if ($st2 && (int)$st2->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE topics ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'published' AFTER scheduled_publish_at");
            $pdo->exec("CREATE INDEX idx_topics_status_scheduled ON topics (status, scheduled_publish_at)");
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec("ALTER TABLE topics DROP INDEX idx_topics_status_scheduled");
        } catch (\Throwable $e) {
        }
        try {
            $st = $pdo->query("SHOW COLUMNS FROM topics LIKE 'status'");
            if ($st && $st->rowCount() > 0) {
                $pdo->exec("ALTER TABLE topics DROP COLUMN status");
            }
        } catch (\Throwable $e) {
        }
        try {
            $st = $pdo->query("SHOW COLUMNS FROM topics LIKE 'scheduled_publish_at'");
            if ($st && $st->rowCount() > 0) {
                $pdo->exec("ALTER TABLE topics DROP COLUMN scheduled_publish_at");
            }
        } catch (\Throwable $e) {
        }
    },
];
