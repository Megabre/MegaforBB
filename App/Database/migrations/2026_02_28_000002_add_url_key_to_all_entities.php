<?php

declare(strict_types=1);

/**
 * SEF URL (tüm sistem): posts, conversations, notifications, attachments, users tablolarına url_key ekler.
 */
return [
    'up' => function (\PDO $pdo) {
        $tables = [
            'posts' => 'ALTER TABLE `posts` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL AFTER `edit_count`, ADD UNIQUE INDEX `idx_posts_url_key` (`url_key`)',
            'conversations' => 'ALTER TABLE `conversations` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL, ADD UNIQUE INDEX `idx_conversations_url_key` (`url_key`)',
            'notifications' => 'ALTER TABLE `notifications` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL, ADD UNIQUE INDEX `idx_notifications_url_key` (`url_key`)',
            'attachments' => 'ALTER TABLE `attachments` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL, ADD UNIQUE INDEX `idx_attachments_url_key` (`url_key`)',
            'users' => 'ALTER TABLE `users` ADD COLUMN `url_key` VARCHAR(24) DEFAULT NULL, ADD UNIQUE INDEX `idx_users_url_key` (`url_key`)',
        ];
        foreach ($tables as $table => $sql) {
            try {
                $st = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'url_key'");
                if ($st && $st->rowCount() === 0) {
                    $pdo->exec($sql);
                }
            } catch (\Throwable $e) {
            }
        }
    },
    'down' => function (\PDO $pdo) {
        $drops = [
            'posts' => ['idx_posts_url_key', 'url_key'],
            'conversations' => ['idx_conversations_url_key', 'url_key'],
            'notifications' => ['idx_notifications_url_key', 'url_key'],
            'attachments' => ['idx_attachments_url_key', 'url_key'],
            'users' => ['idx_users_url_key', 'url_key'],
        ];
        foreach ($drops as $table => [$idx, $col]) {
            try {
                $st = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                if ($st && $st->rowCount() > 0) {
                    $pdo->exec("ALTER TABLE `$table` DROP INDEX `$idx`");
                    $pdo->exec("ALTER TABLE `$table` DROP COLUMN `$col`");
                }
            } catch (\Throwable $e) {
            }
        }
    }
];
