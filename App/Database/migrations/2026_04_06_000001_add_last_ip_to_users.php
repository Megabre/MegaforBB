<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_ip'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) DEFAULT NULL AFTER last_activity_at');
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec('ALTER TABLE users DROP COLUMN last_ip');
        } catch (\Throwable) {
        }
    },
];
