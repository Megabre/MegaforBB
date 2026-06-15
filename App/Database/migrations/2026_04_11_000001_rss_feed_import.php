<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rss_feed_sources (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL DEFAULT '',
                url VARCHAR(2000) NOT NULL,
                forum_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                prefix_id INT UNSIGNED NOT NULL DEFAULT 0,
                frequency_minutes INT UNSIGNED NOT NULL DEFAULT 60,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                title_template VARCHAR(500) NOT NULL DEFAULT '{title}',
                body_template TEXT NULL,
                last_fetch_at DATETIME NULL DEFAULT NULL,
                last_success_at DATETIME NULL DEFAULT NULL,
                last_error VARCHAR(500) NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_rss_feed_active_fetch (is_active, last_fetch_at),
                INDEX idx_rss_feed_forum (forum_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rss_feed_import_logs (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                rss_feed_source_id INT UNSIGNED NOT NULL,
                unique_entry_id VARCHAR(250) NOT NULL,
                topic_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_feed_entry (rss_feed_source_id, unique_entry_id),
                INDEX idx_rss_log_topic (topic_id),
                CONSTRAINT fk_rss_log_feed FOREIGN KEY (rss_feed_source_id) REFERENCES rss_feed_sources (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS rss_feed_import_logs');
        $pdo->exec('DROP TABLE IF EXISTS rss_feed_sources');
    },
];
