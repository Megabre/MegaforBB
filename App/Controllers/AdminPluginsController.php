<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Setting;
use App\Services\PluginLoader;

/**
 * Admin: Eklenti listesi ve etkinleştir / devre dışı bırak.
 * Plugins are defined in plugins/ via plugin.php; disabled ones do not hook into events.
 */
class AdminPluginsController extends AdminController
{
    public function index(): string
    {
        $basePath = $this->app->getBasePath();
        $available = PluginLoader::getAvailablePlugins($basePath);
        $disabled = PluginLoader::getDisabledPlugins();

        $plugins = [];
        foreach ($available as $name) {
            $meta = PluginLoader::getPluginMetadata($basePath, $name);
            $plugins[] = [
                'id' => $name,
                'name' => $meta !== null ? $meta['name'] : $name,
                'version' => $meta !== null ? $meta['version'] : '',
                'description' => $meta !== null ? $meta['description'] : '',
                'author' => $meta !== null ? $meta['author'] : '',
                'path' => 'Inc/Plugin/' . $name,
                'enabled' => !in_array($name, $disabled, true),
                'installed' => PluginLoader::isPluginInstalled($name),
                'has_install' => PluginLoader::hasInstallFile($basePath, $name),
                'has_uninstall' => PluginLoader::hasUninstallFile($basePath, $name),
            ];
        }

        return $this->view('plugins/index', [
            'pageTitle' => lang('admin.plugins.title'),
            'plugins' => $plugins,
        ]);
    }

    /** Tek eklentiyi etkinleştir veya devre dışı bırak. Etkinleştirirken install.php varsa bir kez çalıştırılır. */
    public function toggle(): void
    {
        if (!core_csrf_valid('admin_plugins_toggle', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
            return;
        }
        $id = trim((string) ($_POST['id'] ?? ''));
        if ($id === '' || preg_match('/[^a-zA-Z0-9_-]/', $id)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
            return;
        }
        $basePath = $this->app->getBasePath();
        $available = PluginLoader::getAvailablePlugins($basePath);
        if (!in_array($id, $available, true)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
            return;
        }

        $disabled = PluginLoader::getDisabledPlugins();
        $key = array_search($id, $disabled, true);
        if ($key !== false) {
            // Etkinleştir: önce install çalıştır (kurulmamışsa), sonra disabled'dan çıkar
            if (!PluginLoader::isPluginInstalled($id)) {
                if (!PluginLoader::runPluginInstall($basePath, $id, $this->app)) {
                    $this->app->session()->getFlashBag()->add('error', lang('admin.plugins.install_failed') ?: 'Kurulum başarısız. install.sql veya install.php hatası olabilir.');
                    $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
                    return;
                }
            }
            unset($disabled[$key]);
            $disabled = array_values($disabled);
        } else {
            $disabled[] = $id;
        }
        Setting::setValue(PluginLoader::SETTING_DISABLED, json_encode($disabled, JSON_UNESCAPED_UNICODE), 'forum');
        PluginLoader::clearCompiledRouteCaches($basePath);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
    }

    /** Eklentiyi kaldır: uninstall.php çalıştırılır, tüm kalıntılar temizlenir. */
    public function uninstall(): void
    {
        if (!core_csrf_valid('admin_plugins_uninstall', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
            return;
        }
        $id = trim((string) ($_POST['id'] ?? ''));
        if ($id === '' || preg_match('/[^a-zA-Z0-9_-]/', $id)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
            return;
        }
        $basePath = $this->app->getBasePath();
        $available = PluginLoader::getAvailablePlugins($basePath);
        if (!in_array($id, $available, true)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
            return;
        }
        PluginLoader::runPluginUninstall($basePath, $id, $this->app);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
    }
}
