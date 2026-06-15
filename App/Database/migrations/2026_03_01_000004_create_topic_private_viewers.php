<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS topic_private_viewers (
                topic_id INT(10) UNSIGNED NOT NULL,
                user_id INT(10) UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (topic_id, user_id),
                KEY idx_tpv_user (user_id),
                CONSTRAINT tpv_topic_fk FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE CASCADE,
                CONSTRAINT tpv_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('DROP TABLE IF EXISTS topic_private_viewers');
    },
];
