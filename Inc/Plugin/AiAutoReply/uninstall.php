<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

if (!class_exists(DB::class)) {
    return;
}

$pdo = DB::connection()->getPdo();
$pdo->exec('DROP TABLE IF EXISTS ai_auto_reply_jobs');
