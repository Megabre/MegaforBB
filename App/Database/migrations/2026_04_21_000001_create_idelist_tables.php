<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS idea_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                color VARCHAR(7) NULL,
                icon VARCHAR(50) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ideas (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                category_id BIGINT UNSIGNED NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT NOT NULL,
                status ENUM('pending', 'open', 'planned', 'in_progress', 'completed', 'rejected') NOT NULL DEFAULT 'open',
                completion_note TEXT NULL,
                completion_url VARCHAR(500) NULL,
                vote_count INT NOT NULL DEFAULT 0,
                views_count INT NOT NULL DEFAULT 0,
                is_pinned TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                INDEX idx_ideas_status (status),
                INDEX idx_ideas_category_id (category_id),
                INDEX idx_ideas_vote_count_created_at (vote_count, created_at),
                INDEX idx_ideas_is_pinned_vote_count_created_at (is_pinned, vote_count, created_at),
                CONSTRAINT fk_ideas_user_id
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_ideas_category_id
                    FOREIGN KEY (category_id) REFERENCES idea_categories(id)
                    ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS idea_votes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                idea_id BIGINT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                value TINYINT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_idea_votes_idea_user (idea_id, user_id),
                INDEX idx_idea_votes_idea_id (idea_id),
                INDEX idx_idea_votes_user_id (user_id),
                CONSTRAINT fk_idea_votes_idea_id
                    FOREIGN KEY (idea_id) REFERENCES ideas(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_idea_votes_user_id
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS idea_comments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                idea_id BIGINT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                is_admin_note TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                INDEX idx_idea_comments_idea_id (idea_id),
                INDEX idx_idea_comments_user_id (user_id),
                CONSTRAINT fk_idea_comments_idea_id
                    FOREIGN KEY (idea_id) REFERENCES ideas(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_idea_comments_user_id
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS idelist_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(100) NOT NULL UNIQUE,
                `value` TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            INSERT INTO idelist_settings (`key`, `value`) VALUES
                ('module_enabled', '1'),
                ('allow_anonymous_view', '1'),
                ('votes_per_user', '0'),
                ('require_approval', '0'),
                ('show_vote_counts', '1'),
                ('allow_downvotes', '1')
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);
        ");
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('DROP TABLE IF EXISTS idea_comments;');
        $pdo->exec('DROP TABLE IF EXISTS idea_votes;');
        $pdo->exec('DROP TABLE IF EXISTS ideas;');
        $pdo->exec('DROP TABLE IF EXISTS idea_categories;');
        $pdo->exec('DROP TABLE IF EXISTS idelist_settings;');
    },
];
