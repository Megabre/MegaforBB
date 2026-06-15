<?php

declare(strict_types=1);

/**
 * Tags master table + topic_tags junction (topic_id, tag_id).
 * If topic_tags already exists with tag_name, migrate to tags + topic_id/tag_id.
 */
return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tags (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(120) NOT NULL,
                description VARCHAR(500) DEFAULT NULL,
                use_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_tag_slug (slug),
                INDEX idx_tag_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $hasOldTable = false;
        try {
            $st = $pdo->query("SELECT 1 FROM topic_tags LIMIT 1");
            $cols = [];
            foreach (range(0, $st->columnCount() - 1) as $i) {
                $cols[] = $st->getColumnMeta($i)['name'] ?? '';
            }
            $hasOldTable = in_array('tag_name', $cols, true);
        } catch (\Throwable $e) {
        }

        if ($hasOldTable) {
            $st = $pdo->query("SELECT DISTINCT tag_name FROM topic_tags WHERE TRIM(tag_name) != ''");
            $distinct = $st->fetchAll(\PDO::FETCH_COLUMN);
            $insTag = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
            foreach ($distinct as $name) {
                $name = mb_substr(trim($name), 0, 100);
                $slug = \Forecor\Core\Str::slug($name) ?: 'tag-' . uniqid();
                try {
                    $insTag->execute([$name, $slug]);
                } catch (\PDOException $e) {
                    $insTag->execute([$name, $slug . '-' . uniqid()]);
                }
            }
            $pdo->exec("CREATE TABLE topic_tags_new (
                topic_id INT UNSIGNED NOT NULL,
                tag_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (topic_id, tag_id),
                INDEX idx_tt_topic (topic_id),
                INDEX idx_tt_tag (tag_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $pdo->exec("
                INSERT IGNORE INTO topic_tags_new (topic_id, tag_id)
                SELECT tt.topic_id, t.id
                FROM topic_tags tt
                JOIN tags t ON t.name = tt.tag_name
            ");
            $pdo->exec("DROP TABLE topic_tags");
            $pdo->exec("RENAME TABLE topic_tags_new TO topic_tags");
        } else {
            try {
                $pdo->query("SELECT 1 FROM topic_tags LIMIT 1");
            } catch (\Throwable $e) {
                $pdo->exec("
                    CREATE TABLE topic_tags (
                        topic_id INT UNSIGNED NOT NULL,
                        tag_id INT UNSIGNED NOT NULL,
                        PRIMARY KEY (topic_id, tag_id),
                        INDEX idx_tt_topic (topic_id),
                        INDEX idx_tt_tag (tag_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        }

        try {
            $pdo->exec("UPDATE tags t SET use_count = (SELECT COUNT(*) FROM topic_tags tt WHERE tt.tag_id = t.id)");
        } catch (\Throwable $e) {
        }
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS topic_tags");
        $pdo->exec("DROP TABLE IF EXISTS tags");
    },
];
