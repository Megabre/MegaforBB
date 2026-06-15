<?php

declare(strict_types=1);

/**
 * Spam & Zombie: users.is_suspended, suspended_at
 * Rol kotaları: roles.pm_daily_limit, daily_topic_limit, bump_per_day
 * Bump takibi: topic_bumps tablosu
 */
return [
    'up' => function (\PDO $pdo) {
        foreach (['is_suspended', 'suspended_at'] as $col) {
            $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = " . $pdo->quote($col));
            if ((int) $st->fetchColumn() === 0) {
                if ($col === 'is_suspended') {
                    $pdo->exec("ALTER TABLE users ADD COLUMN is_suspended TINYINT(1) UNSIGNED NOT NULL DEFAULT 0");
                } else {
                    $pdo->exec("ALTER TABLE users ADD COLUMN suspended_at DATETIME DEFAULT NULL");
                }
            }
        }

        foreach (['pm_daily_limit', 'daily_topic_limit', 'bump_per_day'] as $col) {
            $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = " . $pdo->quote($col));
            if ((int) $st->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE roles ADD COLUMN {$col} INT UNSIGNED NOT NULL DEFAULT 0");
            }
        }

        $st = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'topic_bumps'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec("
                CREATE TABLE topic_bumps (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    topic_id INT UNSIGNED NOT NULL,
                    bumped_at DATE NOT NULL,
                    created_at DATETIME DEFAULT NULL,
                    KEY topic_bumps_user_date (user_id, bumped_at),
                    KEY topic_bumps_topic (topic_id)
                )
            ");
        }
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('DROP TABLE IF EXISTS topic_bumps');
        foreach (['bump_per_day', 'daily_topic_limit', 'pm_daily_limit'] as $col) {
            try {
                $pdo->exec("ALTER TABLE roles DROP COLUMN {$col}");
            } catch (\Throwable $e) {
            }
        }
        try {
            $pdo->exec('ALTER TABLE users DROP COLUMN suspended_at');
        } catch (\Throwable $e) {
        }
        try {
            $pdo->exec('ALTER TABLE users DROP COLUMN is_suspended');
        } catch (\Throwable $e) {
        }
    },
];
