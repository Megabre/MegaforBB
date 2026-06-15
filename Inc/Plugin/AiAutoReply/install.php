<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

if (!class_exists(DB::class)) {
    throw new \RuntimeException('Database not initialized.');
}

$pdo = DB::connection()->getPdo();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS ai_auto_reply_jobs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        topic_id INT UNSIGNED NOT NULL,
        forum_id INT UNSIGNED NOT NULL,
        trigger_post_id BIGINT UNSIGNED NULL DEFAULT NULL,
        trigger_type VARCHAR(20) NOT NULL DEFAULT 'topic',
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        last_error VARCHAR(1000) NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        processed_at DATETIME NULL DEFAULT NULL,
        INDEX idx_ai_reply_status_created (status, created_at),
        INDEX idx_ai_reply_topic (topic_id),
        INDEX idx_ai_reply_trigger_post (trigger_post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
