<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Güvenlik audit log: veritabanı kullanmadan, güvenli dizinde JSON (NDJSON) dosyalarına yazar.
 * Her dosya 5000 kayıttan sonra securelog_1.php, securelog_2.php ... şeklinde rotasyona gider.
 * Dosya başı "<?php exit; ?>" ile web'den doğrudan okunması etkisizleştirilir.
 */
final class SecurityLogger
{
    private const LOG_DIR_NAME = 'secure_logs';
    private const CURRENT_FILE = 'securelog.php';
    private const ROTATED_PREFIX = 'securelog_';
    private const ROTATED_SUFFIX = '.php';
    private const MAX_ENTRIES_PER_FILE = 5000;
    private const PHP_GUARD = "<?php exit; ?>\n";

    private static ?string $logDir = null;
    private static bool $enabled = true;

    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    /**
     * Olay kaydet. Context'e otomatik eklenir: ip, uri, method, user_agent, ts.
     * Örnek: SecurityLogger::log('xss_attempt', ['user_id' => 1, 'username' => 'x', 'snippet' => '...']);
     */
    public static function log(string $event, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }
        $entry = [
            'ts' => time(),
            'event' => $event,
            'ip' => SecurityService::clientIp(),
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        foreach ($context as $k => $v) {
            if ($v !== null && $k !== 'ts' && $k !== 'event') {
                $entry[$k] = $v;
            }
        }
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
        $dir = self::getLogDir();
        if ($dir === null) {
            return;
        }
        $currentFile = $dir . DIRECTORY_SEPARATOR . self::CURRENT_FILE;
        if (!is_file($currentFile)) {
            self::ensureDirAndGuard($dir, $currentFile);
        }
        @file_put_contents($currentFile, $line, FILE_APPEND | LOCK_EX);
        self::rotateIfNeeded($currentFile);

        if (class_exists(\App\Services\AnalyticsLogger::class) && \App\Services\AnalyticsLogger::isEnabled()) {
            \App\Services\AnalyticsLogger::log('security', $entry);
        }
    }

