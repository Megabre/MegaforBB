<?php

/**
 * MegaforBB Front Controller (Premium MVC)
 * Tüm istekler bu dosyaya yönlendirilmeli (Apache: mod_rewrite veya Alias; Nginx: try_files).
 * Dizin yapısı: Forecor, App, Content, Route, Inc, Library.
 * Hata yönetimi: Her zaman Content/storage/logs/*.log dosyasına yazılır; APP_DEBUG=true ise ekranda da gösterilir.
 */

declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED);

$basePath = dirname(__DIR__);
if (!defined('MEGAFORBB_BASE_PATH')) {
    define('MEGAFORBB_BASE_PATH', $basePath);
}

/** env() önce Forecor ile tanımlanır; Symfony Dotenv $_ENV doldurur */
require $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'env.php';

require $basePath . DIRECTORY_SEPARATOR . 'Library' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/** Symfony: .env yükleme ($_ENV doldurulur) */
$forecorSymmod = $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'symmod' . DIRECTORY_SEPARATOR . 'symfony.php';
if (is_file($forecorSymmod)) {
    require $forecorSymmod;
}

/** Laravel Illuminate: veritabanı (Eloquent/Capsule) */
$forecorLaramod = $basePath . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'laramod' . DIRECTORY_SEPARATOR . 'laravel.php';
if (is_file($forecorLaramod)) {
    require $forecorLaramod;
}

date_default_timezone_set('UTC');

$isDebug = (bool) env('APP_DEBUG', true);
if (class_exists(\App\Models\Setting::class)) {
    try {
        $dbDebug = \App\Models\Setting::getValue('app_debug', null);
        if ($dbDebug !== null && $dbDebug !== '') {
            $isDebug = $dbDebug === '1' || $dbDebug === 'true' || $dbDebug === true;
        }
    } catch (\Throwable $e) {
    }
}

/** Merkezi hata loglama – mutlaka yükle ve base path ver (log dosyası oluşması için) */
$errorLoggerFile = $basePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'ErrorLogger.php';
if (is_file($errorLoggerFile)) {
    require_once $errorLoggerFile;
}
if (class_exists(\App\Services\ErrorLogger::class)) {
    \App\Services\ErrorLogger::setBasePath($basePath);
}

/** PHP Warning/Notice vb. → dosyaya log; debug açıksa PHP’nin göstermesine izin ver, kapalıysa ekrandan gizle */
set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0) use ($isDebug): bool {
    if (class_exists(\App\Services\ErrorLogger::class)) {
        \App\Services\ErrorLogger::logError($severity, $message, $file, $line);
    }
    return !$isDebug;
});

