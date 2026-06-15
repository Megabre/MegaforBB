<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS import_id_map (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                source     VARCHAR(32)  NOT NULL DEFAULT 'xenforo',
                entity_type VARCHAR(64) NOT NULL,
                old_id     INT UNSIGNED NOT NULL,
                new_id     INT UNSIGNED NOT NULL,
                extra      JSON         DEFAULT NULL,
                UNIQUE KEY uq_source_entity_old (source, entity_type, old_id),
                INDEX idx_entity_new (entity_type, new_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS import_progress (
                id             INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                source         VARCHAR(32)  NOT NULL DEFAULT 'xenforo',
                step           VARCHAR(64)  NOT NULL,
                status         ENUM('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
                total_rows     INT UNSIGNED NOT NULL DEFAULT 0,
                processed_rows INT UNSIGNED NOT NULL DEFAULT 0,
                error_count    INT UNSIGNED NOT NULL DEFAULT 0,
                started_at     DATETIME     DEFAULT NULL,
                completed_at   DATETIME     DEFAULT NULL,
                UNIQUE KEY uq_source_step (source, step)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS import_errors (
                id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                source        VARCHAR(32)  NOT NULL DEFAULT 'xenforo',
                step          VARCHAR(64)  NOT NULL,
                old_id        INT UNSIGNED DEFAULT NULL,
                error_message TEXT         NOT NULL,
                raw_data      JSON         DEFAULT NULL,
                created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_source_step (source, step)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec("DROP TABLE IF EXISTS import_errors");
        $pdo->exec("DROP TABLE IF EXISTS import_progress");
        $pdo->exec("DROP TABLE IF EXISTS import_id_map");
    },
];
