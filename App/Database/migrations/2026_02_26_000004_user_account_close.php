<?php

declare(strict_types=1);

/**
 * Kullanıcı hesabı kalıcı kapatma: users.closed_at
 * Kapatılan hesap tekrar açılamaz; askıya alınan hesap (is_suspended) tekrar açılabilir.
 */
return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'closed_at'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN closed_at DATETIME DEFAULT NULL");
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec('ALTER TABLE users DROP COLUMN closed_at');
        } catch (\Throwable $e) {
        }
    },
];
