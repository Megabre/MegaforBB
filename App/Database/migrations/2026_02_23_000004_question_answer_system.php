<?php

declare(strict_types=1);

/**
 * Stack Overflow tarzı soru/çözüm sistemi:
 * - topics: accepted_post_id (kabul edilen cevap)
 * - posts: net_votes (yukarı/aşağı oy toplamı)
 * - post_votes: kullanıcı başına bir oy (1 veya -1)
 */
return [
    'up' => function (\PDO $pdo) {
        // topics: kabul edilen cevap
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'topics' AND COLUMN_NAME = 'accepted_post_id'");
        if ((int)$st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE topics ADD COLUMN accepted_post_id INT UNSIGNED DEFAULT NULL AFTER is_solved");
            $pdo->exec("ALTER TABLE topics ADD KEY idx_accepted_post (accepted_post_id)");
        }

        // posts: net oy sayısı
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'net_votes'");
        if ((int)$st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN net_votes INT NOT NULL DEFAULT 0 AFTER like_count");
        }

        // post_votes: kullanıcı başına tek oy (1 veya -1)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS post_votes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                post_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                value TINYINT NOT NULL COMMENT '1 up, -1 down',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_post_user (post_id, user_id),
                KEY idx_post (post_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec("ALTER TABLE topics DROP COLUMN accepted_post_id");
        } catch (\Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE posts DROP COLUMN net_votes");
        } catch (\Throwable $e) {
        }
        $pdo->exec("DROP TABLE IF EXISTS post_votes");
    },
];
