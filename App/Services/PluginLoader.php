<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Plugin/Module Loader: config/events.php'ye dokunmadan eklenti dizininden
 * event–listener çiftlerini yükleyip dispatcher'a eklenebilecek formatta döndürür.
 *
 * Sözleşme:
 * - plugins/{PluginName}/plugin.php → listener dizisi (zorunlu)
 * - plugins/{PluginName}/plugin.json → name, version, description, author (isteğe bağlı)
 * - plugins/{PluginName}/routes.php → $router ile rota kaydı (isteğe bağlı)
 * - plugins/{PluginName}/views/ → şablon dizini (Twig eklendiğinde @PluginName namespace ile kullanılır)
 *
 * Devre dışı bırakma: Ayarlar tablosunda disabled_plugins (JSON dizi) ile belirtilen
 * klasör adları yüklenmez.
 */
final class PluginLoader
{
    /** Premium MVC: eklentiler Inc/Plugin altında. */
    private static function getPluginsDirSegment(): string
    {
        return 'Inc' . \DIRECTORY_SEPARATOR . 'Plugin';
    }

    public const SETTING_DISABLED = 'disabled_plugins';
    public const SETTING_INSTALLED = 'plugin_installed';

    /** Eklenti kök dizin yolu. */
    public static function getPluginDir(string $basePath, string $pluginId): string
    {
        return rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . self::getPluginsDirSegment() . \DIRECTORY_SEPARATOR . $pluginId;
    }

