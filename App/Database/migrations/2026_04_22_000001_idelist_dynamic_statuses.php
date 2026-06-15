<?php

declare(strict_types=1);

/**
 * İstek durumlarını veritabanında yönetilebilir yapar; ideas.status ENUM → VARCHAR.
 */
return [
    'up' => function (\PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS idea_statuses (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(64) NOT NULL,
                name VARCHAR(120) NOT NULL,
                color VARCHAR(7) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                requires_completion TINYINT(1) NOT NULL DEFAULT 0,
                default_on_approval TINYINT(1) NOT NULL DEFAULT 0,
                default_on_open TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_idea_statuses_slug (slug),
                INDEX idx_idea_statuses_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $cnt = (int) $pdo->query('SELECT COUNT(*) FROM idea_statuses')->fetchColumn();
        if ($cnt === 0) {
            $rows = [
                ['pending', 'İncelemede', '#64748B', 10, 0, 1, 0],
                ['open', 'Açık', '#3B82F6', 20, 0, 0, 1],
                ['planned', 'Planlandı', '#8B5CF6', 30, 0, 0, 0],
                ['in_progress', 'Geliştiriliyor', '#F59E0B', 40, 0, 0, 0],
                ['completed', 'Tamamlandı', '#22C55E', 50, 1, 0, 0],
                ['rejected', 'Reddedildi', '#EF4444', 60, 0, 0, 0],
            ];
            $stmt = $pdo->prepare('
                INSERT INTO idea_statuses (slug, name, color, sort_order, requires_completion, default_on_approval, default_on_open)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            foreach ($rows as $r) {
                $stmt->execute($r);
            }
        }

        $pdo->exec('ALTER TABLE ideas MODIFY COLUMN status VARCHAR(64) NOT NULL DEFAULT \'open\'');
    },
    'down' => function (\PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS idea_statuses');
    },
];
