<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $sql = "
            CREATE TABLE IF NOT EXISTS profile_views (
                profile_user_id INT UNSIGNED NOT NULL COMMENT 'Profil sahibi',
                viewer_user_id INT UNSIGNED NOT NULL COMMENT 'Ziyaret eden üye',
                view_count INT UNSIGNED NOT NULL DEFAULT 1,
                first_viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (profile_user_id, viewer_user_id),
                INDEX idx_profile_views_last (profile_user_id, last_viewed_at),
                CONSTRAINT fk_profile_views_profile FOREIGN KEY (profile_user_id)
                    REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_profile_views_viewer FOREIGN KEY (viewer_user_id)
                    REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('DROP TABLE IF EXISTS profile_views;');
    },
];
