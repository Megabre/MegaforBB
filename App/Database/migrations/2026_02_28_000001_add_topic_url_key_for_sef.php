<?php

declare(strict_types=1);

/**
 * Adds url_key column to topics for SEF URL support (random 24-char option).
 */
return [
    'up' => function (\PDO $pdo) {
        try {
            $st = $pdo->query("SHOW COLUMNS FROM `topics` LIKE 'url_key'");
            if ($st && $st->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `topics` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL AFTER `slug`");
                $pdo->exec("CREATE UNIQUE INDEX idx_topics_url_key ON topics(url_key)");
            }
        } catch (\Throwable $e) {
            // Column might already exist
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $st = $pdo->query("SHOW COLUMNS FROM `topics` LIKE 'url_key'");
            if ($st && $st->rowCount() > 0) {
                $pdo->exec("ALTER TABLE `topics` DROP INDEX idx_topics_url_key");
                $pdo->exec("ALTER TABLE `topics` DROP COLUMN `url_key`");
            }
        } catch (\Throwable $e) {
        }
    }
];
