<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $sql = "
            CREATE TABLE IF NOT EXISTS user_preferences (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                preference_key VARCHAR(100) NOT NULL,
                value TEXT DEFAULT NULL,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_preference (user_id, preference_key),
                INDEX idx_user_id (user_id),
                CONSTRAINT fk_user_preferences_user FOREIGN KEY (user_id)
                    REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS user_preferences;");
    },
];
