<?php

declare(strict_types=1);

/**
 * Smiley / emoji: metin kodları → Unicode veya GIF.
 */
return [
    'up' => "
        CREATE TABLE IF NOT EXISTS smileys (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(32) NOT NULL,
            unicode_char VARCHAR(16) DEFAULT NULL COMMENT 'Unicode emoji karakteri',
            image_path VARCHAR(255) DEFAULT NULL COMMENT 'GIF dosya yolu (public/smileys/...)',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_smileys_code (code(8)),
            INDEX idx_smileys_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES
        ('smiley_enabled', '1', 'smiley'),
        ('smiley_use_gif', '0', 'smiley'),
        ('smiley_gif_max_size_kb', '50', 'smiley');
    ",
    'down' => "
        DROP TABLE IF EXISTS smileys;
        DELETE FROM settings WHERE `key` IN ('smiley_enabled', 'smiley_use_gif', 'smiley_gif_max_size_kb');
    ",
];
