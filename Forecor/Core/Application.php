<?php

declare(strict_types=1);

namespace Forecor\Core;

/**
 * Application bootstrap: load env, config, DB, session, dispatch router.
 */
class Application
{
    protected string $basePath;
    /** @var Router|null */
    protected ?Router $router = null;
    /** @var array|null Route koleksiyonu (ileride kullanılabilir). */
    protected ?array $routeCollection = null;
    protected ?\App\Services\AuthService $authService = null;
    protected ?\App\Services\Cache $cache = null;
    protected ?\Symfony\Component\EventDispatcher\EventDispatcher $dispatcher = null;
    protected ?\App\Services\SecurityService $security = null;
    protected ?\App\Services\CensorshipService $censorshipService = null;
    protected ?\App\Services\CaptchaService $captcha = null;
    protected ?\App\Services\MailService $mailService = null;
    protected ?\App\Core\TemplateEngine $twigFrontend = null;
    protected ?\App\Core\TemplateEngine $twigAdmin = null;
    protected ?\App\Services\HookService $hookService = null;
    protected ?\App\Services\Translator $translator = null;

    /** @var \Symfony\Component\DependencyInjection\ContainerBuilder */
    protected $container;

    protected static $settingsCache = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->initContainer();
    }

    protected function initContainer(): void
    {
        if (!class_exists(\Symfony\Component\DependencyInjection\ContainerBuilder::class)) {
            return;
        }

        $this->container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

        // Register Application so autowiring finds it
        $this->container->register(\Forecor\Core\Application::class, \Forecor\Core\Application::class)
            ->setSynthetic(true)
            ->setPublic(true);

        // Auto-wire dependencies (App\Services, App\Controllers vs)
        // We'll trust ContainerBuilder to resolve classes on the fly
        // using autowiring via Reflection when requested.
    }

    /**
     * Get the DI Container
     */
    public function getContainer(): ?\Symfony\Component\DependencyInjection\ContainerBuilder
    {
        return $this->container;
    }
    /** Mevcut route koleksiyonu (yüklü değilse null). */
    public function getRouteCollection(): ?array
    {
        return $this->routeCollection;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /** Base path for URLs: .env APP_URL'deki path (tüm sistem tek kaynak). */
    public function getBaseUrlPath(): string
    {
        return app_url_base_path();
    }

    /** Forecor Router atanır (routes/web.php closure ile doldurulur). */
    public function useRouter(Router $router): void
    {
        $this->router = $router;
    }

    public function session(): \Symfony\Component\HttpFoundation\Session\Session
    {
        return \Forecor\Core\SessionManager::get();
    }

    public function auth(): \App\Services\AuthService
    {
        if ($this->authService === null) {
            $this->authService = new \App\Services\AuthService($this);
        }
        return $this->authService;
    }

    /** E-posta gönderimi (SMTP / mail). Ayarlar: Sistem Ayarları → Mail. */
    public function mail(): \App\Services\MailService
    {
        if ($this->mailService === null) {
            $this->mailService = new \App\Services\MailService($this);
        }
        return $this->mailService;
    }

    /** Merkezi güvenlik (cooldown, ihlal, geçici engel). */
    public function security(): \App\Services\SecurityService
    {
        if ($this->security === null) {
            $this->security = new \App\Services\SecurityService($this);
        }
        return $this->security;
    }

    /** Sansür koruma (yasak kelimeler, yasak kullanıcı adları, temp mail). */
    public function censorship(): \App\Services\CensorshipService
    {
        if ($this->censorshipService === null) {
            $this->censorshipService = new \App\Services\CensorshipService($this);
        }
        return $this->censorshipService;
    }

    /** Twig tema motoru (frontend veya admin). */
    public function twig(string $context = 'frontend'): \App\Core\TemplateEngine
    {
        if ($context === 'admin') {
            if ($this->twigAdmin === null) {
                $this->twigAdmin = new \App\Core\TemplateEngine($this->getBasePath(), 'admin');
            }
            return $this->twigAdmin;
        }
        if ($this->twigFrontend === null) {
            $this->twigFrontend = new \App\Core\TemplateEngine($this->getBasePath(), 'frontend');
        }
        return $this->twigFrontend;
    }

    /** Captcha (reCAPTCHA / Turnstile) doğrulama ve widget ayarları. */
    public function captcha(): \App\Services\CaptchaService
    {
        if ($this->captcha === null) {
            $this->captcha = new \App\Services\CaptchaService($this);
        }
        return $this->captcha;
    }

    /** Event Dispatcher (Hook/Plugin sistemi için). Forecor/Config/events.php ile dinleyiciler yüklenir. */
    public function event(): \Symfony\Component\EventDispatcher\EventDispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();

            $eventsConfig = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Forecor' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'events.php';
            if (is_file($eventsConfig)) {
                $listeners = require $eventsConfig;
                if (is_array($listeners)) {
                    self::registerListeners($this->dispatcher, $listeners);
                }
            }
            // Eklentiler: plugins/*/plugin.php ile Core'a dokunmadan listener ekler
            if (class_exists(\App\Services\PluginLoader::class)) {
                $pluginListeners = \App\Services\PluginLoader::loadListeners($this->getBasePath());
                self::registerListeners($this->dispatcher, $pluginListeners);
                \App\Services\PluginLoader::loadHooks($this->getBasePath(), $this->hooks());
            }

            \Forecor\Core\Event::setDispatcher($this->dispatcher);
        }
        return $this->dispatcher;
    }

    /**
     * Event adı => [ [ Class, 'method' ], ... ] dizisini dispatcher'a ekler.
     * @param array<string, list<array{0: string|object, 1: string}>> $listeners
     */
    private static function registerListeners(\Symfony\Component\EventDispatcher\EventDispatcher $dispatcher, array $listeners): void
    {
        foreach ($listeners as $eventName => $entries) {
            foreach ((array) $entries as $entry) {
                if (is_array($entry) && count($entry) >= 2) {
                    [$class, $method] = $entry;
                    if (is_string($class)) {
                        $class = new $class();
                    }
                    $dispatcher->addListener($eventName, [$class, $method]);
                }
            }
        }
    }

    /** Hook API (actions/filters). Eklentiler ve tema kancalara bağlanır. */
    public function hooks(): \App\Services\HookService
    {
        if ($this->hookService === null) {
            $this->hookService = new \App\Services\HookService();
        }
        return $this->hookService;
    }

    /** Çeviri servisi (Translator). Dil dosyalarını + DB override'larını yükler. */
    public function translator(): \App\Services\Translator
    {
        if ($this->translator === null) {
            $this->translator = new \App\Services\Translator($this->getBasePath(), $this);
            $locale = $this->resolveLocale();
            $this->translator->setLocale($locale);

            $fallback = (string) $this->getSetting('default_locale', 'tr');
            if ($fallback === '' || !preg_match('/^[a-z]{2,5}$/', $fallback)) {
                $fallback = 'tr';
            }
            $langFile = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $fallback . '.php';
            if (!is_file($langFile)) {
                $langFile = $this->getBasePath() . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $fallback . '.php';
            }
            if (!is_file($langFile)) {
                $fallback = 'tr';
            }
            $this->translator->setFallbackLocale($fallback);
        }
        return $this->translator;
    }

    /** Locale sırasıyla: Session → Cookie → Accept-Language → config. */
    protected function resolveLocale(): string
    {
        $session = null;
        try {
            $session = \Forecor\Core\SessionManager::get();
        } catch (\Throwable $e) {
            // Session henüz başlatılmamış olabilir
        }

        if ($session && $session->has('locale')) {
            return (string) $session->get('locale');
        }

        try {
            $user = $this->auth()->user();
            if ($user && !empty($user->locale) && in_array($user->locale, ['tr', 'en'], true)) {
                if ($session) {
                    $session->set('locale', $user->locale);
                }
                return $user->locale;
            }
        } catch (\Throwable $e) {
            // Auth/DB henüz hazır olmayabilir
        }

        if (isset($_COOKIE['locale']) && $_COOKIE['locale'] !== '') {
            return (string) $_COOKIE['locale'];
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLocale = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $langFile = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $browserLocale . '.php';
            if (!is_file($langFile)) {
                $langFile = $this->getBasePath() . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $browserLocale . '.php';
            }
            if (is_file($langFile)) {
                return $browserLocale;
            }
        }

        $dbLocale = (string) $this->getSetting('default_locale', '');
        if ($dbLocale !== '' && preg_match('/^[a-z]{2,5}$/', $dbLocale)) {
            $langFile = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $dbLocale . '.php';
            if (!is_file($langFile)) {
                $langFile = $this->getBasePath() . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $dbLocale . '.php';
            }
            if (is_file($langFile)) {
                return $dbLocale;
            }
        }
        return core_config('app.locale', 'tr');
    }

    /** Settings önbelleğini temizle (tek key veya tümü). default_locale değişince çağrılmalı. */
    public static function clearSettingCache(?string $key = null): void
    {
        if ($key === null) {
            self::$settingsCache = [];
        } else {
            unset(self::$settingsCache[$key]);
        }
    }

    /** Veritabanı settings tablosundan key ile değer oku (performans/cache için). Saldırı modu açıksa güvenlik ayarları preset ile override edilir. */
    public function getSetting(string $key, $default = null)
    {
        if (!array_key_exists($key, self::$settingsCache)) {
            try {
                if (class_exists(\App\Models\Setting::class)) {
                    self::$settingsCache[$key] = \App\Models\Setting::getValue($key, $default);
                } else {
                    self::$settingsCache[$key] = $default;
                }
            } catch (\Throwable $e) {
                self::$settingsCache[$key] = $default;
            }
        }
        $value = self::$settingsCache[$key] ?? $default;
        // Saldırı modu: güvenlik ayarlarını en sıkı preset ile override et (security_attack_mode kendisi override edilmez)
        if ($key !== 'security_attack_mode' && class_exists(\App\Config\SecurityAttackPresets::class)) {
            $attackMode = $this->getSettingRaw('security_attack_mode', '0');
            if ($attackMode === '1' && \App\Config\SecurityAttackPresets::has($key)) {
                return \App\Config\SecurityAttackPresets::get($key);
            }
        }
        return $value;
    }

    /** Ayarı preset override olmadan oku (saldırı modu kontrolü için dahili kullanım). */
    public function getSettingRaw(string $key, $default = null)
    {
        if (!array_key_exists($key, self::$settingsCache)) {
            try {
                if (class_exists(\App\Models\Setting::class)) {
                    self::$settingsCache[$key] = \App\Models\Setting::getValue($key, $default);
                } else {
                    self::$settingsCache[$key] = $default;
                }
            } catch (\Throwable $e) {
                self::$settingsCache[$key] = $default;
            }
        }
        return self::$settingsCache[$key] ?? $default;
    }

    /** Önbellek servisi (cache_driver: file|redis; Redis ayarları settings tablosunda). Aynı Redis kullanan her domain için cache_key_prefix ile anahtarlar ayrılır. */
    public function cache(): \App\Services\Cache
    {
        if ($this->cache === null) {
            $driver = $this->getSetting('cache_driver', 'file');
            $host = $this->getSetting('redis_host', '127.0.0.1');
            $port = (int) $this->getSetting('redis_port', '6379');
            $password = $this->getSetting('redis_password', '');
            $username = $this->getSetting('redis_username', '');
            $keyPrefix = $this->getCacheKeyPrefix();
            $this->cache = new \App\Services\Cache(
                $driver,
                $host ?: null,
                $port ?: 6379,
                $password !== '' ? $password : null,
                $username !== '' ? $username : null,
                $this->getBasePath() . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
                $keyPrefix
            );
        }
        return $this->cache;
    }

    /**
     * Redis cache anahtarlarını site bazlı ayırmak için prefix. Aynı sunucuda birden fazla kurulum aynı Redis kullanıyorsa karışmayı önler.
     * Önce settings.cache_key_prefix, yoksa APP_URL host'undan türetilir (örn. forum1.com -> megaforbb:cache:forum1_com:).
     */
    private function getCacheKeyPrefix(): string
    {
        $custom = trim((string) $this->getSetting('cache_key_prefix', ''));
        if ($custom !== '') {
            $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $custom);
            $safe = substr($safe, 0, 64);
            return 'megaforbb:cache:' . $safe . ':';
        }
        $url = function_exists('core_config') ? (string) core_config('app.url', '') : '';
        $host = $url !== '' ? parse_url($url, PHP_URL_HOST) : null;
        if (is_string($host) && $host !== '') {
            $safe = preg_replace('/[^a-zA-Z0-9.-]/', '_', $host);
            $safe = substr($safe, 0, 64);
            return 'megaforbb:cache:' . $safe . ':';
        }
        return 'megaforbb:cache:' . substr(md5($this->getBasePath()), 0, 16) . ':';
    }

    public function run(): void
    {
        \Forecor\Core\SessionManager::start();
        date_default_timezone_set(core_config('app.timezone', 'UTC'));

        $this->event();

        // Translator'ı erken yükle (helpers ve Twig kullanabilsin)
        $this->translator();

        if (class_exists(\App\Services\SecurityLogger::class)) {
            \App\Services\SecurityLogger::setEnabled($this->getSetting('security_tracking_enabled', '1') === '1');
        }
        if (class_exists(\App\Services\AnalyticsLogger::class)) {
            \App\Services\AnalyticsLogger::setEnabled($this->getSetting('analytics_visitor_log_enabled', '0') === '1');
        }

        \Forecor\Core\SecurityHeaders::sendSafeHeaders($this);

        $ip = \App\Services\SecurityService::clientIp();
        $requestPath = $this->normalizeRequestPathForSecurity();
        $user = $this->auth()->user();

        // Güvenlik kısıtlamaları: giriş yapmış kullanıcılar, security-check sayfası ve captcha geçen IP'ler muaf
        $skipSecurityRestrictions = $user !== null
            || $requestPath === '/security-check'
            || $this->security()->hasPassedSecurityCheck($ip);

        if (!$skipSecurityRestrictions) {
            // 1) Engelli IP mi?
            $block = $this->security()->isBlocked(null, $ip);
            if ($block !== null) {
                $this->sendRateLimitResponse((int) $block['wait_seconds']);
                return;
            }

            // 2) Global rate limit
            $rateCheck = $this->security()->checkGlobalRateLimit($ip);
            if (!$rateCheck['allowed']) {
                $this->sendRateLimitResponse((int)($rateCheck['retry_after'] ?? 300));
                return;
            }
        }

        // 3) APP_URL https ise ve istek HTTP ile geldiyse HTTPS'e yönlendir (nginx 301'den önce PHP'ye gelmesi için nginx'te HTTP→PHP iletin)
        $appUrl = (string) core_config('app.url', '');
        if (str_starts_with($appUrl, 'https://')) {
            $isHttps = \App\Services\SecurityService::isHttpsRequest();
            if (!$isHttps) {
                $host = $_SERVER['HTTP_HOST'] ?? '';
                $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
                header('Location: https://' . $host . $reqUri, true, 301);
                return;
            }
        }

        if ($this->isMaintenanceMode()) {
            $this->sendMaintenancePage();
            return;
        }

        if ($this->router === null) {
            http_response_code(500);
            echo 'Routes not loaded';
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $match = $this->router->match($method, $uri);

        if ($match === null) {
            $errorController = new \App\Controllers\ErrorController($this);
            echo $errorController->notFound();
            return;
        }

        // API route'ları için ayrı rate limit (bot / saniyede bin istek koruması)
        $handler = $match['handler'] ?? '';
        if (is_string($handler) && str_contains($handler, 'ApiController')) {
            $apiRateCheck = $this->security()->checkApiRateLimit($ip);
            if (!$apiRateCheck['allowed']) {
                $this->sendApiRateLimitResponse((int) ($apiRateCheck['retry_after'] ?? 60));
                return;
            }
        }

        $user = $this->auth()->user();
        if (class_exists(\App\Services\AnalyticsLogger::class)) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $retentionMin = (int) $this->getSetting('analytics_log_retention_minutes', '20');
            $retentionMin = in_array($retentionMin, [10, 20, 30, 60], true) ? $retentionMin : 20;
            \App\Services\AnalyticsLogger::logRequest($ip, $uri, $method, $userAgent, $user, $retentionMin);
        }

        // Giriş yapmış kullanıcı: güvenlik loglarında IP→kullanıcı eşlemesi için son IP (her istekte)
        if ($user && isset($user->id)) {
            \App\Models\User::where('id', (int) $user->id)->update(['last_ip' => $ip]);
            $threshold = date('Y-m-d H:i:s', strtotime('-2 minutes'));
            \App\Models\User::where('id', (int) $user->id)
                ->where(function ($q) use ($threshold) {
                    $q->whereNull('last_activity_at')->orWhere('last_activity_at', '<', $threshold);
                })
                ->update(['last_activity_at' => date('Y-m-d H:i:s')]);
        }

        $handler = $match['handler'];
        $params = array_values($match['params'] ?? []);
        $isAdminRequest = false;

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$class, $method] = explode('@', $handler, 2);
            // Eklenti controller'ları tam namespace ile gelir (Plugins\...); çekirdek controller'lar App\Controllers\ ile ön eklenir
            if (strpos($class, '\\') === false) {
                $class = "App\\Controllers\\{$class}";
            }
            $isAdminRequest = str_contains($class, '\\Admin') || (stripos($class, 'Admin') !== false && str_contains($class, 'Controller'));
            $controllerLoaded = false;
            try {
                $controllerLoaded = class_exists($class);
            } catch (\Throwable $e) {
                $this->displayControllerLoadError($class, $method, $e);
                return;
            }
            if (!$controllerLoaded) {
                http_response_code(500);
                echo 'Controller not found: ' . htmlspecialchars($class);
                return;
            }

            // Resolve controller via DI Container if available
            try {
                if ($this->container) {
                    // Determine parameter classes recursively
                    try {
                        $ref = new \ReflectionClass($class);
                        $constructor = $ref->getConstructor();
                        if ($constructor) {
                            foreach ($constructor->getParameters() as $param) {
                                $type = $param->getType();
                                if ($type && !$type->isBuiltin() && class_exists($type->getName())) {
                                    $depClass = $type->getName();
                                    if (!$this->container->has($depClass) && $depClass !== \Forecor\Core\Application::class) {
                                        $this->container->autowire($depClass, $depClass)->setPublic(true);
                                    }
                                }
                            }
                        }
                    } catch (\ReflectionException $e) {
                        // Ignore
                    }

                    if (!$this->container->has($class)) {
                        $this->container->autowire($class, $class)->setPublic(true);
                        $this->container->compile();
                    }

                    // Set the synthetic Application service
                    $this->container->set(\Forecor\Core\Application::class, $this);
                    // Also satisfy the typed class for legacy code
                    $this->container->set('Forecor\Core\Application', $this);

                    $controller = $this->container->get($class);
                } else {
                    $controller = new $class($this);
                }

                if (!method_exists($controller, $method)) {
                    http_response_code(500);
                    echo 'Action not found: ' . htmlspecialchars($class . '@' . $method);
                    return;
                }
                // Route'dan gelen parametreler string; controller type-hint'e göre cast et (strict_types uyumu)
                try {
                    $refMethod = new \ReflectionMethod($controller, $method);
                    $refParams = $refMethod->getParameters();
                    foreach ($params as $i => $val) {
                        if (!isset($refParams[$i])) {
                            break;
                        }
                        $pType = $refParams[$i]->getType();
                        if ($pType instanceof \ReflectionNamedType && $pType->isBuiltin()) {
                            settype($params[$i], $pType->getName());
                        }
                    }
                } catch (\ReflectionException $e) {
                    // cast yapılamazsa olduğu gibi geç
                }
                $result = $controller->$method(...$params);
            } catch (\Throwable $e) {
                $this->displayControllerLoadError($class, $method, $e);
                return;
            }
        } elseif (is_callable($handler)) {
            $result = $handler(...$params);
        } else {
            http_response_code(500);
            echo 'Invalid handler';
            return;
        }

        if (is_string($result)) {
            if (!$isAdminRequest && preg_match('/^\s*<(?:!DOCTYPE|html\b)/i', $result) === 1) {
                $minifyHtml = $this->getSetting('minify_html', '0') === '1';
                $minifyCss = $this->getSetting('minify_css', '0') === '1';
                $minifyJs = $this->getSetting('minify_js', '0') === '1';
                if ($minifyHtml || $minifyCss || $minifyJs) {
                    $result = \App\Services\HtmlMinifier::minify($result, [
                        'minify_html' => $minifyHtml,
                        'minify_css' => $minifyCss,
                        'minify_js' => $minifyJs,
                    ]);
                }
                $gzipEnabled = $this->getSetting('gzip_enabled', '0') === '1';
                if ($gzipEnabled && extension_loaded('zlib') && !headers_sent()) {
                    $acceptEncoding = strtolower((string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''));
                    if (strpos($acceptEncoding, 'gzip') !== false) {
                        $encoded = gzencode($result, 6, FORCE_GZIP);
                        if ($encoded !== false) {
                            header('Content-Encoding: gzip');
                            header('Vary: Accept-Encoding');
                            $result = $encoded;
                        }
                    }
                }
            }
            echo $result;
        } elseif ($result instanceof \Closure) {
            $result();
        }
    }

    /** Bakım modu açıksa ve kullanıcı staff değilse true (bakım sayfası gösterilecek). */
    protected function isMaintenanceMode(): bool
    {
        try {
            if (!class_exists(\Illuminate\Database\Capsule\Manager::class)) {
                return false;
            }
            $row = \Illuminate\Database\Capsule\Manager::table('settings')->where('key', 'maintenance_mode')->first(['value']);
            if ($row === null || $row->value !== '1') {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }
        $user = $this->auth()->user();
        if (!$user) {
            return true;
        }
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        return !$user->role || !$user->role->is_staff;
    }

    protected function sendMaintenancePage(): void
    {
        http_response_code(503);
        header('Retry-After: 3600');
        header('Content-Type: text/html; charset=utf-8');
        $appName = core_config('app.name', 'MegaforBB');
        $loginUrl = rtrim(core_config('app.url', ''), '/') . '/login';
        try {
            echo $this->twig('frontend')->render('maintenance.html.twig', [
                'appName' => $appName,
                'loginUrl' => $loginUrl,
            ]);
        } catch (\Throwable $e) {
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Bakımda</title></head><body><h1>Site bakımda</h1><p>Kısa süre sonra tekrar açılacaktır.</p></body></html>';
        }
    }

    /** İstek path'ini güvenlik kontrolü için normalize eder (base path çıkarılmış). */
    private function normalizeRequestPathForSecurity(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = '/' . trim((string) $path, '/');
        if ($path === '') {
            $path = '/';
        }
        $base = $this->getBaseUrlPath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        return $path;
    }

    /** 429 rate limit / engel yanıtı; security-check sayfasına link içerir. */
    private function sendRateLimitResponse(int $retryAfter): void
    {
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('Content-Type: text/html; charset=utf-8');
        $securityCheckUrl = rtrim((string) core_config('app.url', ''), '/') . '/security-check';
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Çok fazla istek</title></head><body><h1>Çok fazla istek</h1><p>Lütfen bir süre sonra tekrar deneyin.</p><p><a href="' . htmlspecialchars($securityCheckUrl) . '">Güvenlik doğrulaması ile devam et</a></p></body></html>';
    }

    /** API rate limit aşımında JSON 429 döner. */
    private function sendApiRateLimitResponse(int $retryAfter): void
    {
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Too many requests. Retry after ' . $retryAfter . ' seconds.'], JSON_UNESCAPED_UNICODE);
    }

    /** Controller yüklenirken oluşan hatayı güvenli şekilde gösterir (makale/forum hata ayıklama). */
    private function displayControllerLoadError(string $class, string $method, \Throwable $e): void
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        $msg = htmlspecialchars($e->getMessage());
        $file = htmlspecialchars($e->getFile());
        $line = $e->getLine();
        $prev = $e->getPrevious();
        $prevInfo = $prev ? '<p><strong>Önceki hata:</strong> ' . htmlspecialchars($prev->getMessage()) . ' in ' . htmlspecialchars($prev->getFile()) . ':' . $prev->getLine() . '</p>' : '';
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Controller yükleme hatası</title></head><body>';
        echo '<h1>Controller yükleme hatası</h1>';
        echo '<p><strong>Controller:</strong> ' . htmlspecialchars($class) . '@' . htmlspecialchars($method) . '</p>';
        echo '<p><strong>Hata:</strong> ' . $msg . '</p>';
        echo '<p><strong>Dosya:</strong> ' . $file . ' (satır ' . $line . ')</p>';
        echo $prevInfo;
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    }
}