    /**
     * Son kayıtları oku (en yeni üstte). retention_days dışındakiler filtrelenir.
     * @return array<int, array<string, mixed>>
     */
    public static function read(int $limit = 500, int $retentionDays = 7): array
    {
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return [];
        }
        $cutoff = time() - ($retentionDays * 86400);
        $all = [];
        $files = self::getLogFilesInOrder($dir);
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '<?php') === 0) {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || empty($decoded['ts'])) {
                    continue;
                }
                if ((int) $decoded['ts'] < $cutoff) {
                    continue;
                }
                $all[] = $decoded;
            }
        }
        usort($all, fn ($a, $b) => ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0));
        return array_slice($all, 0, $limit);
    }

    /**
     * Retention süresi dışındaki kayıtları temizler: güncel dosyadan eski satırları siler, tamamen eski rotasyon dosyalarını kaldırır.
     * Temp dosyaya yazıp rename ile günceller (kilitleme/izin sorunlarını önler).
     */
    public static function purge(int $retentionDays = 7): void
    {
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return;
        }
        clearstatcache(true);
        $cutoff = time() - ($retentionDays * 86400);
        $currentPath = $dir . DIRECTORY_SEPARATOR . self::CURRENT_FILE;

        if (is_file($currentPath) && is_readable($currentPath)) {
            $content = @file_get_contents($currentPath);
            if ($content !== false) {
                $lines = explode("\n", $content);
                $keep = [self::PHP_GUARD];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '<?php') === 0) {
                        continue;
                    }
                    $decoded = json_decode($line, true);
                    if (is_array($decoded) && !empty($decoded['ts']) && (int) $decoded['ts'] >= $cutoff) {
                        $keep[] = $line;
                    }
                }
                if (count($keep) <= 1) {
                    @unlink($currentPath);
                } else {
                    $newContent = implode("\n", $keep) . "\n";
                    $tmpPath = $dir . DIRECTORY_SEPARATOR . '.securelog_tmp_' . uniqid('', true);
                    if (@file_put_contents($tmpPath, $newContent, LOCK_EX) !== false) {
                        @unlink($currentPath);
                        @rename($tmpPath, $currentPath);
                    } else {
                        @unlink($tmpPath);
                    }
                }
            }
        }

        $rotated = glob($dir . DIRECTORY_SEPARATOR . self::ROTATED_PREFIX . '*' . self::ROTATED_SUFFIX) ?: [];
        foreach ($rotated as $path) {
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            $lines = array_filter(explode("\n", $content), fn ($l) => trim($l) !== '' && strpos(trim($l), '<?php') !== 0);
            $allOld = true;
            foreach ($lines as $line) {
                $decoded = json_decode(trim($line), true);
                if (is_array($decoded) && !empty($decoded['ts']) && (int) $decoded['ts'] >= $cutoff) {
                    $allOld = false;
                    break;
                }
            }
            if ($allOld && count($lines) > 0) {
                @unlink($path);
            }
        }
    }

    /**
     * Tüm log dosyalarını siler (acil durum / saldırı sonrası). Klasör kalır; dosyalar sonraki log() çağrısında yeniden oluşturulur.
     */
    public static function deleteAll(): void
    {
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return;
        }
        clearstatcache(true);
        $currentPath = $dir . DIRECTORY_SEPARATOR . self::CURRENT_FILE;
        if (is_file($currentPath)) {
            @unlink($currentPath);
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . self::ROTATED_PREFIX . '*' . self::ROTATED_SUFFIX) ?: [] as $path) {
            @unlink($path);
        }
        self::$logDir = null;
    }

    /** Eski tail() uyumluluğu: satır metni döner. $retentionDays 0 ise varsayılan 7. */
    public static function tail(int $lines = 500, int $retentionDays = 0): array
    {
        $retentionDays = $retentionDays > 0 ? $retentionDays : 7;
        $entries = self::read($lines, $retentionDays);
        return array_map([self::class, 'formatLineForTail'], $entries);
    }

    public static function formatLineForTail(array $e): string
    {
        $ts = date('Y-m-d H:i:s', (int) ($e['ts'] ?? 0));
        $event = $e['event'] ?? 'unknown';
        $ip = $e['ip'] ?? '';
        $user = isset($e['username']) ? $e['username'] : (isset($e['user_id']) ? 'user_id:' . $e['user_id'] : 'misafir');
        $detail = self::eventSummary($e);
        return $ts . ' [' . $event . '] ' . $user . ' | ' . $ip . ' | ' . $detail;
    }

    /** Event type label for admin table. */
    public static function eventLabel(string $event): string
    {
        $labels = [
            'rate_limit_exceeded' => lang('security_log.rate_limit'),
            'block_applied' => lang('security_log.block_applied'),
            'login_failed' => lang('security_log.login_failed'),
            'csrf_failed' => lang('security_log.csrf_failed'),
            'xss_attempt' => lang('security_log.xss_attempt'),
            'rtbh_match' => lang('security_log.rtbh_match'),
            'rtbh_login_blocked' => lang('security_log.rtbh_login_blocked'),
            'rtbh_register_blocked' => lang('security_log.rtbh_register_blocked'),
        ];
        return $labels[$event] ?? $event;
    }

    /** Short summary per event type. */
    public static function eventSummary(array $e): string
    {
        $event = $e['event'] ?? '';
        $parts = [];
        switch ($event) {
            case 'rate_limit_exceeded':
                $parts[] = lang('security_log.rate_limit');
                if (isset($e['count']) && isset($e['limit'])) {
                    $parts[] = lang('security_log.summary_requests', ['count' => $e['count'], 'limit' => $e['limit']]);
                }
                if (!empty($e['block_minutes'])) {
                    $parts[] = lang('security_log.summary_block_dk', ['min' => $e['block_minutes']]);
                }
                break;
            case 'block_applied':
                $parts[] = lang('security_log.block_applied');
                if (!empty($e['violations'])) {
                    $parts[] = lang('security_log.summary_violations', ['n' => $e['violations']]);
                }
                if (!empty($e['block_minutes'])) {
                    $parts[] = lang('security_log.summary_block_min', ['min' => $e['block_minutes']]);
                }
                break;
            case 'login_failed':
                $parts[] = lang('security_log.login_failed');
                if (!empty($e['login'])) {
                    $parts[] = 'login: ' . substr((string) $e['login'], 0, 30);
                }
                break;
            case 'csrf_failed':
                $parts[] = lang('security_log.summary_csrf_token');
                if (!empty($e['token_id'])) {
                    $parts[] = 'form: ' . $e['token_id'];
                }
                break;
            case 'xss_attempt':
                $parts[] = lang('security_log.summary_xss_detected');
                if (!empty($e['snippet'])) {
                    $parts[] = substr((string) $e['snippet'], 0, 80) . '...';
                }
                break;
            case 'rtbh_match':
                $parts[] = lang('security_log.rtbh_match');
                if (!empty($e['phase'])) {
                    $parts[] = 'phase: ' . $e['phase'];
                }
                if (!empty($e['client_ip'])) {
                    $parts[] = 'ip: ' . $e['client_ip'];
                }
                if (!empty($e['username'])) {
                    $parts[] = 'user: ' . substr((string) $e['username'], 0, 40);
                }
                break;
            case 'rtbh_login_blocked':
            case 'rtbh_register_blocked':
                $parts[] = $event === 'rtbh_login_blocked' ? lang('security_log.rtbh_login_blocked') : lang('security_log.rtbh_register_blocked');
                if (!empty($e['client_ip'])) {
                    $parts[] = 'ip: ' . $e['client_ip'];
                }
                break;
            default:
                $parts[] = json_encode(array_diff_key($e, array_flip(['ts', 'event', 'ip', 'uri', 'method', 'user_agent'])), JSON_UNESCAPED_UNICODE);
                break;
        }
        $uri = $e['uri'] ?? '';
        if ($uri !== '') {
            $parts[] = 'URI: ' . substr($uri, 0, 60);
        }
        return implode(' | ', $parts);
    }

    private static function getLogDir(): ?string
    {
        if (self::$logDir !== null) {
            return self::$logDir;
        }
        $base = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : (dirname(__DIR__, 2) ?: getcwd());
        $dir = $base . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . self::LOG_DIR_NAME;
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        if (!is_dir($dir)) {
            return null;
        }
        $index = $dir . DIRECTORY_SEPARATOR . 'index.html';
        if (!is_file($index)) {
            @file_put_contents($index, '');
        }
        self::$logDir = $dir;
        return self::$logDir;
    }

    private static function ensureDirAndGuard(string $dir, string $currentFile): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        if (!is_file($currentFile)) {
            @file_put_contents($currentFile, self::PHP_GUARD, LOCK_EX);
        }
    }

    private static function rotateIfNeeded(string $currentFilePath): void
    {
        $content = @file_get_contents($currentFilePath);
        if ($content === false) {
            return;
        }
        $lines = array_filter(explode("\n", $content), fn ($l) => trim($l) !== '' && strpos(trim($l), '<?php') !== 0);
        if (count($lines) < self::MAX_ENTRIES_PER_FILE) {
            return;
        }
        $dir = dirname($currentFilePath);
        $nextIndex = 1;
        foreach (glob($dir . DIRECTORY_SEPARATOR . self::ROTATED_PREFIX . '*' . self::ROTATED_SUFFIX) ?: [] as $f) {
            if (preg_match('/' . preg_quote(self::ROTATED_PREFIX, '/') . '(\d+)' . preg_quote(self::ROTATED_SUFFIX, '/') . '$/', $f, $m)) {
                $n = (int) $m[1];
                if ($n >= $nextIndex) {
                    $nextIndex = $n + 1;
                }
            }
        }
        $rotatedPath = $dir . DIRECTORY_SEPARATOR . self::ROTATED_PREFIX . $nextIndex . self::ROTATED_SUFFIX;
        @rename($currentFilePath, $rotatedPath);
        @file_put_contents($currentFilePath, self::PHP_GUARD, LOCK_EX);
    }

    /** Önce güncel dosya, sonra numaraya göre eski rotasyon dosyaları (büyük numara = daha yeni). */
    private static function getLogFilesInOrder(string $dir): array
    {
        $current = $dir . DIRECTORY_SEPARATOR . self::CURRENT_FILE;
        $list = [];
        if (is_file($current)) {
            $list[] = $current;
        }
        $rotated = glob($dir . DIRECTORY_SEPARATOR . self::ROTATED_PREFIX . '*' . self::ROTATED_SUFFIX) ?: [];
        usort($rotated, function ($a, $b) {
            $aNum = (int) preg_replace('/\D/', '', $a);
            $bNum = (int) preg_replace('/\D/', '', $b);
            return $bNum <=> $aNum;
        });
        foreach ($rotated as $path) {
            $list[] = $path;
        }
        return $list;
    }
}
