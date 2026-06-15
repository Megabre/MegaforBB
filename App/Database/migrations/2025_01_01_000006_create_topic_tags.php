<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS topic_tags (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                topic_id INT UNSIGNED NOT NULL,
                tag_name VARCHAR(100) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_tt_topic (topic_id),
                INDEX idx_tt_name (tag_name),
                UNIQUE KEY uniq_topic_tag (topic_id, tag_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS topic_tags");
    },
];
