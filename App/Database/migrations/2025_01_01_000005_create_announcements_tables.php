<?php

declare(strict_types=1);

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS announcements (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            badge_type VARCHAR(32) NOT NULL DEFAULT 'info',
            display_location VARCHAR(32) NOT NULL DEFAULT 'both',
            send_as_notification TINYINT(1) NOT NULL DEFAULT 0,
            is_dismissible TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            show_from DATETIME DEFAULT NULL,
            show_until DATETIME DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_active_dates (is_active, show_from, show_until),
            INDEX idx_display_location (display_location)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS announcement_dismissals (
            user_id INT UNSIGNED NOT NULL,
            announcement_id INT UNSIGNED NOT NULL,
            dismissed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, announcement_id),
            INDEX idx_announcement (announcement_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'down' => "
        DROP TABLE IF EXISTS announcement_dismissals;
        DROP TABLE IF EXISTS announcements;
    "
];
