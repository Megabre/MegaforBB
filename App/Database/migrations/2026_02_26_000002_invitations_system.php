<?php

declare(strict_types=1);

/**
 * Davetiye sistemi: users tablosuna kota/skor alanları, invitations tablosu.
 */
return [
    'up' => function (\PDO $pdo) {
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $prefix = $driver === 'mysql' ? '' : '';

        foreach (['available_invites', 'trust_score', 'message_count'] as $col) {
            $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = " . $pdo->quote($col));
            if ((int) $st->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE users ADD COLUMN {$col} INT UNSIGNED NOT NULL DEFAULT 0");
            }
        }

        $st = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invitations'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec("
                CREATE TABLE invitations (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    code VARCHAR(16) NOT NULL,
                    email VARCHAR(255) DEFAULT NULL,
                    used_at DATETIME DEFAULT NULL,
                    used_by INT UNSIGNED DEFAULT NULL,
                    expires_at DATETIME DEFAULT NULL,
                    created_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    UNIQUE KEY invitations_code_unique (code),
                    KEY invitations_user_id (user_id),
                    KEY invitations_used_by (used_by),
                    CONSTRAINT invitations_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                    CONSTRAINT invitations_used_by_foreign FOREIGN KEY (used_by) REFERENCES users (id) ON DELETE SET NULL
                )
            ");
        }
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('DROP TABLE IF EXISTS invitations');
        foreach (['available_invites', 'trust_score', 'message_count'] as $col) {
            try {
                $pdo->exec("ALTER TABLE users DROP COLUMN {$col}");
            } catch (\Throwable $e) {
            }
        }
    },
];
