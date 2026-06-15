<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Canlı trafik / ziyaretçi / sistem olay günlüğü. Veritabanı kullanmadan dosya tabanlı (NDJSON).
 * Bot tespiti, ziyaretçi, giriş/çıkış ve güvenlik olayları tek akışta. Admin panelden açılıp kapatılabilir.
 */
final class AnalyticsLogger
{
    private const LOG_DIR_NAME = 'analytics_logs';
    private const CURRENT_FILE = 'trafficlog.php';
    private const ROTATED_PREFIX = 'trafficlog_';
    private const ROTATED_SUFFIX = '.php';
    private const MAX_ENTRIES_PER_FILE = 5000;
    private const PHP_GUARD = "<?php exit; ?>\n";

    /** Bilinen bot User-Agent parçaları (küçük harf). */
    private const BOT_PATTERNS = [
        'googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'duckduckbot',
        'slurp', 'exabot', 'facebot', 'facebookexternalhit', 'twitterbot',
        'rogerbot', 'linkedinbot', 'embedly', 'quora link preview',
        'showyoubot', 'outbrain', 'pinterest', 'slackbot', 'vkshare',
        'w3c_validator', 'validator.nu', 'feedvalidator', 'python-requests',
        'curl', 'libwww', 'go-http-client', 'java/', 'apache-httpclient',
        'bot', 'crawler', 'spider', 'crawling', 'scanner', 'headless',
        'phantom', 'selenium', 'webdriver', 'gtmetrix', 'pingdom',
        'uptimerobot', 'monitor', 'check', 'archive.org_bot',
    ];