/** Fatal error (E_ERROR, parse error) → shutdown’da yakala ve logla */
register_shutdown_function(function () use ($basePath): void {
    $err = error_get_last();
    if ($err === null || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    if (class_exists(\App\Services\ErrorLogger::class)) {
        \App\Services\ErrorLogger::logError($err['type'], $err['message'], $err['file'] ?? '', $err['line'] ?? 0);
    }
});

$whoops = new \Whoops\Run;

/** Whoops'ta son push edilen handler ÖNCE çalışır. Log'u en son ekleyerek önce çalışmasını sağlıyoruz. */

if ($isDebug) {
    $prettyPageHandler = new \Whoops\Handler\PrettyPageHandler();
    $prettyPageHandler->setPageTitle('Sistem Hatası – MegaforBB');
    $whoops->pushHandler($prettyPageHandler);
} else {
    $whoops->pushHandler(new \Whoops\Handler\CallbackHandler(function ($exception, $inspector, $run) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Hatası - MegaforBB</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .box { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-width: 450px; text-align: center; border-top: 5px solid #ef4444; }
        h1 { font-size: 24px; font-weight: 700; margin-top: 0; color: #ef4444; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 25px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; font-weight: 600; color: #fff; background: #3b82f6; text-decoration: none; border-radius: 6px; transition: background 0.3s; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Geçici Bir Hata Meydana Geldi</h1>
        <p>Sistemde beklenmeyen bir durum saptandı. Hata log dosyasına kaydedildi. Lütfen sayfayı yenilemeyi deneyin veya ana sayfaya geri dönün.</p>
        <a href="/" class="btn">Ana Sayfaya Dön</a>
    </div>
</body>
</html>';
        return \Whoops\Handler\Handler::QUIT;
    }));
}

/** Log callback en son eklendiği için Whoops'ta ÖNCE çalışır; böylece hata mutlaka dosyaya yazılır. */
$whoops->pushHandler(new \Whoops\Handler\CallbackHandler(function ($exception, $inspector, $run) use ($basePath) {
    if (!$exception instanceof \Throwable) {
        return;
    }
    if (class_exists(\App\Services\ErrorLogger::class)) {
        \App\Services\ErrorLogger::setBasePath($basePath);
        \App\Services\ErrorLogger::logException($exception);
    }
    if (function_exists('env') && (env('WEBHOOK_TELEGRAM_BOT_TOKEN', '') !== '' || env('WEBHOOK_TELEGRAM_URL', '') !== '')) {
        try {
            \App\Services\WebhookService::notifyCriticalError(
                $exception->getMessage(),
                ['file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
        } catch (\Throwable $ignored) {
        }
    }
}));

$whoops->register();

// ── Botble Lisans Kontrolü ──────────────────────────────────────────────────
// Enforce sadece LM_ENFORCE=true iken aktif olur; geliştirme ortamında kapalı tutun.
(function () use ($basePath): void {
    $licenseFile = $basePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'LicenseService.php';
    if (! is_file($licenseFile)) {
        return;
    }
    require_once $licenseFile;

    $enforce = filter_var(env('LM_ENFORCE', 'false'), FILTER_VALIDATE_BOOLEAN);
    if (! $enforce) {
        // Enforce kapalıysa: arka planda doğrula, durumu DB'ye yaz; forum kapanmaz.
        try {
            \App\Services\LicenseService::bootCheck();
        } catch (\Throwable $ignored) {
        }
        return;
    }

    // Enforce açık: lisans geçersizse sadece admin lisans yönetim sayfasına izin ver.
    try {
        $valid = \App\Services\LicenseService::bootCheck();
    } catch (\Throwable) {
        $valid = false;
    }

    if ($valid) {
        return;
    }

    $adminPath = env('ADMIN_PATH', 'admin');
    $uri       = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? '/';
    $uri       = '/' . ltrim($uri, '/');

    // Lisans yönetim sayfasına her zaman geçiş serbest
    if (str_starts_with($uri, '/' . $adminPath . '/license')) {
        return;
    }

    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lisans Geçersiz — MegaforBB</title>
    <style>
        body{font-family:system-ui,sans-serif;background:#0f172a;color:#f1f5f9;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
        .box{background:#1e293b;padding:40px 48px;border-radius:16px;text-align:center;max-width:440px;border-top:4px solid #f59e0b}
        h1{font-size:22px;margin:0 0 12px;color:#f59e0b}
        p{color:#94a3b8;line-height:1.7;margin:0 0 24px}
        a{display:inline-block;padding:10px 22px;background:#f59e0b;color:#0f172a;border-radius:8px;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
<div class="box">
    <h1>⚠ Lisans Doğrulanamadı</h1>
    <p>Bu kurulum için geçerli bir Botble lisansı bulunamadı.<br>Lütfen yönetici panelinden lisansı aktifleştirin.</p>
    <a href="/' . htmlspecialchars($adminPath) . '/license">Lisansı Aktifleştir</a>
</div>
</body>
</html>';
    exit;
})();
// ───────────────────────────────────────────────────────────────────────────

$app = new \Forecor\Core\Application($basePath);
if (function_exists('app')) {
    app($app);
}

date_default_timezone_set(core_config('app.timezone', 'UTC'));

if (class_exists(\App\Services\RtbhIpListService::class)) {
    try {
        \App\Services\RtbhIpListService::maybeExitRedirectForAttackMode($app);
    } catch (\Throwable $e) {
        // yönlendirme hatasında siteyi kilitleme
    }
}

$router = new \Forecor\Core\Router($app->getBaseUrlPath());
$routesCacheFile = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . \Forecor\Core\Router::ROUTES_CACHE_FILENAME;
$routeDir = $basePath . DIRECTORY_SEPARATOR . 'Route';

if (!$router->loadFromCompiledFile($routesCacheFile)) {
    $routeFiles = [
        $routeDir . DIRECTORY_SEPARATOR . 'web.php',
        $routeDir . DIRECTORY_SEPARATOR . 'admin.php',
        $routeDir . DIRECTORY_SEPARATOR . 'api.php',
    ];
    foreach ($routeFiles as $rf) {
        if (is_file($rf)) {
            $loader = require $rf;
            $loader($router);
        }
    }
    $router->dumpToCompiledFile($routesCacheFile);
}
if (class_exists(\App\Services\PluginLoader::class)) {
    \App\Services\PluginLoader::loadPluginRoutes($basePath, $router);
}
$app->useRouter($router);

// ── Uzak Takip (Fallback) ───────────────────────────────────────────────────
// Cron çalışmayan kurulumlar için yedek mekanizma.
// Günde 1 kez, sayfa kapanırken arka planda ping atar. Siteyi yavaşlatmaz.
register_shutdown_function(function () use ($basePath): void {
    try {
        ignore_user_abort(true);

        $storageDir = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage';
        $pingFile   = $storageDir . DIRECTORY_SEPARATOR . 'tracker_last_ping.txt';

        // Günde 1 kez çalışsın (86400 saniye = 24 saat)
        if (is_file($pingFile) && (time() - (int) @file_get_contents($pingFile)) < 86400) {
            return;
        }

        // Önce yanıtı kullanıcıya gönder, sonra arka planda çalışmaya devam et
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        if (!function_exists('curl_init')) {
            return;
        }

        $ch = curl_init('https://track.megaforbb.com.tr/api.php');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'domain'      => $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'unknown'),
                'server_ip'   => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                'path'        => $basePath,
                'php_version' => phpversion(),
            ]),
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT      => 'MegaforBB-Tracker/1.0',
        ]);

        $res  = @curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);

        // Sadece başarılıysa 24 saatlik kilidi yaz
        if ($res !== false && $code === 200) {
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0755, true);
            }
            @file_put_contents($pingFile, (string) time());
        }
    } catch (\Throwable $e) {
        // Sessizce geç – siteyi asla bozma
    }
});
// ─────────────────────────────────────────────────────────────────────────────

$app->run();
