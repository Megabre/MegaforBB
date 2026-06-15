#!/usr/bin/env php
<?php

/**
 * MegaforBB CLI Migration Runner (Premium MVC)
 * Usage:
 *   php Forecor/bin/migrate.php           - Run pending migrations
 *   php Forecor/bin/migrate.php --rollback - Rollback last batch
 *   php Forecor/bin/migrate.php --status   - Show migration status
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Forbidden\n");
}

// Prevent accidental include/require from another script; only direct CLI execution is allowed.
$scriptFilename = isset($_SERVER['SCRIPT_FILENAME']) ? (string) $_SERVER['SCRIPT_FILENAME'] : '';
if ($scriptFilename === '' || realpath($scriptFilename) !== __FILE__) {
    fwrite(STDERR, "This script must be executed directly via CLI.\n");
    exit(1);
}

$basePath = dirname(__DIR__, 2);
if (!defined('MEGAFORBB_BASE_PATH')) {
    define('MEGAFORBB_BASE_PATH', $basePath);
}

require $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'env.php';
require $basePath . DIRECTORY_SEPARATOR . 'Library' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$forecorSymmod = $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'symmod' . DIRECTORY_SEPARATOR . 'symfony.php';
if (is_file($forecorSymmod)) {
    require $forecorSymmod;
}
require $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'config.php';

$forecorLaramod = $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'laramod' . DIRECTORY_SEPARATOR . 'laravel.php';
if (is_file($forecorLaramod)) {
    require $forecorLaramod;
}

use Illuminate\Database\Capsule\Manager as DB;

$pdo = DB::connection()->getPdo();
if (!$pdo) {
    fwrite(STDERR, "Database connection not available. Ensure forecor/laramod/laravel.php and config load correctly.\n");
    exit(1);
}

$migrationsDir = $basePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'migrations';

$createMigrationsTable = "
CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INT UNSIGNED NOT NULL DEFAULT 1,
    ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_migration (migration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

try {
    DB::statement($createMigrationsTable);
} catch (\Throwable $e) {
    fwrite(STDERR, "Failed to create migrations table: " . $e->getMessage() . "\n");
    exit(1);
}

function getRanMigrations(): array
{
    return DB::table('migrations')->orderBy('batch')->orderBy('id')->pluck('migration')->all();
}

function getMigrationFiles(string $dir): array
{
    if (!is_dir($dir)) {
        return [];
    }
    $files = [];
    foreach (scandir($dir) as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        if (pathinfo($f, PATHINFO_EXTENSION) === 'php') {
            $files[] = $f;
        }
    }
    sort($files);
    return $files;
}

function getNextBatch(): int
{
    $mx = DB::table('migrations')->max('batch');
    return $mx !== null ? (int)$mx + 1 : 1;
}

function getLastBatch(): ?int
{
    $mx = DB::table('migrations')->max('batch');
    return $mx !== null ? (int)$mx : null;
}

function getMigrationsInBatch(int $batch): array
{
    return DB::table('migrations')->where('batch', $batch)->orderByDesc('id')->pluck('migration')->all();
}

$rollback = in_array('--rollback', $argv ?? [], true);
$status = in_array('--status', $argv ?? [], true);

if ($status) {
    echo "Migration Status:\n";
    echo str_repeat('-', 60) . "\n";
    $ran = getRanMigrations();
    $files = getMigrationFiles($migrationsDir);
    foreach ($files as $f) {
        $name = pathinfo($f, PATHINFO_FILENAME);
        $done = in_array($name, $ran, true);
        echo ($done ? '[x]' : '[ ]') . ' ' . $name . "\n";
    }
    echo "\nRan: " . count($ran) . " / Total: " . count($files) . "\n";
    exit(0);
}

if ($rollback) {
    $batch = getLastBatch();
    if ($batch === null) {
        echo "Nothing to rollback.\n";
        exit(0);
    }
    $toRollback = getMigrationsInBatch($batch);
    if (empty($toRollback)) {
        echo "No migrations in last batch.\n";
        exit(0);
    }
    echo "Rolling back batch $batch (" . count($toRollback) . " migration(s))...\n";
    foreach (array_reverse($toRollback) as $name) {
        $file = $migrationsDir . DIRECTORY_SEPARATOR . $name . '.php';
        if (!is_file($file)) {
            echo "  Warning: migration file not found: $name\n";
            DB::table('migrations')->where('migration', $name)->delete();
            continue;
        }
        $migration = require $file;
        if (!is_array($migration) || empty($migration['down'])) {
            echo "  Warning: no 'down' for $name, removing from migrations table\n";
            DB::table('migrations')->where('migration', $name)->delete();
            continue;
        }
        $down = $migration['down'];
        if (is_callable($down)) {
            $down($pdo);
        } else {
            $sql = trim((string)$down);
            if ($sql !== '') {
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if ($stmt !== '') {
                        $pdo->exec($stmt);
                    }
                }
            }
        }
        DB::table('migrations')->where('migration', $name)->delete();
        echo "  Rolled back: $name\n";
    }
    echo "Rollback complete.\n";
    exit(0);
}

$ran = getRanMigrations();
$files = getMigrationFiles($migrationsDir);
$pending = array_filter($files, function ($f) use ($ran) {
    return !in_array(pathinfo($f, PATHINFO_FILENAME), $ran, true);
});

if (empty($pending)) {
    echo "No pending migrations.\n";
    exit(0);
}

$batch = getNextBatch();
echo "Running " . count($pending) . " migration(s) (batch $batch)...\n";

foreach ($pending as $file) {
    $name = pathinfo($file, PATHINFO_FILENAME);
    $path = $migrationsDir . DIRECTORY_SEPARATOR . $file;
    $migration = require $path;
    if (!is_array($migration) || empty($migration['up'])) {
        echo "  Skipping $name (no 'up')\n";
        continue;
    }
    $up = $migration['up'];
    if (is_callable($up)) {
        try {
            $up($pdo);
        } catch (\Throwable $e) {
            fwrite(STDERR, "  Error in $name: " . $e->getMessage() . "\n");
            exit(1);
        }
    } else {
        $sql = trim((string)$up);
        if ($sql !== '') {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
            }
        }
    }
    DB::table('migrations')->insert([
        'migration' => $name,
        'batch' => $batch,
        'ran_at' => date('Y-m-d H:i:s'),
    ]);
    echo "  Migrated: $name\n";
}

echo "Migration complete.\n";