    private static ?string $logDir = null;
    private static bool $enabled = false;

    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Bot detection by User-Agent. ['bot' => bool, 'name' => string|null]
     */
    public static function detectBot(string $userAgent): array
    {
        $ua = strtolower($userAgent);
        if ($ua === '') {
            return ['bot' => false, 'name' => null];
        }
        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($ua, $pattern)) {
                return ['bot' => true, 'name' => $pattern];
            }
        }
        return ['bot' => false, 'name' => null];
    }

    private const RECENT_VISITS_FILE = 'recent_visits.php';

    /**
     * Her istek için çağrılır: ziyaretçi veya bot kaydı. Aynı IP/üye saklama süresi içinde tek sefer "ziyaret" loglanır.
     * @param int|null $retentionMinutes Saklama süresi (dakika); aynı ziyaretçi bu süre içinde tekrar loglanmaz.
     */
    public static function logRequest(string $ip, string $uri, string $method, string $userAgent, $user = null, ?int $retentionMinutes = null): void
    {
        if (!self::$enabled) {
            return;
        }
        $path = parse_url($uri, PHP_URL_PATH) ?: $uri;
        $adminSlug = '/' . (env('ADMIN_PATH', 'admin')) . '/analytics';
        if (strpos($path, $adminSlug) !== false) {
            return;
        }
        $detect = self::detectBot($userAgent);
        $type = $detect['bot'] ? 'bot' : 'visit';
        $retentionMinutes = $retentionMinutes ?? 20;
        $retentionMinutes = max(1, min(60, $retentionMinutes));

        if ($type === 'visit') {
            $key = ($user && is_object($user) && isset($user->id))
                ? 'u' . (string) $user->id
                : 'i' . $ip;
            if (self::wasRecentlyLogged($key, $retentionMinutes)) {
                return;
            }
        } else {
            $key = 'b' . $ip;
            if (self::wasRecentlyLogged($key, $retentionMinutes)) {
                return;
            }
        }

        $context = [
            'ip' => $ip,
            'uri' => $uri,
            'method' => $method,
            'user_agent' => $userAgent,
            'bot_name' => $detect['name'],
        ];
        if ($user && is_object($user)) {
            $context['user_id'] = $user->id ?? null;
            $context['username'] = $user->username ?? null;
        }
        self::log($type, $context);
        self::markRecentlyLogged($key, $retentionMinutes);
    }

    private static function getRecentVisitsPath(): ?string
    {
        $dir = self::getLogDir();
        return $dir === null ? null : $dir . DIRECTORY_SEPARATOR . self::RECENT_VISITS_FILE;
    }

    private static function readRecentVisits(): array
    {
        $path = self::getRecentVisitsPath();
        if ($path === null || !is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $lines = explode("\n", $raw);
        $json = '';
        foreach ($lines as $line) {
            if (strpos(trim($line), '<?php') === 0) {
                continue;
            }
            $json .= $line . "\n";
        }
        $data = json_decode(trim($json), true);
        return is_array($data) ? $data : [];
    }

    private static function writeRecentVisits(array $data): void
    {
        $path = self::getRecentVisitsPath();
        if ($path === null) {
            return;
        }
        $dir = self::getLogDir();
        if ($dir === null) {
            return;
        }
        if (!is_file($path)) {
            @file_put_contents($path, self::PHP_GUARD . "\n", LOCK_EX);
        }
        $content = self::PHP_GUARD . "\n" . json_encode($data, JSON_UNESCAPED_UNICODE);
        @file_put_contents($path, $content, LOCK_EX);
    }

    private static function wasRecentlyLogged(string $key, int $retentionMinutes): bool
    {
        $data = self::readRecentVisits();
        $ts = $data[$key] ?? null;
        if ($ts === null) {
            return false;
        }
        return (time() - (int) $ts) < ($retentionMinutes * 60);
    }

    private static function markRecentlyLogged(string $key, int $retentionMinutes): void
    {
        $data = self::readRecentVisits();
        $data[$key] = time();
        $cutoff = time() - ($retentionMinutes * 60);
        $data = array_filter($data, fn ($ts) => (int) $ts >= $cutoff);
        self::writeRecentVisits($data);
    }

    /**
     * Olay yaz. type: visit | bot | login | logout | security
     */
    public static function log(string $type, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }
        $entry = [
            'ts' => time(),
            'type' => $type,
            'ip' => $context['ip'] ?? SecurityService::clientIp(),
            'uri' => $context['uri'] ?? ($_SERVER['REQUEST_URI'] ?? ''),
            'method' => $context['method'] ?? ($_SERVER['REQUEST_METHOD'] ?? ''),
            'user_agent' => $context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ];
        foreach (['user_id', 'username', 'bot_name', 'event', 'message'] as $k) {
            if (array_key_exists($k, $context)) {
                $entry[$k] = $context[$k];
            }
        }
        if ($type === 'security' && isset($context['event'])) {
            $entry['security_event'] = $context['event'];
            foreach (['snippet', 'token_id', 'login', 'count', 'limit', 'block_minutes', 'violations'] as $k) {
                if (array_key_exists($k, $context)) {
                    $entry[$k] = $context[$k];
                }
            }
        }
        self::writeEntry($entry);
    }

    private static function writeEntry(array $entry): void
    {
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
     * Read entries from last N minutes (newest first). For live log.
     * @return array<int, array<string, mixed>>
     */
    public static function readLastMinutes(int $limit, int $retentionMinutes): array
    {
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return [];
        }
        $cutoff = time() - ($retentionMinutes * 60);
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
     * Saklama süresi (dakika) dışındaki kayıtları dosyadan siler. Canlı günlüğü yormamak için periyodik temizlik.
     */
    public static function purge(int $retentionMinutes): void
    {
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return;
        }
        clearstatcache(true);
        $cutoff = time() - ($retentionMinutes * 60);
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
                    $tmpPath = $dir . DIRECTORY_SEPARATOR . '.trafficlog_tmp_' . uniqid('', true);
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

        $recentPath = self::getRecentVisitsPath();
        if ($recentPath !== null && is_file($recentPath)) {
            $data = self::readRecentVisits();
            $data = array_filter($data, fn ($ts) => (int) $ts >= $cutoff);
            self::writeRecentVisits($data);
        }
    }

    /**
     * Tüm trafik log dosyalarını ve son ziyaret önbelleğini siler.
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
        $recentPath = self::getRecentVisitsPath();
        if ($recentPath !== null && is_file($recentPath)) {
            @unlink($recentPath);
        }
        self::$logDir = null;
    }

    /**
     * Belirli zamandan sonraki kayıtlar (canlı feed için). En yeni üstte.
     * @return array<int, array<string, mixed>>
     */
    public static function readSince(int $sinceTs, int $limit = 200): array
    {
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return [];
        }
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
                if ((int) $decoded['ts'] <= $sinceTs) {
                    continue;
                }
                $all[] = $decoded;
            }
        }
        usort($all, fn ($a, $b) => ($b['ts'] ?? 0) <=> ($a['ts'] ?? 0));
        return array_slice($all, 0, $limit);
    }

    /**
     * Tek bir kayıt için canlı günlük mesajı (Türkçe, samimi).
     *
     * @param array<string, string> $ipUserLabels sessions tablosundan IP → kullanıcı adı (virgülle çoklu)
     */
    public static function formatMessage(array $e, array $ipUserLabels = []): string
    {
        $type = $e['type'] ?? 'visit';
        $ip = $e['ip'] ?? '?';
        $ipTrim = is_string($ip) ? trim($ip) : '';
        $username = $e['username'] ?? null;
        $ts = date('H:i:s', (int) ($e['ts'] ?? 0));

        switch ($type) {
            case 'visit':
                if ($username) {
                    return $ts . ' — ' . lang('analytics.summary_visit_member', ['ip' => $ip, 'name' => $username]);
                }
                $line = $ts . ' — ' . lang('analytics.summary_visit_guest', ['ip' => $ip]);

                return self::appendIpSessionLabel($line, $ipTrim, $ipUserLabels);
            case 'bot':
                $name = $e['bot_name'] ?? 'bot';
                $line = $ts . ' — ' . lang('analytics.summary_bot', ['name' => $name]);

                return self::appendIpSessionLabel($line, $ipTrim, $ipUserLabels);
            case 'login':
                $u = $username ?? ('user_id:' . ($e['user_id'] ?? '?'));
                $line = $ts . ' — ' . lang('analytics.summary_login', ['user' => $u]);

                return self::appendIpSessionLabel($line, $ipTrim, $ipUserLabels, (string) ($username ?? '') !== '');
            case 'logout':
                $u = $username ?? ('user_id:' . ($e['user_id'] ?? '?'));
                $line = $ts . ' — ' . lang('analytics.summary_logout', ['user' => $u]);

                return self::appendIpSessionLabel($line, $ipTrim, $ipUserLabels, (string) ($username ?? '') !== '');
            case 'register':
                $u = $username ?? ('user_id:' . ($e['user_id'] ?? '?'));
                $line = $ts . ' — ' . lang('analytics.summary_register', ['user' => $u]);

                return self::appendIpSessionLabel($line, $ipTrim, $ipUserLabels, (string) ($username ?? '') !== '');
            case 'security':
                $event = $e['security_event'] ?? $e['event'] ?? 'attack';
                $label = self::securityEventLabel($event);
                $line = $ts . ' — ⚠️ ' . lang('analytics.summary_attack', ['label' => $label, 'ip' => $ip]);

                return self::appendIpSessionLabel($line, $ipTrim, $ipUserLabels);
            default:
                $line = $ts . ' — ' . $ip . ' | ' . $type;

                return self::appendIpSessionLabel($line, $ipTrim, $ipUserLabels);
        }
    }

    /**
     * @param array<string, string> $ipUserLabels
     */
    private static function appendIpSessionLabel(string $line, string $ip, array $ipUserLabels, bool $skip = false): string
    {
        if ($skip || $ip === '' || $ip === '?') {
            return $line;
        }
        if (!isset($ipUserLabels[$ip])) {
            return $line;
        }

        return $line . ' — ' . lang('analytics.ip_resolved_users', ['users' => $ipUserLabels[$ip]]);
    }

    private static function securityEventLabel(string $event): string
    {
        $labels = [
            'rate_limit_exceeded' => lang('analytics.security_rate_limit'),
            'block_applied' => lang('analytics.security_block'),
            'login_failed' => lang('analytics.security_login_failed'),
            'csrf_failed' => lang('analytics.security_csrf'),
            'xss_attempt' => lang('analytics.security_xss'),
        ];
        return $labels[$event] ?? $event;
    }

    /**
     * Real visitor count in last N minutes (unique IP, excluding bots).
     */
    public static function uniqueVisitorsCount(int $lastMinutes = 60): int
    {
        $cutoff = time() - ($lastMinutes * 60);
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return 0;
        }
        $ips = [];
        $files = self::getLogFilesInOrder($dir);
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            foreach (explode("\n", $content) as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '<?php') === 0) {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || empty($decoded['ts']) || (int) $decoded['ts'] < $cutoff) {
                    continue;
                }
                if (($decoded['type'] ?? '') === 'bot') {
                    continue;
                }
                $ip = $decoded['ip'] ?? '';
                if ($ip !== '') {
                    $ips[$ip] = true;
                }
            }
        }
        return count($ips);
    }

    /**
     * Son N dakikada giriş yapmamış ziyaretçi sayısı (benzersiz IP, type=visit ve user_id yok).
     */
    public static function uniqueGuestsCount(int $lastMinutes = 15): int
    {
        $cutoff = time() - ($lastMinutes * 60);
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return 0;
        }
        $ips = [];
        $files = self::getLogFilesInOrder($dir);
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            foreach (explode("\n", $content) as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '<?php') === 0) {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || empty($decoded['ts']) || (int) $decoded['ts'] < $cutoff) {
                    continue;
                }
                if (($decoded['type'] ?? '') !== 'visit') {
                    continue;
                }
                if (!empty($decoded['user_id'])) {
                    continue;
                }
                $ip = $decoded['ip'] ?? '';
                if ($ip !== '') {
                    $ips[$ip] = true;
                }
            }
        }
        return count($ips);
    }

    /**
     * Son N dakikada bot sayısı (benzersiz IP, type=bot).
     */
    public static function uniqueBotsCount(int $lastMinutes = 15): int
    {
        $cutoff = time() - ($lastMinutes * 60);
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return 0;
        }
        $ips = [];
        $files = self::getLogFilesInOrder($dir);
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            foreach (explode("\n", $content) as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '<?php') === 0) {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || empty($decoded['ts']) || (int) $decoded['ts'] < $cutoff) {
                    continue;
                }
                if (($decoded['type'] ?? '') !== 'bot') {
                    continue;
                }
                $ip = $decoded['ip'] ?? '';
                if ($ip !== '') {
                    $ips[$ip] = true;
                }
            }
        }
        return count($ips);
    }

    /**
     * Son N dakikadaki ziyaretçi listesi (giriş yapmamış, benzersiz IP). IP maskelenir.
     * @return array<int, array{display_name: string, ip_masked: string, last_seen: int}>
     */
    public static function getRecentGuestsList(int $lastMinutes = 15, int $limit = 200): array
    {
        $cutoff = time() - ($lastMinutes * 60);
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return [];
        }
        $byIp = [];
        $files = self::getLogFilesInOrder($dir);
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            foreach (explode("\n", $content) as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '<?php') === 0) {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || empty($decoded['ts']) || (int) $decoded['ts'] < $cutoff) {
                    continue;
                }
                if (($decoded['type'] ?? '') !== 'visit' || !empty($decoded['user_id'])) {
                    continue;
                }
                $ip = $decoded['ip'] ?? '';
                if ($ip !== '') {
                    $ts = (int) $decoded['ts'];
                    if (!isset($byIp[$ip]) || $byIp[$ip] < $ts) {
                        $byIp[$ip] = $ts;
                    }
                }
            }
        }
        $out = [];
        foreach ($byIp as $ip => $ts) {
            $out[] = [
                'display_name' => 'Ziyaretçi',
                'ip' => $ip,
                'ip_masked' => self::maskIp($ip),
                'last_seen' => $ts,
            ];
        }
        usort($out, fn ($a, $b) => ($b['last_seen'] ?? 0) <=> ($a['last_seen'] ?? 0));
        return array_slice($out, 0, $limit);
    }

    /**
     * Son N dakikadaki bot listesi (benzersiz bot_name veya IP).
     * @return array<int, array{bot_name: string, last_seen: int}>
     */
    public static function getRecentBotsList(int $lastMinutes = 15, int $limit = 100): array
    {
        $cutoff = time() - ($lastMinutes * 60);
        $dir = self::getLogDir();
        if ($dir === null || !is_dir($dir)) {
            return [];
        }
        $byKey = [];
        $files = self::getLogFilesInOrder($dir);
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            foreach (explode("\n", $content) as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '<?php') === 0) {
                    continue;
                }
                $decoded = json_decode($line, true);
                if (!is_array($decoded) || empty($decoded['ts']) || (int) $decoded['ts'] < $cutoff) {
                    continue;
                }
                if (($decoded['type'] ?? '') !== 'bot') {
                    continue;
                }
                $name = !empty($decoded['bot_name']) ? strtolower((string) $decoded['bot_name']) : ('ip:' . ($decoded['ip'] ?? ''));
                $ts = (int) $decoded['ts'];
                $ip = $decoded['ip'] ?? '';
                if (!isset($byKey[$name]) || $byKey[$name]['ts'] < $ts) {
                    $byKey[$name] = ['ts' => $ts, 'ip' => $ip];
                }
            }
        }
        $out = [];
        foreach ($byKey as $name => $data) {
            $ts = is_array($data) ? ($data['ts'] ?? 0) : (int) $data;
            $ip = is_array($data) ? ($data['ip'] ?? '') : '';
            $out[] = [
                'bot_name' => str_starts_with($name, 'ip:') ? 'Bot' : $name,
                'ip' => $ip,
                'ip_masked' => $ip !== '' ? self::maskIp($ip) : '',
                'last_seen' => $ts,
            ];
        }
        usort($out, fn ($a, $b) => ($b['last_seen'] ?? 0) <=> ($a['last_seen'] ?? 0));
        return array_slice($out, 0, $limit);
    }

    private static function maskIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return '***.***.***.***';
        }
        $parts = explode('.', $ip);
        if (count($parts) >= 2) {
            return $parts[0] . '.' . $parts[1] . '.***.***';
        }
        return '***.***.***.***';
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
