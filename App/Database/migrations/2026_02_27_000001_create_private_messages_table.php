<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $sql = "
            CREATE TABLE IF NOT EXISTS private_messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                conversation_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                body_html TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_conversation_id (conversation_id),
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                CONSTRAINT fk_pm_conversation FOREIGN KEY (conversation_id)
                    REFERENCES conversations(id) ON DELETE CASCADE,
                CONSTRAINT fk_pm_user FOREIGN KEY (user_id)
                    REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS private_messages;");
    },
];
