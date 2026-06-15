<?php

declare(strict_types=1);

return [
    'up' => function (\PDO $pdo) {
        $driver = 'local';
        $row = $pdo->query("SELECT value FROM settings WHERE `key` = 'storage_s3_enabled' LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        if ($row && ($row['value'] ?? '') === '1') {
            $driver = 'aws_s3';
        }

        $settings = [
            ['storage_driver', $driver, 'storage'],
            ['storage_aws_s3_key', '', 'storage'],
            ['storage_aws_s3_secret', '', 'storage'],
            ['storage_aws_s3_region', 'us-east-1', 'storage'],
            ['storage_aws_s3_bucket', '', 'storage'],
            ['storage_aws_s3_prefix', '', 'storage'],
            ['storage_aws_s3_cdn_url', '', 'storage'],
            ['storage_r2_key', '', 'storage'],
            ['storage_r2_secret', '', 'storage'],
            ['storage_r2_endpoint', '', 'storage'],
            ['storage_r2_bucket', '', 'storage'],
            ['storage_r2_prefix', '', 'storage'],
            ['storage_r2_cdn_url', '', 'storage'],
        ];
        foreach ($settings as $s) {
            $pdo->exec("INSERT IGNORE INTO settings (`key`, `value`, `group`) VALUES (" . $pdo->quote($s[0]) . ", " . $pdo->quote($s[1]) . ", " . $pdo->quote($s[2]) . ")");
        }

        if ($driver === 'aws_s3') {
            foreach (
                [
                    'storage_s3_key' => 'storage_aws_s3_key',
                    'storage_s3_secret' => 'storage_aws_s3_secret',
                    'storage_s3_region' => 'storage_aws_s3_region',
                    'storage_s3_bucket' => 'storage_aws_s3_bucket',
                    'storage_s3_prefix' => 'storage_aws_s3_prefix',
                    'storage_cdn_url' => 'storage_aws_s3_cdn_url',
                ] as $oldKey => $newKey
            ) {
                $st = $pdo->query("SELECT value FROM settings WHERE `key` = " . $pdo->quote($oldKey) . " LIMIT 1");
                if ($st && ($r = $st->fetch(\PDO::FETCH_ASSOC)) && ($r['value'] ?? '') !== '') {
                    $pdo->exec("UPDATE settings SET value = " . $pdo->quote($r['value']) . " WHERE `key` = " . $pdo->quote($newKey));
                }
            }
        }

        $st = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attachments' AND COLUMN_NAME = 'storage_driver'");
        if ((int) $st->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE attachments ADD COLUMN storage_driver VARCHAR(16) NOT NULL DEFAULT 'local' AFTER stored_name");
        }
    },
    'down' => function (\PDO $pdo) {
        foreach ([
            'storage_driver', 'storage_aws_s3_key', 'storage_aws_s3_secret', 'storage_aws_s3_region', 'storage_aws_s3_bucket', 'storage_aws_s3_prefix', 'storage_aws_s3_cdn_url',
            'storage_r2_key', 'storage_r2_secret', 'storage_r2_endpoint', 'storage_r2_bucket', 'storage_r2_prefix', 'storage_r2_cdn_url',
        ] as $key) {
            $pdo->exec("DELETE FROM settings WHERE `key` = " . $pdo->quote($key));
        }
        try {
            $pdo->exec("ALTER TABLE attachments DROP COLUMN storage_driver");
        } catch (\Throwable $e) {
        }
    },
];
