<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $sql = "
            CREATE TABLE IF NOT EXISTS user_activities (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                item_id BIGINT UNSIGNED DEFAULT NULL,
                details JSON DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_action_type (action_type),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS user_activities;");
    },
];
