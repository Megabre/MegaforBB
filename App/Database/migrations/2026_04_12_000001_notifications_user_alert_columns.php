<?php

declare(strict_types=1);

/**
 * Bildirim tablosunu XenForo uyarı (user alert) modeline yaklaştırır:
 * gönderen, içerik türü/kimliği/aksiyon, görüntülenme; mevcut type+data ile uyumludur.
 */
return [
    'up' => function (\PDO $pdo): void {
        $has = static function (\PDO $pdo, string $col): bool {
            $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications' AND COLUMN_NAME = " . $pdo->quote($col));

            return (int) $st->fetchColumn() > 0;
        };

        if (!$has($pdo, 'sender_user_id')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN sender_user_id INT UNSIGNED NULL DEFAULT NULL AFTER user_id');
        }
        if (!$has($pdo, 'sender_username')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN sender_username VARCHAR(100) NULL DEFAULT NULL AFTER sender_user_id');
        }
        if (!$has($pdo, 'content_type')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN content_type VARCHAR(50) NULL DEFAULT NULL AFTER type');
        }
        if (!$has($pdo, 'content_id')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN content_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER content_type');
        }
        if (!$has($pdo, 'action')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN action VARCHAR(50) NULL DEFAULT NULL AFTER content_id');
        }
        if (!$has($pdo, 'view_at')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN view_at DATETIME NULL DEFAULT NULL AFTER read_at');
        }
        if (!$has($pdo, 'auto_read')) {
            $pdo->exec('ALTER TABLE notifications ADD COLUMN auto_read TINYINT(1) NOT NULL DEFAULT 0 AFTER view_at');
        }

        try {
            $pdo->exec("UPDATE notifications SET sender_user_id = CAST(JSON_UNQUOTE(JSON_EXTRACT(CAST(data AS CHAR), '$.from_user_id')) AS UNSIGNED)
                WHERE JSON_VALID(CAST(data AS CHAR))
                AND JSON_EXTRACT(CAST(data AS CHAR), '$.from_user_id') IS NOT NULL
                AND JSON_UNQUOTE(JSON_EXTRACT(CAST(data AS CHAR), '$.from_user_id')) REGEXP '^[0-9]+$'
                AND (sender_user_id IS NULL OR sender_user_id = 0)");
        } catch (\Throwable $e) {
        }

        try {
            $pdo->exec("UPDATE notifications SET sender_username = LEFT(TRIM(JSON_UNQUOTE(JSON_EXTRACT(CAST(data AS CHAR), '$.from_username'))), 100)
                WHERE JSON_VALID(CAST(data AS CHAR))
                AND JSON_EXTRACT(CAST(data AS CHAR), '$.from_username') IS NOT NULL
                AND (sender_username IS NULL OR sender_username = '')");
        } catch (\Throwable $e) {
        }

        foreach (['idx_notifications_user_unread_sender' => '(user_id, read_at, sender_user_id)', 'idx_notifications_content' => '(content_type, content_id)'] as $name => $cols) {
            try {
                $pdo->exec("CREATE INDEX {$name} ON notifications {$cols}");
            } catch (\Throwable $e) {
            }
        }
    },
    'down' => function (\PDO $pdo): void {
        foreach (['idx_notifications_user_unread_sender', 'idx_notifications_content'] as $idx) {
            try {
                $pdo->exec("ALTER TABLE notifications DROP INDEX {$idx}");
            } catch (\Throwable $e) {
            }
        }
        foreach (['auto_read', 'view_at', 'action', 'content_id', 'content_type', 'sender_username', 'sender_user_id'] as $col) {
            try {
                $pdo->exec("ALTER TABLE notifications DROP COLUMN {$col}");
            } catch (\Throwable $e) {
            }
        }
    },
];
