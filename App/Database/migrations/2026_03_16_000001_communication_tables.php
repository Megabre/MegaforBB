<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS message_templates (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body_html MEDIUMTEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS mail_templates (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                template_key VARCHAR(64) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                subject VARCHAR(500) NOT NULL DEFAULT '',
                body_html MEDIUMTEXT NULL,
                body_text TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_template_key (template_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('DROP TABLE IF EXISTS message_templates;');
        $pdo->exec('DROP TABLE IF EXISTS mail_templates;');
    },
];
