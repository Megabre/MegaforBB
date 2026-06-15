<?php

declare(strict_types=1);

namespace App\Services;

use Forecor\Core\Application;

/**
 * list.rtbh.com.tr — output.txt IP listesini yerelde tutar; kayıt/girişte kontrol.
 * @see https://list.rtbh.com.tr/
 */
class RtbhIpListService
{
    public const SOURCE_URL = 'https://list.rtbh.com.tr/output.txt';

    public const ACTION_LOG_ONLY = 'log_only';

    public const ACTION_PENDING_APPROVAL = 'pending_approval';

    public const ACTION_BLOCK_REGISTER = 'block_register';

    public const ACTION_BLOCK_REGISTER_AND_LOGIN = 'block_register_and_login';

    /** Tam site (yönetici paneli hariç) HTTP yönlendirmesi — URL ayrı ayarda */
    public const ACTION_REDIRECT_URL = 'redirect_url';

    private const STORAGE_REL = 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'rtbh_ips.json';

    /** @var array<string, true>|null */
    private static ?array $memoryCache = null;

    public function __construct(
        private Application $app
    ) {
    }

    public static function storagePath(string $basePath): string
    {
        return $basePath . DIRECTORY_SEPARATOR . self::STORAGE_REL;
    }

    public function isEnabled(): bool
    {
        return $this->app->getSetting('rtbh_enabled', '0') === '1';
    }

    public function getAction(): string
    {
        $a = (string) $this->app->getSetting('rtbh_action', self::ACTION_LOG_ONLY);
        $allowed = [
            self::ACTION_LOG_ONLY,
            self::ACTION_PENDING_APPROVAL,
            self::ACTION_BLOCK_REGISTER,
            self::ACTION_BLOCK_REGISTER_AND_LOGIN,
            self::ACTION_REDIRECT_URL,
        ];

        return in_array($a, $allowed, true) ? $a : self::ACTION_LOG_ONLY;
    }

    public function isIpListed(string $ip): bool
    {
        $ip = trim($ip);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }
        $set = self::loadIpSet($this->app->getBasePath());

        return isset($set[$ip]);
    }

    /**
     * Davranış "Şuraya yönlendir" seçiliyken: RTBH açık, yerel liste ve geçerli URL varsa,
     * listedeki IPv4 için tüm site isteğini (yönetici paneli hariç) verilen adrese yönlendirir.
     * public/index.php içinde, yönlendirme yığınından önce çağrılır.
     */
    public static function maybeExitRedirectForAttackMode(Application $app): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if ($app->getSettingRaw('rtbh_enabled', '0') !== '1') {
            return;
        }
        if ((string) $app->getSettingRaw('rtbh_action', '') !== self::ACTION_REDIRECT_URL) {
            return;
        }
        $url = trim((string) $app->getSettingRaw('rtbh_attack_redirect_url', ''));
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return;
        }
        if (!is_file(self::storagePath($app->getBasePath()))) {
            return;
        }
        $adminPath = function_exists('env') ? (string) env('ADMIN_PATH', 'admin') : 'admin';
        $adminPath = trim($adminPath, '/');
        if ($adminPath === '') {
            $adminPath = 'admin';
        }
        $reqPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $reqPath = is_string($reqPath) ? $reqPath : '/';
        $reqPath = '/' . ltrim($reqPath, '/');
        if ($reqPath === '/' . $adminPath || str_starts_with($reqPath, '/' . $adminPath . '/')) {
            return;
        }
        $svc = new self($app);
        if (!$svc->isIpListed(SecurityService::clientIp())) {
            return;
        }
        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
        }
        exit;
    }

    /**
     * @return array<string, true>
     */
    public static function loadIpSet(string $basePath): array
    {
        if (self::$memoryCache !== null) {
            return self::$memoryCache;
        }
        $path = self::storagePath($basePath);
        if (!is_file($path) || !is_readable($path)) {
            self::$memoryCache = [];

            return self::$memoryCache;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            self::$memoryCache = [];

            return self::$memoryCache;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            self::$memoryCache = [];

            return self::$memoryCache;
        }
        /** @var array<string, true> $out */
        $out = [];
        foreach ($data as $k => $v) {
            if (is_string($k) && $k !== '' && ($v === true || $v === 1 || $v === '1')) {
                $out[$k] = true;
            }
        }
        self::$memoryCache = $out;

        return self::$memoryCache;
    }

    public static function clearMemoryCache(): void
    {
        self::$memoryCache = null;
    }

    /**
     * output.txt indirir, JSON olarak kaydeder.
     *
     * @return array{success: bool, count?: int, error?: string}
     */
    public function refreshFromRemote(): array
    {
        $basePath = $this->app->getBasePath();
        $dir = dirname(self::storagePath($basePath));
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            return ['success' => false, 'error' => 'storage_not_writable'];
        }

        $body = $this->httpGet(self::SOURCE_URL);
        if ($body === null || $body === '') {
            return ['success' => false, 'error' => 'download_failed'];
        }

        /** @var array<string, true> $map */
        $map = [];
        $lines = preg_split("/\r\n|\n|\r/", $body) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (filter_var($line, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $map[$line] = true;
            }
        }

        $tmp = self::storagePath($basePath) . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $json = json_encode($map, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['success' => false, 'error' => 'encode_failed'];
        }
        if (@file_put_contents($tmp, $json) === false) {
            @unlink($tmp);

            return ['success' => false, 'error' => 'write_failed'];
        }
        $final = self::storagePath($basePath);
        if (!@rename($tmp, $final)) {
            @unlink($tmp);

            return ['success' => false, 'error' => 'rename_failed'];
        }

        self::clearMemoryCache();
        $count = count($map);
        \App\Models\Setting::setValue('rtbh_list_updated_at', (string) time(), 'security');
        \App\Models\Setting::setValue('rtbh_list_count', (string) $count, 'security');
        \App\Models\Setting::setValue('rtbh_list_last_error', '', 'security');
        Application::clearSettingCache('rtbh_list_updated_at');
        Application::clearSettingCache('rtbh_list_count');
        Application::clearSettingCache('rtbh_list_last_error');

        return ['success' => true, 'count' => $count];
    }

    /**
     * Hata durumunda ayarlara yazar (cron / manuel).
     */
    public function recordRefreshError(string $code): void
    {
        \App\Models\Setting::setValue('rtbh_list_last_error', $code, 'security');
        Application::clearSettingCache('rtbh_list_last_error');
    }

    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_USERAGENT => 'MegaforBB/RTBH',
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code !== 200) {
                return null;
            }

            return (string) $body;
        }
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 120,
                'user_agent' => 'MegaforBB/RTBH',
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);

        return $body !== false ? (string) $body : null;
    }
}
