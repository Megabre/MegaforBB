<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $sql = "
            CREATE TABLE IF NOT EXISTS profile_comments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL COMMENT 'Profil sahibi',
                author_id INT UNSIGNED NOT NULL COMMENT 'Yorumu yazan',
                body TEXT NOT NULL,
                body_html TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_profile_comments_user_id (user_id),
                INDEX idx_profile_comments_author_id (author_id),
                INDEX idx_profile_comments_created_at (created_at),
                CONSTRAINT fk_profile_comment_user FOREIGN KEY (user_id)
                    REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_profile_comment_author FOREIGN KEY (author_id)
                    REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS profile_comments;");
    },
];
