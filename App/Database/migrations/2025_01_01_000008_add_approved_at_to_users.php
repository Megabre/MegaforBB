<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'approved_at'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER role_id");
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec("ALTER TABLE users DROP COLUMN approved_at");
        } catch (\Throwable $e) {
        }
    },
];
