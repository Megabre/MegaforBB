<?php

declare(strict_types=1);

/**
 * Konu ön ekleri: ikon, hex renkler; forum ve kategori bazlı önek listesi (pivot).
 */
return [
    'up' => function (\PDO $pdo): void {
        $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $hasCol = static function (\PDO $pdo, string $table, string $col) use ($db): bool {
            $st = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $st->execute([$db, $table, $col]);

            return (int) $st->fetchColumn() > 0;
        };

        if (!$hasCol($pdo, 'topic_prefixes', 'icon_class')) {
            $pdo->exec("ALTER TABLE topic_prefixes ADD COLUMN icon_class VARCHAR(64) NULL DEFAULT NULL AFTER css_class");
        }
        if (!$hasCol($pdo, 'topic_prefixes', 'badge_bg')) {
            $pdo->exec("ALTER TABLE topic_prefixes ADD COLUMN badge_bg VARCHAR(7) NULL DEFAULT NULL AFTER icon_class");
        }
        if (!$hasCol($pdo, 'topic_prefixes', 'badge_text')) {
            $pdo->exec("ALTER TABLE topic_prefixes ADD COLUMN badge_text VARCHAR(7) NULL DEFAULT NULL AFTER badge_bg");
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS forum_topic_prefix (
                forum_id INT UNSIGNED NOT NULL,
                prefix_id INT UNSIGNED NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (forum_id, prefix_id),
                KEY idx_forum_topic_prefix_forum (forum_id),
                KEY idx_forum_topic_prefix_prefix (prefix_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS category_topic_prefix (
                category_id INT UNSIGNED NOT NULL,
                prefix_id INT UNSIGNED NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (category_id, prefix_id),
                KEY idx_category_topic_prefix_cat (category_id),
                KEY idx_category_topic_prefix_prefix (prefix_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $pdo): void {
        try {
            $pdo->exec('DROP TABLE IF EXISTS forum_topic_prefix');
            $pdo->exec('DROP TABLE IF EXISTS category_topic_prefix');
        } catch (\Throwable $e) {
        }
        foreach (['badge_text', 'badge_bg', 'icon_class'] as $col) {
            try {
                $pdo->exec("ALTER TABLE topic_prefixes DROP COLUMN {$col}");
            } catch (\Throwable $e) {
            }
        }
    },
];