    /**
     * Read single plugin metadata from plugin.json (null if missing; folder name used for name).
     *
     * @return array{name: string, version: string, description: string, author: string}|null
     */
    public static function getPluginMetadata(string $basePath, string $pluginId): ?array
    {
        $dir = self::getPluginDir($basePath, $pluginId);
        $file = $dir . \DIRECTORY_SEPARATOR . 'plugin.json';
        if (!is_file($file)) {
            return null;
        }
        $json = @file_get_contents($file);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        return [
            'name'        => (string) ($data['name'] ?? $pluginId),
            'version'     => (string) ($data['version'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'author'      => (string) ($data['author'] ?? ''),
        ];
    }

    /**
     * Etkin eklentilerin views dizinlerini döndürür (Twig namespace => tam yol).
     * Twig entegre edildiğinde: foreach (PluginLoader::getPluginViewPaths($basePath) as $ns => $path) { $twigLoader->addPath($path, $ns); }
     *
     * @return array<string, string>
     */
    public static function getPluginViewPaths(string $basePath): array
    {
        $available = self::getAvailablePlugins($basePath);
        $disabled = self::getDisabledPlugins();
        $pluginsDir = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . self::getPluginsDirSegment();
        $paths = [];
        foreach ($available as $name) {
            if (in_array($name, $disabled, true)) {
                continue;
            }
            $viewsDir = $pluginsDir . \DIRECTORY_SEPARATOR . $name . \DIRECTORY_SEPARATOR . 'views';
            if (is_dir($viewsDir)) {
                $paths[$name] = $viewsDir;
            }
        }
        return $paths;
    }

    /**
     * Etkin eklentilerin routes.php dosyalarını yükler; her dosyada $router kullanılabilir.
     */
    public static function loadPluginRoutes(string $basePath, object $router): void
    {
        $available = self::getAvailablePlugins($basePath);
        $disabled = self::getDisabledPlugins();
        $pluginsDir = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . self::getPluginsDirSegment();
        foreach ($available as $name) {
            if (in_array($name, $disabled, true)) {
                continue;
            }
            $routesFile = $pluginsDir . \DIRECTORY_SEPARATOR . $name . \DIRECTORY_SEPARATOR . 'routes.php';
            if (is_file($routesFile)) {
                try {
                    (function () use ($routesFile, $router): void {
                        require $routesFile;
                    })();
                } catch (\Throwable $e) {
                    // Eklenti routes hatası uygulamayı kırmasın
                }
            }
        }
    }

    /**
     * plugins/ altındaki geçerli eklenti klasör adlarını döndürür (plugin.php olanlar).
     *
     * @return list<string>
     */
    public static function getAvailablePlugins(string $basePath): array
    {
        $pluginsDir = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . self::getPluginsDirSegment();
        if (!is_dir($pluginsDir)) {
            return [];
        }
        $list = [];
        $dirs = @scandir($pluginsDir) ?: [];
        foreach ($dirs as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $pluginsDir . \DIRECTORY_SEPARATOR . $name;
            if (!is_dir($path)) {
                continue;
            }
            if (is_file($path . \DIRECTORY_SEPARATOR . 'plugin.php')) {
                $list[] = $name;
            }
        }
        sort($list);
        return $list;
    }

    /**
     * Devre dışı bırakılmış eklenti klasör adlarını döndürür (settings tablosu).
     *
     * @return list<string>
     */
    public static function getDisabledPlugins(): array
    {
        if (!class_exists(\App\Models\Setting::class)) {
            return [];
        }
        $raw = \App\Models\Setting::getValue(self::SETTING_DISABLED, '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
    }

    /**
     * Normalizes array returned from plugin.php: 'events' | 'actions' | 'filters' or legacy format (all events).
     *
     * @return array{events: array, actions: array, filters: array}
     */
    public static function parsePluginDefinition(string $pluginFile): array
    {
        try {
            $result = require $pluginFile;
            if (!is_array($result)) {
                return ['events' => [], 'actions' => [], 'filters' => []];
            }
            $events = isset($result['events']) && is_array($result['events']) ? $result['events'] : $result;
            $actions = isset($result['actions']) && is_array($result['actions']) ? $result['actions'] : [];
            $filters = isset($result['filters']) && is_array($result['filters']) ? $result['filters'] : [];
            return ['events' => $events, 'actions' => $actions, 'filters' => $filters];
        } catch (\Throwable $e) {
            return ['events' => [], 'actions' => [], 'filters' => []];
        }
    }

    /**
     * Eklenti dizinini tara; devre dışı olmayan her eklentinin plugin.php'sinden listener tanımlarını topla ve birleştir.
     *
     * @return array<string, list<array{0: string|object, 1: string}>>
     */
    public static function loadListeners(string $basePath): array
    {
        $pluginsDir = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . self::getPluginsDirSegment();
        if (!is_dir($pluginsDir)) {
            return [];
        }
        $disabled = self::getDisabledPlugins();
        $merged = [];
        $dirs = @scandir($pluginsDir) ?: [];
        foreach ($dirs as $name) {
            if ($name === '.' || $name === '..' || in_array($name, $disabled, true)) {
                continue;
            }
            $pluginFile = $pluginsDir . \DIRECTORY_SEPARATOR . $name . \DIRECTORY_SEPARATOR . 'plugin.php';
            if (!is_file($pluginFile)) {
                continue;
            }
            $def = self::parsePluginDefinition($pluginFile);
            foreach ($def['events'] as $eventName => $entries) {
                if (!is_string($eventName) || !is_array($entries)) {
                    continue;
                }
                foreach ((array) $entries as $entry) {
                    if (is_array($entry) && count($entry) >= 2) {
                        $merged[$eventName] = $merged[$eventName] ?? [];
                        $merged[$eventName][] = [$entry[0], $entry[1]];
                    }
                }
            }
        }
        return $merged;
    }

    /**
     * Etkin eklentilerin actions ve filters tanımlarını HookService'e kaydeder.
     */
    public static function loadHooks(string $basePath, HookService $hookService): void
    {
        $pluginsDir = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . self::getPluginsDirSegment();
        if (!is_dir($pluginsDir)) {
            return;
        }
        $disabled = self::getDisabledPlugins();
        $dirs = @scandir($pluginsDir) ?: [];
        foreach ($dirs as $name) {
            if ($name === '.' || $name === '..' || in_array($name, $disabled, true)) {
                continue;
            }
            $pluginFile = $pluginsDir . \DIRECTORY_SEPARATOR . $name . \DIRECTORY_SEPARATOR . 'plugin.php';
            if (!is_file($pluginFile)) {
                continue;
            }
            $def = self::parsePluginDefinition($pluginFile);
            foreach ($def['actions'] as $hookName => $callables) {
                if (!is_string($hookName)) {
                    continue;
                }
                $list = is_array($callables) ? $callables : [$callables];
                foreach ($list as $i => $c) {
                    $priority = 10;
                    $callable = $c;
                    if (is_array($c) && count($c) >= 2) {
                        $callable = [$c[0], $c[1]];
                        $priority = (int) ($c[2] ?? 10);
                    }
                    if (is_callable($callable)) {
                        $hookService->addAction($hookName, $callable, $priority + $i);
                    }
                }
            }
            foreach ($def['filters'] as $hookName => $callables) {
                if (!is_string($hookName)) {
                    continue;
                }
                $list = is_array($callables) ? $callables : [$callables];
                foreach ($list as $i => $c) {
                    $priority = 10;
                    $callable = $c;
                    if (is_array($c) && count($c) >= 2) {
                        $callable = [$c[0], $c[1]];
                        $priority = (int) ($c[2] ?? 10);
                    }
                    if (is_callable($callable)) {
                        $hookService->addFilter($hookName, $callable, $priority + $i);
                    }
                }
            }
        }
    }

    // ---------- Install / Uninstall yaşam döngüsü ----------

    /** Eklenti daha önce install edilmiş mi? */
    public static function isPluginInstalled(string $pluginId): bool
    {
        if (!class_exists(\App\Models\Setting::class)) {
            return false;
        }
        $raw = \App\Models\Setting::getValue(self::SETTING_INSTALLED, '{}');
        $decoded = json_decode($raw, true);
        return is_array($decoded) && !empty($decoded[$pluginId]);
    }

    /** Tüm kurulu eklenti id'lerini işaretle (SETTING_INSTALLED JSON). */
    private static function setPluginInstalled(string $pluginId, bool $installed): void
    {
        if (!class_exists(\App\Models\Setting::class)) {
            return;
        }
        $raw = \App\Models\Setting::getValue(self::SETTING_INSTALLED, '{}');
        $decoded = is_array(json_decode($raw, true)) ? json_decode($raw, true) : [];
        if ($installed) {
            $decoded[$pluginId] = true;
        } else {
            unset($decoded[$pluginId]);
        }
        \App\Models\Setting::setValue(self::SETTING_INSTALLED, json_encode($decoded, JSON_UNESCAPED_UNICODE), 'forum');
    }

    /** install.php var mı? */
    public static function hasInstallFile(string $basePath, string $pluginId): bool
    {
        return is_file(self::getPluginDir($basePath, $pluginId) . \DIRECTORY_SEPARATOR . 'install.php');
    }

    /** uninstall.php var mı? (Kaldır butonu için zorunlu kabul edilebilir.) */
    public static function hasUninstallFile(string $basePath, string $pluginId): bool
    {
        return is_file(self::getPluginDir($basePath, $pluginId) . \DIRECTORY_SEPARATOR . 'uninstall.php');
    }

    /**
     * Eklentiyi kurar: install.php çalıştırılır (varsa), kurulum işaretlenir.
     * Etkinleştirildiğinde bir kez çağrılmalı.
     */
    public static function runPluginInstall(string $basePath, string $pluginId, $app = null): bool
    {
        $dir = self::getPluginDir($basePath, $pluginId);
        $installFile = $dir . \DIRECTORY_SEPARATOR . 'install.php';
        if (!is_file($installFile)) {
            self::setPluginInstalled($pluginId, true);
            return true;
        }
        try {
            (function () use ($installFile, $app, $basePath, $pluginId): void {
                $application = $app;
                require $installFile;
            })();
            self::setPluginInstalled($pluginId, true);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Eklentiyi kaldırır: uninstall.php çalıştırılır, kurulum işareti silinir, eklenti devre dışı listesine eklenir
     * (rotalar/menü yüklenmez). Yeniden etkinleştirmek install.php çalıştırır.
     */
    public static function runPluginUninstall(string $basePath, string $pluginId, $app = null): void
    {
        $dir = self::getPluginDir($basePath, $pluginId);
        $uninstallFile = $dir . \DIRECTORY_SEPARATOR . 'uninstall.php';
        if (is_file($uninstallFile)) {
            try {
                (function () use ($uninstallFile, $app): void {
                    $application = $app;
                    require $uninstallFile;
                })();
            } catch (\Throwable $e) {
                // Hata olsa da devam et; işaretleri temizle
            }
        }
        self::setPluginInstalled($pluginId, false);
        // Kaldırıldıktan sonra eklenti dosyası duruyorsa yine de yüklensin istemiyoruz: devre dışı bırak.
        // Yeniden "Etkinleştir" kurulumu (install.php) çalıştırır.
        $disabled = self::getDisabledPlugins();
        if (!in_array($pluginId, $disabled, true)) {
            $disabled[] = $pluginId;
            \App\Models\Setting::setValue(self::SETTING_DISABLED, json_encode(array_values($disabled), JSON_UNESCAPED_UNICODE), 'forum');
        }

        self::clearCompiledRouteCaches($basePath);
    }

    /**
     * Çekirdek + eski rota önbelleğini siler (eklenti rotası değişince zorunlu).
     */
    public static function clearCompiledRouteCaches(string $basePath): void
    {
        $dir = rtrim($basePath, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . 'Content' . \DIRECTORY_SEPARATOR . 'storage' . \DIRECTORY_SEPARATOR . 'cache';
        foreach ([
            \Forecor\Core\Router::ROUTES_CACHE_FILENAME,
            \Forecor\Core\Router::ROUTES_LEGACY_CACHE_FILENAME,
        ] as $name) {
            $f = $dir . \DIRECTORY_SEPARATOR . $name;
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }
}
