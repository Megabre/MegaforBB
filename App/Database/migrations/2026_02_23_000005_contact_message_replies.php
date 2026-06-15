<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contact_message_replies (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                contact_message_id BIGINT UNSIGNED NOT NULL,
                reply_body TEXT NOT NULL,
                replied_by_user_id INT UNSIGNED NOT NULL,
                email_sent TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_contact_message_id (contact_message_id),
                INDEX idx_replied_at (created_at),
                CONSTRAINT fk_contact_reply_message FOREIGN KEY (contact_message_id) REFERENCES contact_messages(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS contact_message_replies;");
    },
];
