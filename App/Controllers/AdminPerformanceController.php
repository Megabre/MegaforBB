<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Admin: Performance settings — Redis/cache, minify, CDN, lazy load, image optimization.
 */
class AdminPerformanceController extends AdminController
{
    private const CSRF_TOKEN = 'admin_performance';
    private const TABS = ['cache', 'varnish', 'minify', 'assets'];


    public function index(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        header('Location: ' . core_url($adminPath . '/performance/cache'), true, 302);
        exit;
    }


    public function showSection(string $section): string
    {
        $section = strtolower(trim($section));
        if (!in_array($section, self::TABS, true)) {
            $adminPath = env('ADMIN_PATH', 'admin');
            header('Location: ' . core_url($adminPath . '/performance/cache'), true, 302);
            exit;
        }
        return $this->renderSection($section);
    }

    private function renderSection(string $activeTab): string
    {
        $settings = [
            'cache_driver' => $this->getSetting('cache_driver', 'file'),
            'cache_key_prefix' => $this->getSetting('cache_key_prefix', ''),
            'redis_host' => $this->getSetting('redis_host', '127.0.0.1'),
            'redis_port' => $this->getSetting('redis_port', '6379'),
            'redis_password' => $this->getSetting('redis_password', ''),
            'redis_username' => $this->getSetting('redis_username', ''),
            'minify_html' => $this->getSetting('minify_html', '0') === '1',
            'minify_css' => $this->getSetting('minify_css', '0') === '1',
            'minify_js' => $this->getSetting('minify_js', '0') === '1',
            'cdn_url' => $this->getSetting('cdn_url', ''),
            'gzip_enabled' => $this->getSetting('gzip_enabled', '0') === '1',
            'consolidate_assets' => $this->getSetting('consolidate_assets', '0') === '1',
            'minify_consolidated' => $this->getSetting('minify_consolidated', '0') === '1',
            'jquery_cdn_url' => $this->getSetting('jquery_cdn_url', ''),
            'jquery_enabled' => $this->getSetting('jquery_enabled', '1') === '1',
            'ajax_enabled' => $this->getSetting('ajax_enabled', '1') === '1',
            'lazy_load_images' => $this->getSetting('lazy_load_images', '1') === '1',
            'image_optimize' => $this->getSetting('image_optimize', '0') === '1',
            'varnish_enabled' => $this->getSetting('varnish_enabled', '0') === '1',
            'varnish_servers' => $this->getSetting('varnish_servers', '127.0.0.1:6081'),
            'varnish_secret' => $this->getSetting('varnish_secret', ''),
        ];
        $cacheDriver = $this->app->cache()->getDriver();
        $opcacheStatus = $this->getOpcacheStatus();
        $cacheSummary = $this->getCacheSummary();
        return $this->view('performance/index', [
            'pageTitle' => lang('admin.performance.page_title'),
            'settings' => $settings,
            'activeTab' => $activeTab,
            'currentCacheDriver' => $cacheDriver,
            'opcacheStatus' => $opcacheStatus,
            'cacheSummary' => $cacheSummary,
            'cleared' => (isset($_GET['cleared']) && $_GET['cleared'] === '1'),
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirectToPerformance('cache');
            return;
        }
        $tab = (string) ($_POST['tab'] ?? 'cache');
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'cache';
        }
        if ($tab === 'cache') {
            $this->updateCache();
        } elseif ($tab === 'varnish') {
            $this->updateVarnish();
        } elseif ($tab === 'minify') {
            $this->updateMinify();
        } else {
            $this->updateAssets();
        }
        $this->redirectToPerformance($tab);
    }

    /** POST: Redis bağlantı testi. JSON döner. */
    public function redisTest(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => lang('api.invalid_request')], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!core_csrf_valid(self::CSRF_TOKEN, (string)($_POST['_token'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $host = trim((string) ($_POST['redis_host'] ?? '127.0.0.1'));
        $port = (int) ($_POST['redis_port'] ?? 6379);
        $password = trim((string) ($_POST['redis_password'] ?? ''));
        $username = trim((string) ($_POST['redis_username'] ?? ''));
        $result = \App\Services\Cache::testRedisConnection($host, $port, $password !== '' ? $password : null, $username !== '' ? $username : null);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /** POST: Sistem önbelleğini temizle (kapsamlı). AJAX ise JSON detay döner, değilse yönlendirir. */
    public function clearCache(): void
    {
        if (!core_csrf_valid('admin_clear_cache', (string) ($_POST['_token'] ?? ''))) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => lang('admin.performance.csrf_failed'), 'details' => []], JSON_UNESCAPED_UNICODE);
                return;
            }
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/performance'));
            return;
        }

