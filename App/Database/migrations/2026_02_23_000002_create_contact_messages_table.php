<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $sql = "
            CREATE TABLE IF NOT EXISTS contact_messages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_is_read (is_read),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS contact_messages;");
    },
];
