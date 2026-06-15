<?php

declare(strict_types=1);

/**
 * categories tablosuna is_article_category ekler.
 * Makale kategorisi işaretlenen kategoriler forum listesinde görünmez, sadece Makaleler bölümünde kullanılır.
 */
return [
    'up' => function (\PDO $pdo) {
        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'is_article_category'");
        if ((int)$st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN is_article_category TINYINT(1) NOT NULL DEFAULT 0 AFTER sort_order");
        }
    },
    'down' => function (\PDO $pdo) {
        try {
            $pdo->exec("ALTER TABLE categories DROP COLUMN is_article_category");
        } catch (\Throwable $e) {
        }
    },
];
