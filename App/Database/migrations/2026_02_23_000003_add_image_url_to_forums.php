<?php

declare(strict_types=1);

/**
 * forums tablosuna image_url sütunu ekler (Forum modeli ve portal kartları bu alanı kullanıyor).
 */
return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'forums' AND COLUMN_NAME = 'image_url'");
        if ((int)$st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE forums ADD COLUMN image_url VARCHAR(255) DEFAULT NULL AFTER icon");
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec("ALTER TABLE forums DROP COLUMN image_url");
        } catch (\Throwable $e) {
        }
    },
];
