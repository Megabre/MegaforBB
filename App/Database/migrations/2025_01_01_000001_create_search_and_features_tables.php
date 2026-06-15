<?php

declare(strict_types=1);

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pr_email (email),
            INDEX idx_pr_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS topic_reads (
            user_id INT UNSIGNED NOT NULL,
            topic_id INT UNSIGNED NOT NULL,
            last_read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, topic_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS polls (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            topic_id INT UNSIGNED NOT NULL,
            question VARCHAR(500) NOT NULL,
            max_votes TINYINT UNSIGNED NOT NULL DEFAULT 1,
            allow_change_vote TINYINT(1) NOT NULL DEFAULT 0,
            closes_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_poll_topic (topic_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS poll_options (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id INT UNSIGNED NOT NULL,
            option_text VARCHAR(500) NOT NULL,
            vote_count INT UNSIGNED NOT NULL DEFAULT 0,
            sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
            INDEX idx_po_poll (poll_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS poll_votes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            poll_id INT UNSIGNED NOT NULL,
            option_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_pv_user_option (poll_id, option_id, user_id),
            INDEX idx_pv_poll (poll_id),
            INDEX idx_pv_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS attachments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED DEFAULT NULL,
            user_id INT UNSIGNED NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL DEFAULT 'application/octet-stream',
            file_size INT UNSIGNED NOT NULL DEFAULT 0,
            download_count INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_att_post (post_id),
            INDEX idx_att_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS post_edits (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            old_body LONGTEXT NOT NULL,
            edit_reason VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pe_post (post_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT UNSIGNED NOT NULL DEFAULT 1,
            ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS poll_votes;
        DROP TABLE IF EXISTS poll_options;
        DROP TABLE IF EXISTS polls;
        DROP TABLE IF EXISTS password_resets;
        DROP TABLE IF EXISTS topic_reads;
        DROP TABLE IF EXISTS attachments;
        DROP TABLE IF EXISTS post_edits;
    "
];
