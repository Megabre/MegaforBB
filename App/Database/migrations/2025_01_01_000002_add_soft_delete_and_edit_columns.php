<?php

declare(strict_types=1);

/**
 * Adds deleted_at, deleted_by to topics and posts; edited_at, edited_by, edit_count to posts.
 * Uses procedural checks to avoid errors when columns already exist.
 */
return [
    'up' => function (\PDO $pdo) {
        $columns = [
            ['posts', 'edited_at', 'DATETIME DEFAULT NULL'],
            ['posts', 'edited_by', 'INT UNSIGNED DEFAULT NULL'],
            ['posts', 'edit_count', 'INT UNSIGNED NOT NULL DEFAULT 0'],
            ['topics', 'deleted_at', 'DATETIME DEFAULT NULL'],
            ['topics', 'deleted_by', 'INT UNSIGNED DEFAULT NULL'],
            ['posts', 'deleted_at', 'DATETIME DEFAULT NULL'],
            ['posts', 'deleted_by', 'INT UNSIGNED DEFAULT NULL'],
        ];
        foreach ($columns as $c) {
            $table = $c[0];
            $col = $c[1];
            $def = $c[2];
            try {
                $st = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
                if ($st && $st->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
                }
            } catch (\Throwable $e) {
                // Column might already exist or table structure differs
            }
        }
    },
    'down' => function (\PDO $pdo) {
        $columns = [
            ['posts', 'edited_at'], ['posts', 'edited_by'], ['posts', 'edit_count'],
            ['topics', 'deleted_at'], ['topics', 'deleted_by'],
            ['posts', 'deleted_at'], ['posts', 'deleted_by'],
        ];
        foreach ($columns as $c) {
            try {
                $st = $pdo->query("SHOW COLUMNS FROM `{$c[0]}` LIKE '{$c[1]}'");
                if ($st && $st->rowCount() > 0) {
                    $pdo->exec("ALTER TABLE `{$c[0]}` DROP COLUMN `{$c[1]}`");
                }
            } catch (\Throwable $e) {
            }
        }
    }
];
