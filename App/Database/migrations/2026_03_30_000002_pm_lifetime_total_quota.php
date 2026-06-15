<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $pdo->exec('ALTER TABLE roles ADD COLUMN pm_lifetime_total_quota INT UNSIGNED NOT NULL DEFAULT 0 AFTER pm_daily_receive_limit;');
    },
    'down' => function (\PDO $pdo) {
        $pdo->exec('ALTER TABLE roles DROP COLUMN pm_lifetime_total_quota;');
    },
];
