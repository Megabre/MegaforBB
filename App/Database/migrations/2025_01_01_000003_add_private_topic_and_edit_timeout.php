<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'topics' AND COLUMN_NAME = 'is_private'");
        if ((int)$st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE topics ADD COLUMN is_private TINYINT(1) NOT NULL DEFAULT 0 AFTER is_locked");
        }
        $pdo->exec("INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES ('edit_timeout_minutes', '0', 'topic_post')");
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec("ALTER TABLE topics DROP COLUMN is_private");
        } catch (\Throwable $e) {
        }
        $pdo->exec("DELETE FROM settings WHERE `key` = 'edit_timeout_minutes'");
    },
];
