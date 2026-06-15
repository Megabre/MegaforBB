<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Veritabanı ve dosya yedekleme servisi.
 * Yedekler Content/storage/backups dizininde saklanır.
 * DB: mysqldump (tercih) veya PHP dump. Dosya: ZIP (uploads + Content/storage, backup/cache/views hariç).
 */
class BackupService
{
    private string $basePath;
    private string $backupDir;

    /** Yedek dosya adı önekleri (güvenlik: sadece bunlara izin) */
    private const PREFIX_DB = 'db_';
    private const PREFIX_FILES = 'files_';

    /** PHP limit aşımı uyarısı için tahmini güvenli boyut (byte) - timeout riski */
    private const SAFE_SIZE_THRESHOLD = 50 * 1024 * 1024; // 50 MB

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim(str_replace('\\', '/', $basePath), '/');
        $this->backupDir = $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, 'Content/storage/backups');
    }

    /**
     * Yedek dizinini oluşturur ve mutlak path döner.
     */
    public function ensureBackupDir(): string
    {
        $dir = $this->basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, 'Content/storage/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Mevcut yedekleri listeler. [ 'type' => 'db'|'files', 'filename' => ..., 'size' => int, 'date' => 'Y-m-d H:i:s' ]
     *
     * @return list<array{type: string, filename: string, size: int, date: string}>
     */
    public function listBackups(): array
    {
        $dir = $this->ensureBackupDir();
        $list = [];
        $iter = @scandir($dir);
        if ($iter === false) {
            return $list;
        }
        foreach ($iter as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) {
                continue;
            }
            $type = 'other';
            if (strpos($name, self::PREFIX_DB) === 0) {
                $type = 'db';
            } elseif (strpos($name, self::PREFIX_FILES) === 0) {
                $type = 'files';
            }
            $list[] = [
                'type'     => $type,
                'filename' => $name,
                'size'     => (int) @filesize($path),
                'date'     => date('Y-m-d H:i:s', (int) @filemtime($path)),
            ];
        }
        usort($list, static fn ($a, $b) => strcmp($b['date'], $a['date']));
        return $list;
    }

    /**
     * Veritabanı yedeği oluşturur. Önce mysqldump dener, yoksa PHP ile dump.
     *
     * @return array{success: bool, filename: string|null, message: string}
     */
    public function createDbBackup(): array
    {
        $this->ensureBackupDir();
        $filename = self::PREFIX_DB . date('Y-m-d_H-i-s') . '.sql';
        $path = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $config = core_config('database.connections.mysql');
        if (empty($config['database'])) {
            return ['success' => false, 'filename' => null, 'message' => 'Veritabanı yapılandırması bulunamadı.'];
        }

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 3306);
        $database = $config['database'];
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        // 1) mysqldump ile (tercih) — başarısız veya 0 byte ise PHP fallback kullanılır
        $mysqldump = $this->findMysqldump();
        if ($mysqldump !== null) {
            $cmd = $this->buildMysqldumpCommand($mysqldump, $host, $port, $username, $password, $database);
            $handle = @popen($cmd, 'r');
            if (is_resource($handle)) {
                $fp = @fopen($path, 'wb');
                if ($fp) {
                    while (!feof($handle)) {
                        $chunk = fread($handle, 65536);
                        if ($chunk !== false) {
                            fwrite($fp, $chunk);
                        }
                    }
                    fclose($fp);
                    pclose($handle);
                    clearstatcache(true, $path);
                    if (is_file($path) && filesize($path) > 0) {
                        return ['success' => true, 'filename' => $filename, 'message' => 'Veritabanı yedeği oluşturuldu.'];
                    }
                    @unlink($path);
                } else {
                    pclose($handle);
                }
            }
        }

        // 2) PHP fallback: tabloları tek tek dump
        try {
            $pdo = DB::connection()->getPdo();
            $tables = $this->getTables($pdo, $database);
            $out = "-- MegaforBB DB Backup " . date('Y-m-d H:i:s') . "\n-- PHP fallback\nSET NAMES utf8mb4;\n\n";
            foreach ($tables as $table) {
                $create = $this->getCreateTable($pdo, $table);
                if ($create !== '') {
                    $out .= "DROP TABLE IF EXISTS `" . str_replace('`', '``', $table) . "`;\n";
                    $out .= $create . ";\n\n";
                }
                $rows = $this->getTableData($pdo, $table);
                if ($rows !== '') {
                    $out .= $rows . "\n";
                }
            }
            if (file_put_contents($path, $out) !== false) {
                return ['success' => true, 'filename' => $filename, 'message' => 'Veritabanı yedeği (PHP) oluşturuldu.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'filename' => null, 'message' => 'PHP dump hatası: ' . $e->getMessage()];
        }

        return ['success' => false, 'filename' => null, 'message' => 'Yedek dosyası yazılamadı.'];
    }

    /**
     * Dosya yedeği (ZIP): .env'in bulunduğu proje kökünden tüm sistem.
     * Hariç: Content/storage/backups, cache, views, .git, node_modules.
     *
     * @return array{success: bool, filename: string|null, message: string}
     */
    public function createFileBackup(): array
    {
        $this->ensureBackupDir();
        $filename = self::PREFIX_FILES . date('Y-m-d_H-i-s') . '.zip';
        $path = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        if (!extension_loaded('zip')) {
            return [
                'success'  => false,
                'filename' => null,
                'message'  => 'Zip (ZipArchive) PHP eklentisi yüklü değil. php.ini içinde extension=zip satırının başındaki noktalı virgülü (;) kaldırıp kaydedin ve web sunucusunu yeniden başlatın. Laragon: sağ tık → PHP → php.ini → "extension=zip" satırı etkin olmalı.',
            ];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'filename' => null, 'message' => 'ZIP dosyası oluşturulamadı.'];
        }

        $basePath = rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $backupDirReal = realpath($this->backupDir);
        if ($backupDirReal === false) {
            $backupDirReal = '';
        }
        // Kök dizinden tüm sistem; hariç: yedekler, cache, views, .git, node_modules
        $excludeDirNames = ['backups', 'cache', 'views', '.git', 'node_modules'];
        $added = 0;
        $this->addRootToZip($zip, $basePath, $backupDirReal, $excludeDirNames, $added);

        $zip->close();

        if ($added === 0) {
            @unlink($path);
            return ['success' => false, 'filename' => null, 'message' => 'Yedeklenecek dosya bulunamadı.'];
        }

        return ['success' => true, 'filename' => $filename, 'message' => 'Dosya yedeği oluşturuldu.'];
    }

    /**
     * Güvenli yedek path: sadece backup dizini içinde ve izin verilen önekler.
     */
    public function getBackupPath(string $filename): ?string
    {
        $filename = basename($filename);
        if ($filename === '' || preg_match('/[^a-zA-Z0-9_.\-]/', $filename) !== 0) {
            return null;
        }
        if (strpos($filename, self::PREFIX_DB) !== 0 && strpos($filename, self::PREFIX_FILES) !== 0) {
            return null;
        }
        $path = $this->backupDir . DIRECTORY_SEPARATOR . $filename;
        $realBackup = realpath($this->backupDir);
        $realPath = realpath($path);
        if ($realBackup === false || $realPath === false || strpos($realPath, $realBackup) !== 0) {
            return null;
        }
        return $path;
    }

    public function deleteBackup(string $filename): bool
    {
        $path = $this->getBackupPath($filename);
        if ($path === null || !is_file($path)) {
            return false;
        }
        return @unlink($path);
    }

    /**
     * Tüm yedekleri siler.
     *
     * @return int Silinen dosya sayısı
     */
    public function deleteAllBackups(): int
    {
        $list = $this->listBackups();
        $count = 0;
        foreach ($list as $item) {
            if ($this->deleteBackup($item['filename'])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * PHP limit bilgisi (uyarı için).
     *
     * @return array{max_execution_time: int, memory_limit_bytes: int, memory_limit: string, warning: bool}
     */
    public function getPhpLimits(): array
    {
        $maxTime = (int) ini_get('max_execution_time');
        $mem = ini_get('memory_limit');
        $memBytes = $this->parseMemoryLimit($mem);
        $warning = $maxTime > 0 && $maxTime < 300; // 5 dakikadan az ise uyarı
        return [
            'max_execution_time'  => $maxTime,
            'memory_limit_bytes'  => $memBytes,
            'memory_limit'       => $mem,
            'warning'            => $warning,
        ];
    }

    /**
     * Tahmini veritabanı boyutu (byte). Büyükse timeout uyarısı verilebilir.
     */
    public function estimateDbSize(): int
    {
        try {
            $db = core_config('database.connections.mysql.database');
            if (empty($db)) {
                return 0;
            }
            $rows = DB::select("SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?", [$db]);
            $size = (int) ($rows[0]->size ?? 0);
            return $size;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Tahmini dosya yedeği boyutu: kök dizin, backups/cache/views/.git/node_modules hariç.
     */
    public function estimateFilesSize(): int
    {
        $basePath = rtrim($this->basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $backupDirReal = realpath($this->backupDir);
        if ($backupDirReal === false) {
            $backupDirReal = '';
        }
        $excludeDirNames = ['backups', 'cache', 'views', '.git', 'node_modules'];
        return $this->rootSizeExcluding($basePath, $backupDirReal, $excludeDirNames);
    }

    /**
     * Kök dizin boyutunu hesaplar; backupDirReal altı ve excludeDirNames segmentleri hariç.
     */
    private function rootSizeExcluding(string $basePath, string $backupDirReal, array $excludeDirNames): int
    {
        $total = 0;
        $baseLen = strlen($basePath);
        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iter as $item) {
                if (!$item->isFile()) {
                    continue;
                }
                $path = $item->getPathname();
                if ($backupDirReal !== '' && strpos(str_replace('\\', '/', $path), str_replace('\\', '/', $backupDirReal)) === 0) {
                    continue;
                }
                $rel = substr($path, $baseLen);
                $rel = str_replace('\\', '/', $rel);
                $segments = explode('/', $rel);
                foreach ($segments as $seg) {
                    if (in_array($seg, $excludeDirNames, true)) {
                        continue 2;
                    }
                }
                $total += $item->getSize();
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $total;
    }

    /**
     * Boyut PHP timeout/memory limit için riskli mi?
     */
    public function isSizeRisky(int $estimatedBytes): bool
    {
        return $estimatedBytes >= self::SAFE_SIZE_THRESHOLD;
    }

    /**
     * mysqldump komut satırı oluşturur. Windows'ta --password= kullanır (MYSQL_PWD bazen çalışmaz).
     */
    private function buildMysqldumpCommand(string $mysqldump, string $host, int $port, string $username, string $password, string $database): string
    {
        $parts = [
            escapeshellcmd($mysqldump),
            '--host=' . escapeshellarg($host),
            '--port=' . $port,
            '--user=' . escapeshellarg($username),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--set-charset',
            '--default-character-set=utf8mb4',
            escapeshellarg($database),
        ];
        if ($password !== '') {
            $parts[] = '--password=' . escapeshellarg($password);
        }
        $cmd = implode(' ', $parts);
        if (DIRECTORY_SEPARATOR === '\\') {
            $cmd .= ' 2>NUL';
        } else {
            $cmd .= ' 2>/dev/null';
        }
        return $cmd;
    }

    /**
     * PATH'te mysqldump var mı kontrol eder. Linux/cPanel/Plesk/Hostinger'da genelde PATH'tedir.
     * Bulunamazsa null döner, PHP fallback kullanılır. İşletim sistemine özel sabit path kullanılmaz.
     */
    private function findMysqldump(): ?string
    {
        $out = [];
        @exec('mysqldump --version 2>&1', $out);
        if (!empty($out)) {
            return 'mysqldump';
        }
        return null;
    }

    /** @return list<string> */
    private function getTables(\PDO $pdo, string $database): array
    {
        $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $pdo->quote($database) . " ORDER BY TABLE_NAME");
        $list = [];
        while (($row = $stmt->fetch(\PDO::FETCH_NUM)) !== false) {
            $list[] = $row[0];
        }
        return $list;
    }

    private function getCreateTable(\PDO $pdo, string $table): string
    {
        $stmt = $pdo->query("SHOW CREATE TABLE `" . str_replace('`', '``', $table) . "`");
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return $row ? (string) $row[1] : '';
    }

    private function getTableData(\PDO $pdo, string $table): string
    {
        $stmt = $pdo->query("SELECT * FROM `" . str_replace('`', '``', $table) . "`");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return '';
        }
        $out = "INSERT INTO `" . str_replace('`', '``', $table) . "` (";
        $cols = array_keys($rows[0]);
        foreach ($cols as $c) {
            $out .= "`" . str_replace('`', '``', $c) . "`,";
        }
        $out = rtrim($out, ',') . ") VALUES\n";
        $lines = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($row as $v) {
                $vals[] = $v === null ? 'NULL' : $pdo->quote(is_string($v) ? $v : (string) $v);
            }
            $lines[] = '(' . implode(',', $vals) . ')';
        }
        $out .= implode(",\n", $lines) . ";\n\n";
        return $out;
    }

    /**
     * Proje kökünden (basePath) tüm dosya/klasörleri ZIP'e ekler.
     * backupDirReal path'i içinde kalanları ve excludeDirNames ile eşleşen segmentleri atlar.
     */
    private function addRootToZip(\ZipArchive $zip, string $basePath, string $backupDirReal, array $excludeDirNames, int &$added): void
    {
        $baseLen = strlen($basePath);
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $item) {
            $path = $item->getPathname();
            if ($backupDirReal !== '' && strpos(str_replace('\\', '/', $path), str_replace('\\', '/', $backupDirReal)) === 0) {
                continue;
            }
            $rel = substr($path, $baseLen);
            $rel = str_replace('\\', '/', $rel);
            if ($rel === '') {
                continue;
            }
            $segments = explode('/', $rel);
            $skip = false;
            foreach ($segments as $seg) {
                if (in_array($seg, $excludeDirNames, true)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }
            if ($item->isDir()) {
                $zip->addEmptyDir($rel);
            } else {
                $zip->addFile($path, $rel);
                $added++;
            }
        }
    }

    private function addDirToZip(\ZipArchive $zip, string $dir, string $zipPrefix, array $excludeDirs, int &$added): void
    {
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $baseLen = strlen($dir);
        foreach ($iter as $item) {
            $path = $item->getPathname();
            $rel = substr($path, $baseLen);
            $rel = str_replace('\\', '/', $rel);
            $rel = ltrim($rel, '/');
            if ($rel === '') {
                continue;
            }
            $first = strpos($rel, '/') !== false ? substr($rel, 0, strpos($rel, '/')) : $rel;
            if (in_array($first, $excludeDirs, true)) {
                continue;
            }
            $zipPath = $zipPrefix . '/' . $rel;
            if ($item->isDir()) {
                $zip->addEmptyDir($zipPath);
            } else {
                $zip->addFile($path, $zipPath);
                $added++;
            }
        }
    }

    private function dirSize(string $dir): int
    {
        $size = 0;
        try {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iter as $item) {
                if ($item->isFile()) {
                    $size += $item->getSize();
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $size;
    }

    private function parseMemoryLimit(string $value): int
    {
        $value = trim($value);
        $n = (int) $value;
        $unit = strtoupper(substr($value, strlen((string) $n)));
        $mult = 1;
        if ($unit === 'G') {
            $mult = 1024 * 1024 * 1024;
        } elseif ($unit === 'M') {
            $mult = 1024 * 1024;
        } elseif ($unit === 'K') {
            $mult = 1024;
        }
        return $n * $mult;
    }
}
