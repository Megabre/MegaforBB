<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            ALTER TABLE conversation_user
            ADD COLUMN hidden_at DATETIME NULL DEFAULT NULL
            AFTER last_read_at
        ");
        $pdo->exec('CREATE INDEX idx_conversation_user_hidden ON conversation_user (user_id, hidden_at);');

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS private_message_hidden (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                private_message_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                hidden_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_pm_user (private_message_id, user_id),
                INDEX idx_user_id (user_id),
                CONSTRAINT fk_pmh_message FOREIGN KEY (private_message_id)
                    REFERENCES private_messages(id) ON DELETE CASCADE,
                CONSTRAINT fk_pmh_user FOREIGN KEY (user_id)
                    REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec('ALTER TABLE roles ADD COLUMN pm_inbox_limit INT UNSIGNED NOT NULL DEFAULT 0 AFTER pm_daily_limit;');
        $pdo->exec('ALTER TABLE roles ADD COLUMN pm_daily_receive_limit INT UNSIGNED NOT NULL DEFAULT 0 AFTER pm_inbox_limit;');
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('DROP TABLE IF EXISTS private_message_hidden;');
        $pdo->exec('ALTER TABLE conversation_user DROP INDEX idx_conversation_user_hidden;');
        $pdo->exec('ALTER TABLE conversation_user DROP COLUMN hidden_at;');
        $pdo->exec('ALTER TABLE roles DROP COLUMN pm_daily_receive_limit;');
        $pdo->exec('ALTER TABLE roles DROP COLUMN pm_inbox_limit;');
    },
];
