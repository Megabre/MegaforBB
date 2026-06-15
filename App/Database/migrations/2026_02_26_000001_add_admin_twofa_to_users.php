<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'admin_twofa_question'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN admin_twofa_question VARCHAR(255) DEFAULT NULL");
        }
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'admin_twofa_answer_hash'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN admin_twofa_answer_hash VARCHAR(255) DEFAULT NULL");
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec("ALTER TABLE users DROP COLUMN admin_twofa_answer_hash");
        } catch (\Throwable $e) {
        }
        try {
            $pdo->exec("ALTER TABLE users DROP COLUMN admin_twofa_question");
        } catch (\Throwable $e) {
        }
    },
];