        $details = [];

        // 1. App cache (driver-based: file or redis)
        $driver = $this->app->cache()->getDriver();
        $this->app->cache()->clear();
        $details[] = [
            'label' => lang('admin.performance.clear_detail_app_label'),
            'description' => lang('admin.performance.clear_detail_app_desc', ['driver' => $driver]),
            'status' => 'ok',
        ];

        // 2. File-based storage cache (route cache routes_compiled.php included)
        $cacheDir = MEGAFORBB_BASE_PATH . '/Content/storage/cache';
        $cacheCount = 0;
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                        $cacheCount++;
                    }
                }
            }
        }
        $details[] = [
            'label' => lang('admin.performance.clear_detail_file_label'),
            'description' => lang('admin.performance.clear_detail_file_desc', ['count' => $cacheCount]),
            'path' => 'Content/storage/cache',
            'count' => $cacheCount,
            'status' => 'ok',
        ];

        // 3. View cache (compiled templates) – Twig alt dizin kullanabilir, recursive sil
        $viewCacheDir = MEGAFORBB_BASE_PATH . '/Content/storage/views';
        $viewCount = 0;
        if (is_dir($viewCacheDir)) {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($viewCacheDir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iter as $item) {
                if ($item->isFile()) {
                    @unlink($item->getPathname());
                    $viewCount++;
                }
            }
        }
        $details[] = [
            'label' => lang('admin.performance.clear_detail_views_label'),
            'description' => lang('admin.performance.clear_detail_views_desc', ['count' => $viewCount]),
            'path' => 'Content/storage/views',
            'count' => $viewCount,
            'status' => 'ok',
        ];

        // 4. OPcache (PHP bytecode cache)
        $opcacheReset = false;
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $opcacheReset = true;
        }
        $details[] = [
            'label' => lang('admin.performance.clear_detail_opcache_label'),
            'description' => $opcacheReset ? lang('admin.performance.clear_detail_opcache_ok') : lang('admin.performance.clear_detail_opcache_skip'),
            'status' => $opcacheReset ? 'ok' : 'skip',
        ];

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => lang('admin.performance.cache_cleared'),
                'details' => $details,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/performance/cache?cleared=1'));
    }

    private function getOpcacheStatus(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['enabled' => false, 'message' => lang('admin.performance.opcache_not_loaded')];
        }
        $status = @opcache_get_status(false);
        if ($status === false) {
            return ['enabled' => false, 'message' => lang('admin.performance.opcache_disabled_cli')];
        }
        $mem = $status['memory_usage'] ?? [];
        $stats = $status['opcache_statistics'] ?? [];
        return [
            'enabled' => true,
            'used_memory' => isset($mem['used_memory']) ? round($mem['used_memory'] / 1024 / 1024, 2) : 0,
            'free_memory' => isset($mem['free_memory']) ? round($mem['free_memory'] / 1024 / 1024, 2) : 0,
            'num_cached_scripts' => $stats['num_cached_scripts'] ?? 0,
            'hits' => $stats['hits'] ?? 0,
            'misses' => $stats['misses'] ?? 0,
            'hit_rate' => isset($stats['hits'], $stats['misses']) && ($stats['hits'] + $stats['misses']) > 0
                ? round(100 * $stats['hits'] / ($stats['hits'] + $stats['misses']), 1) : 0,
        ];
    }

    private function getCacheSummary(): array
    {
        $base = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : (dirname(__DIR__, 2));
        $viewsDir = $base . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'views';
        $viewCount = 0;
        if (is_dir($viewsDir)) {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($viewsDir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iter as $item) {
                if ($item->isFile()) {
                    $viewCount++;
                }
            }
        }
        $cacheDir = $base . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        $cacheFileCount = 0;
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . DIRECTORY_SEPARATOR . '*.cache');
            $cacheFileCount = is_array($files) ? count($files) : 0;
            foreach ([\Forecor\Core\Router::ROUTES_CACHE_FILENAME, \Forecor\Core\Router::ROUTES_LEGACY_CACHE_FILENAME] as $routeCacheName) {
                $routesFile = $cacheDir . DIRECTORY_SEPARATOR . $routeCacheName;
                if (is_file($routesFile) && !in_array($routesFile, $files ?: [], true)) {
                    $cacheFileCount++;
                }
            }
        }
        return [
            'twig_cache_enabled' => true,
            'twig_cache_path' => 'Content/storage/views',
            'twig_cached_files' => $viewCount,
            'app_cache_files' => $cacheFileCount,
        ];
    }

    private function redirectToPerformance(string $tab): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        header('Location: ' . core_url($adminPath . '/performance/' . urlencode($tab)), true, 302);
        exit;
    }

    private function updateCache(): void
    {
        $driver = isset($_POST['cache_driver']) && $_POST['cache_driver'] === 'redis' ? 'redis' : 'file';
        $this->setSetting('cache_driver', $driver, 'performance');
        $this->setSetting('redis_host', trim((string) ($_POST['redis_host'] ?? '127.0.0.1')), 'performance');
        $this->setSetting('redis_port', (string) (int) ($_POST['redis_port'] ?? 6379), 'performance');
        $this->setSetting('redis_password', trim((string) ($_POST['redis_password'] ?? '')), 'performance');
        $this->setSetting('redis_username', trim((string) ($_POST['redis_username'] ?? '')), 'performance');
        $this->setSetting('cache_key_prefix', trim((string) ($_POST['cache_key_prefix'] ?? '')), 'performance');
        $this->app->cache(); // reset cached instance next time by clearing internal cache not possible without new request; next request will use new settings
    }

    private function updateMinify(): void
    {
        if (array_key_exists('minify_html', $_POST)) {
            $this->setSetting('minify_html', isset($_POST['minify_html']) && $_POST['minify_html'] === '1' ? '1' : '0', 'performance');
        }
        if (array_key_exists('minify_css', $_POST)) {
            $this->setSetting('minify_css', isset($_POST['minify_css']) && $_POST['minify_css'] === '1' ? '1' : '0', 'performance');
        }
        if (array_key_exists('minify_js', $_POST)) {
            $this->setSetting('minify_js', isset($_POST['minify_js']) && $_POST['minify_js'] === '1' ? '1' : '0', 'performance');
        }
        if (array_key_exists('cdn_url', $_POST)) {
            $this->setSetting('cdn_url', trim((string) ($_POST['cdn_url'] ?? '')), 'performance');
        }
        if (array_key_exists('gzip_enabled', $_POST)) {
            $this->setSetting('gzip_enabled', isset($_POST['gzip_enabled']) && $_POST['gzip_enabled'] === '1' ? '1' : '0', 'performance');
        }
        if (array_key_exists('consolidate_assets', $_POST)) {
            $this->setSetting('consolidate_assets', isset($_POST['consolidate_assets']) && $_POST['consolidate_assets'] === '1' ? '1' : '0', 'performance');
        }
        if (array_key_exists('minify_consolidated', $_POST)) {
            $this->setSetting('minify_consolidated', isset($_POST['minify_consolidated']) && $_POST['minify_consolidated'] === '1' ? '1' : '0', 'performance');
        }
        if (array_key_exists('jquery_cdn_url', $_POST)) {
            $this->setSetting('jquery_cdn_url', trim((string) ($_POST['jquery_cdn_url'] ?? '')), 'performance');
        }
        if (array_key_exists('jquery_enabled', $_POST)) {
            $this->setSetting('jquery_enabled', isset($_POST['jquery_enabled']) && $_POST['jquery_enabled'] === '1' ? '1' : '0', 'performance');
        }
        if (array_key_exists('ajax_enabled', $_POST)) {
            $this->setSetting('ajax_enabled', isset($_POST['ajax_enabled']) && $_POST['ajax_enabled'] === '1' ? '1' : '0', 'performance');
        }
    }

    private function updateAssets(): void
    {
        $this->setSetting('lazy_load_images', isset($_POST['lazy_load_images']) && $_POST['lazy_load_images'] === '1' ? '1' : '0', 'performance');
        $this->setSetting('image_optimize', isset($_POST['image_optimize']) && $_POST['image_optimize'] === '1' ? '1' : '0', 'performance');
    }

    private function updateVarnish(): void
    {
        $enabled = isset($_POST['varnish_enabled']) && $_POST['varnish_enabled'] === '1' ? '1' : '0';
        $this->setSetting('varnish_enabled', $enabled, 'performance');

        $servers = trim((string) ($_POST['varnish_servers'] ?? '127.0.0.1:6081'));
        $this->setSetting('varnish_servers', $servers, 'performance');

        $secret = trim((string) ($_POST['varnish_secret'] ?? ''));
        $this->setSetting('varnish_secret', $secret, 'performance');
    }
}
