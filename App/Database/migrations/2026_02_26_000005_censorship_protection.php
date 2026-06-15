<?php

declare(strict_types=1);

/**
 * Sansür koruma: engellenecek kelimeler, engellenecek kullanıcı adları, temp mail koruma.
 */
return [
    'up' => "
        CREATE TABLE IF NOT EXISTS blocked_words (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            word VARCHAR(255) NOT NULL,
            replacement VARCHAR(255) DEFAULT NULL COMMENT 'Replace with this if action=replace',
            is_regex TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bw_word (word(64))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS blocked_usernames (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            pattern VARCHAR(255) NOT NULL COMMENT 'Exact username or regex pattern',
            is_regex TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bu_pattern (pattern(64))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS blocked_email_domains (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            domain VARCHAR(255) NOT NULL COMMENT 'e.g. tempmail.com',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_domain (domain(191)),
            INDEX idx_bed_domain (domain(64))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES
        ('censorship_enabled', '0', 'censorship'),
        ('censorship_word_action', 'block', 'censorship'),
        ('censorship_apply_posts', '1', 'censorship'),
        ('censorship_apply_topic_titles', '1', 'censorship'),
        ('censorship_apply_signatures', '1', 'censorship'),
        ('temp_mail_block_enabled', '1', 'censorship'),
        ('blocked_usernames_enabled', '1', 'censorship');

        INSERT IGNORE INTO blocked_email_domains (domain) VALUES
        ('tempmail.com'), ('guerrillamail.com'), ('10minutemail.com'), ('mailinator.com'),
        ('throwaway.email'), ('temp-mail.org'), ('fakeinbox.com'), ('trashmail.com'),
        ('yopmail.com'), ('getnada.com'), ('mailnesia.com'), ('sharklasers.com'),
        ('guerrillamail.info'), ('dispostable.com'), ('tempinbox.com'), ('mohmal.com');
    ",
    'down' => "
        DROP TABLE IF EXISTS blocked_words;
        DROP TABLE IF EXISTS blocked_usernames;
        DROP TABLE IF EXISTS blocked_email_domains;
        DELETE FROM settings WHERE `key` IN (
            'censorship_enabled', 'censorship_word_action',
            'censorship_apply_posts', 'censorship_apply_topic_titles', 'censorship_apply_signatures',
            'temp_mail_block_enabled', 'blocked_usernames_enabled'
        );
    ",
];
