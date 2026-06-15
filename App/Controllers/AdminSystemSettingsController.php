<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Admin: System settings — each section presented as a tab (General, Menu, Mail/SMTP, SEO, Storage).
 */
class AdminSystemSettingsController extends AdminController
{
    private const GROUP_SYSTEM = 'system';
    private const GROUP_MAIL = 'mail';
    private const GROUP_SEO = 'seo';
    private const CSRF_TOKEN = 'admin_system_settings';
    private const TABS = ['general', 'menu', 'mail', 'seo', 'storage', 'debug'];

    /** Eski URL: /admin/system-settings → /admin/settings/general yönlendir */
    public function index(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        header('Location: ' . core_url($adminPath . '/settings/general'), true, 302);
        exit;
    }

    /** Path tabanlı bölüm: /admin/settings/{section} — tab yok, menüden dallanır */
    public function showSection(string $section): string
    {
        $section = strtolower(trim($section));
        if (!in_array($section, self::TABS, true)) {
            $adminPath = env('ADMIN_PATH', 'admin');
            header('Location: ' . core_url($adminPath . '/settings/general'), true, 302);
            exit;
        }
        return $this->renderSection($section);
    }

    private function renderSection(string $activeTab): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $menuJson = $this->getSetting('top_menu_items', '');
        $menuItemsRaw = $menuJson !== '' ? (array) json_decode($menuJson, true) : $this->defaultMenuItems();
        $menuItems = $this->normalizeMenuItemsForAdmin($menuItemsRaw);
        $menuItemsTree = $this->buildMenuTreeForAdmin($menuItems);
        $footerMenuJson = $this->getSetting('footer_menu_items', '');
        $footerMenuItems = $footerMenuJson !== '' ? (array) json_decode($footerMenuJson, true) : $this->defaultFooterMenuItems();
        $footerQuickLinksJson = $this->getSetting('footer_quick_links', '');
        $footerQuickLinks = $footerQuickLinksJson !== '' ? (array) json_decode($footerQuickLinksJson, true) : $this->defaultFooterQuickLinks();

        $settings = [
            'menu_items_tree' => $menuItemsTree,
            'enable_timeline' => $this->getSetting('enable_timeline', '1') === '1',
            'timeline_title' => $this->getSetting('timeline_title', lang('admin.sys_settings.timeline_title_default')),
            'timeline_description' => $this->getSetting('timeline_description', lang('admin.sys_settings.timeline_description_default')),
            'forum_logo_url' => $this->getSetting('forum_logo_url', ''),
            'forum_favicon_url' => $this->getSetting('forum_favicon_url', ''),
            'show_site_title_next_to_logo' => $this->getSetting('show_site_title_next_to_logo', '1') === '1',
            'maintenance_mode' => $this->getSetting('maintenance_mode', '0') === '1',
            'registration_require_email_verification' => $this->getSetting('registration_require_email_verification', '0') === '1',
            'registration_requires_approval' => $this->getSetting('registration_requires_approval', '0') === '1',
            'registration_requires_invite' => $this->getSetting('registration_requires_invite', '0') === '1',
            'registration_show_first_name' => $this->getSetting('registration_show_first_name', '1'),
            'registration_show_last_name' => $this->getSetting('registration_show_last_name', '1'),
            'profile_comments_enabled' => $this->getSetting('profile_comments_enabled', '1') === '1',
            'documentation_enabled' => $this->getSetting('documentation_enabled', '0') === '1',
            'social_facebook' => $this->getSetting('social_facebook', ''),
            'social_twitter' => $this->getSetting('social_twitter', ''),
            'social_instagram' => $this->getSetting('social_instagram', ''),
            'social_youtube' => $this->getSetting('social_youtube', ''),
            'social_linkedin' => $this->getSetting('social_linkedin', ''),
            'social_show_header' => $this->getSetting('social_show_header', '1') === '1',
            'social_show_footer' => $this->getSetting('social_show_footer', '1') === '1',
            'social_show_sidebar' => $this->getSetting('social_show_sidebar', '0') === '1',
            'admin_path' => $adminPath,
            'top_menu_items' => $menuItems,
            'footer_menu_items' => $footerMenuItems,
            'footer_quick_links' => $footerQuickLinks,
            'sef_url_mode' => $this->getSetting('sef_url_mode', $this->getSetting('sef_topic_url_mode', 'id')),
            'seo_site_name' => $this->getSetting('seo_site_name', ''),
            'seo_description' => $this->getSetting('seo_description', ''),
            'seo_keywords' => $this->getSetting('seo_keywords', ''),
            'og_title' => $this->getSetting('og_title', ''),
            'og_description' => $this->getSetting('og_description', ''),
            'og_image' => $this->getSetting('og_image', ''),
            'og_type' => $this->getSetting('og_type', 'website'),
            'schema_json' => $this->getSetting('schema_json', ''),
            'schema_form' => $this->parseSchemaJsonForForm($this->getSetting('schema_json', '')),
            'robots_txt_content' => $this->getSetting('robots_txt_content', ''),
            'custom_css' => $this->getSetting('custom_css', ''),
            'custom_js' => $this->getSetting('custom_js', ''),
            'mail_driver' => $this->getSetting('mail_driver', 'smtp'),
            'smtp_host' => $this->getSetting('smtp_host', ''),
            'smtp_port' => $this->getSetting('smtp_port', '587'),
            'smtp_username' => $this->getSetting('smtp_username', ''),
            'smtp_password' => $this->getSetting('smtp_password', ''),
            'smtp_encryption' => $this->getSetting('smtp_encryption', 'tls'),
            'mail_from_address' => $this->getSetting('mail_from_address', ''),
            'mail_from_name' => $this->getSetting('mail_from_name', core_config('app.name', 'MegaforBB')),
            'error_404_action' => $this->getSetting('error_404_action', 'page'),
            'error_404_redirect_url' => $this->getSetting('error_404_redirect_url', ''),
            'home_page_type' => $this->getSetting('home_page_type', 'forum'),
            'home_page_custom_url' => $this->getSetting('home_page_custom_url', ''),
            'cron_token' => $this->getSetting('cron_token', ''),
            'composer_binary_path' => $this->getSetting('composer_binary_path', ''),
            'npx_binary_path' => $this->getSetting('npx_binary_path', ''),
            'storage_driver' => $this->getSetting('storage_driver', 'local'),
            'storage_local_path' => $this->getSetting('storage_local_path', 'uploads'),
            'storage_aws_s3_key' => $this->getSetting('storage_aws_s3_key', ''),
            'storage_aws_s3_secret' => $this->getSetting('storage_aws_s3_secret', ''),
            'storage_aws_s3_region' => $this->getSetting('storage_aws_s3_region', 'us-east-1'),
            'storage_aws_s3_bucket' => $this->getSetting('storage_aws_s3_bucket', ''),
            'storage_aws_s3_prefix' => $this->getSetting('storage_aws_s3_prefix', ''),
            'storage_aws_s3_cdn_url' => $this->getSetting('storage_aws_s3_cdn_url', ''),
            'storage_r2_key' => $this->getSetting('storage_r2_key', ''),
            'storage_r2_secret' => $this->getSetting('storage_r2_secret', ''),
            'storage_r2_endpoint' => $this->getSetting('storage_r2_endpoint', ''),
            'storage_r2_bucket' => $this->getSetting('storage_r2_bucket', ''),
            'storage_r2_prefix' => $this->getSetting('storage_r2_prefix', ''),
            'storage_r2_cdn_url' => $this->getSetting('storage_r2_cdn_url', ''),
        ];
        $flashSuccess = $this->app->session()->getFlashBag()->get('settings_success');
        $flashError = $this->app->session()->getFlashBag()->get('settings_error');
        $flashSuccess = is_array($flashSuccess) ? ($flashSuccess[0] ?? '') : (string) $flashSuccess;
        $flashError = is_array($flashError) ? ($flashError[0] ?? '') : (string) $flashError;

        $cronToken = $settings['cron_token'] ?? '';
        $cronUrlFull = $this->getCronUrlFull($cronToken);
        $cronCliExample = $cronToken !== ''
            ? 'php /path/to/public/cron.php token=' . $cronToken
            : 'php /path/to/public/cron.php';

        $appDebug = core_config('app.debug', (bool) env('APP_DEBUG', false));
        $logPath = '';
        $recentLogLines = [];
        if (class_exists(\App\Services\ErrorLogger::class)) {
            $logPath = \App\Services\ErrorLogger::getLogPath();
            $recentLogLines = \App\Services\ErrorLogger::getRecentLines(150);
        }

        return $this->view('system_settings/index', [
            'pageTitle' => lang('admin.sys_settings.page_title'),
            'settings' => $settings,
            'activeTab' => $activeTab,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'cronUrlFull' => $cronUrlFull,
            'cronCliExample' => $cronCliExample,
            'appDebug' => $appDebug,
            'logPath' => $logPath,
            'recentLogLines' => $recentLogLines,
        ]);
    }

    /** Tam URL (wget/curl için); sunucu cron'unda mutlaka tam URL kullanılmalı. */
    private function getCronUrlFull(string $cronToken): string
    {
        $base = rtrim((string) core_config('app.url', ''), '/');
        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = function_exists('app_url_base_path') ? app_url_base_path() : '';
            $base = $scheme . '://' . $host . $basePath;
        }
        $query = $cronToken !== '' ? '?token=' . rawurlencode($cronToken) : '';
        return rtrim($base, '/') . '/cron.php' . $query;
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.invalid_request'));
            $this->redirectToSettings('general');
            return;
        }
        $tab = (string) ($_POST['tab'] ?? 'general');
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'general';
        }

        try {
            if ($tab === 'debug') {
                $appDebug = isset($_POST['app_debug']) && ($_POST['app_debug'] === '1' || $_POST['app_debug'] === 'on');
                $this->setSetting('app_debug', $appDebug ? '1' : '0');
                $this->app->session()->getFlashBag()->add('settings_success', lang('admin.sys_settings.saved'));
                $this->redirectToSettings('debug');
                return;
            }
            if ($tab === 'general') {
                $this->updateGeneral();
            } elseif ($tab === 'menu') {
                $this->updateMenu();
                $this->updateFooterMenu();
                $this->updateFooterQuickLinks();
            } elseif ($tab === 'seo') {
                $this->updateSeo();
            } elseif ($tab === 'storage') {
                $this->updateStorage();
            } else {
                $this->updateMail();
            }
            $this->app->session()->getFlashBag()->add('settings_success', lang('admin.sys_settings.saved'));
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.save_error') . $e->getMessage());
        }
        $this->redirectToSettings($tab);
    }

    /**
     * Sends SMTP test email using saved settings. Save mail settings first.
     */
    public function mailTest(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.invalid_request'));
            $this->redirectToSettings('mail');
            return;
        }
        $to = trim((string) ($_POST['test_email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.valid_test_email'));
            $this->redirectToSettings('mail');
            return;
        }
        $fromAddress = $this->getSetting('mail_from_address', '');
        if ($fromAddress === '') {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.fill_from_first'));
            $this->redirectToSettings('mail');
            return;
        }
        try {
            $mailer = new \App\Services\MailService($this->app);
            $subject = lang('admin.sys_settings.test_email_subject');
            $bodyHtml = lang('admin.sys_settings.test_email_body');
            $sent = $mailer->send($to, $subject, $bodyHtml, strip_tags($bodyHtml));
            if ($sent) {
                $this->app->session()->getFlashBag()->add('settings_success', lang('admin.sys_settings.test_email_sent', ['to' => $to]));
            } else {
                $detail = $mailer->getLastError();
                $this->app->session()->getFlashBag()->add('settings_error', $detail !== '' ? $detail : lang('admin.sys_settings.test_email_fail'));
            }
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.test_email_error') . $e->getMessage());
        }
        $this->redirectToSettings('mail');
    }

    /**
     * POST: Eski yerel yükleme dizinindeki dosyaları mevcut dizine taşır (storage_local_path değişince kullanın).
     */
    public function storageSync(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.invalid_request'));
            $this->redirectToSettings('storage');
            return;
        }
        $basePath = $this->app->getBasePath();
        $sync = new \App\Services\StorageSyncService($basePath);
        $result = $sync->syncToCurrentRoot();
        $moved = (int) ($result['moved'] ?? 0);
        $errors = $result['errors'] ?? [];
        $updatedUsers = (int) ($result['updated_users'] ?? 0);
        $updatedPosts = (int) ($result['updated_posts'] ?? 0);
        $parts = [];
        if ($updatedUsers > 0 || $updatedPosts > 0) {
            $parts[] = sprintf(lang('admin.sys_settings.storage_sync_db_updated') ?? '%d kullanıcı path\'i, %d post içeriği güncellendi.', $updatedUsers, $updatedPosts);
        }
        if ($moved > 0) {
            $parts[] = sprintf(lang('admin.sys_settings.storage_sync_done') ?? '%d dosya mevcut dizine taşındı.', $moved);
        }
        if (!empty($errors)) {
            $parts[] = (lang('admin.sys_settings.storage_sync_errors') ?? 'Hatalar: ') . implode('; ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' …' : '');
        }
        $msg = implode(' ', $parts);
        if ($msg !== '') {
            $this->app->session()->getFlashBag()->add(!empty($errors) && $moved === 0 && $updatedUsers === 0 && $updatedPosts === 0 ? 'settings_error' : 'settings_success', trim($msg));
        } else {
            $this->app->session()->getFlashBag()->add('settings_success', lang('admin.sys_settings.storage_sync_nothing') ?? 'Veritabanı path\'leri güncellendi; taşınacak dosya yok.');
        }
        $this->redirectToSettings('storage');
    }

    /**
     * POST: Tüm SEF URL'leri mevcut moda göre günceller (slug veya url_key doldurur).
     */
    public function rebuildSefUrls(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.invalid_request'));
            $this->redirectToSettings('seo');
            return;
        }
        try {
            $svc = new \App\Services\SefUrlService();
            $counts = $svc->rebuildAllUrls();
            $total = array_sum($counts);
            $msg = $total > 0
                ? lang('admin.sys_settings.rebuild_sef_done', ['count' => $total, 'topics' => $counts['topics'], 'posts' => $counts['posts'], 'conversations' => $counts['conversations'], 'notifications' => $counts['notifications'], 'attachments' => $counts['attachments'], 'users' => $counts['users']])
                : lang('admin.sys_settings.rebuild_sef_nothing');
            $this->app->session()->getFlashBag()->add('settings_success', $msg);
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.rebuild_sef_error') . ' ' . $e->getMessage());
        }
        $this->redirectToSettings('seo');
    }

    /**
     * POST: Sitemap'ı yeniden oluşturur (dinamik olduğu için sadece ping/cache temizleme simüle edilir)
     */
    public function rebuildSitemap(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.sys_settings.invalid_request'));
            $this->redirectToSettings('seo');
            return;
        }
        try {
            // Sitemap dinamik oluşturulur, bu yüzden sadece başarılı mesajı döner
            // Gelecekte cache vb. olursa burada temizlenebilir
            $this->app->session()->getFlashBag()->add('settings_success', 'Sitemap başarıyla yeniden oluşturuldu.');
        } catch (\Throwable $e) {
            $this->app->session()->getFlashBag()->add('settings_error', 'Sitemap hatası: ' . $e->getMessage());
        }
        $this->redirectToSettings('seo');
    }

    private function redirectToSettings(string $tab): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        header('Location: ' . core_url($adminPath . '/settings/' . urlencode($tab)), true, 302);
        exit;
    }

    private function updateGeneral(): void
    {
        $url = isset($_POST['forum_logo_url']) ? trim((string) $_POST['forum_logo_url']) : '';
        $this->setSetting('forum_logo_url', $url, self::GROUP_SYSTEM);
        $faviconUrl = isset($_POST['forum_favicon_url']) ? trim((string) $_POST['forum_favicon_url']) : '';
        $this->setSetting('forum_favicon_url', $faviconUrl, self::GROUP_SYSTEM);
        $show = isset($_POST['show_site_title_next_to_logo']) && $_POST['show_site_title_next_to_logo'] === '1' ? '1' : '0';
        $this->setSetting('show_site_title_next_to_logo', $show, self::GROUP_SYSTEM);
        $maintenance = isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] === '1' ? '1' : '0';
        $this->setSetting('maintenance_mode', $maintenance, self::GROUP_SYSTEM);
        $timeline = isset($_POST['enable_timeline']) && $_POST['enable_timeline'] === '1' ? '1' : '0';
        $this->setSetting('enable_timeline', $timeline, self::GROUP_SYSTEM);
        $timelineTitle = trim((string) ($_POST['timeline_title'] ?? ''));
        $this->setSetting('timeline_title', $timelineTitle !== '' ? $timelineTitle : lang('admin.sys_settings.timeline_title_default'), self::GROUP_SYSTEM);
        $this->setSetting('timeline_description', trim((string) ($_POST['timeline_description'] ?? '')), self::GROUP_SYSTEM);
        $docEnabled = isset($_POST['documentation_enabled']) && $_POST['documentation_enabled'] === '1' ? '1' : '0';
        $this->setSetting('documentation_enabled', $docEnabled, self::GROUP_SYSTEM);
        $socialUrls = ['social_facebook', 'social_twitter', 'social_instagram', 'social_youtube', 'social_linkedin'];
        foreach ($socialUrls as $k) {
            $this->setSetting($k, isset($_POST[$k]) ? trim((string) $_POST[$k]) : '', self::GROUP_SYSTEM);
        }
        $this->setSetting('social_show_header', isset($_POST['social_show_header']) && $_POST['social_show_header'] === '1' ? '1' : '0', self::GROUP_SYSTEM);
        $this->setSetting('social_show_footer', isset($_POST['social_show_footer']) && $_POST['social_show_footer'] === '1' ? '1' : '0', self::GROUP_SYSTEM);
        $this->setSetting('social_show_sidebar', isset($_POST['social_show_sidebar']) && $_POST['social_show_sidebar'] === '1' ? '1' : '0', self::GROUP_SYSTEM);
        $homeType = in_array((string) ($_POST['home_page_type'] ?? ''), ['forum', 'articles', 'portal', 'custom_url'], true) ? (string) $_POST['home_page_type'] : 'forum';
        $this->setSetting('home_page_type', $homeType, self::GROUP_SYSTEM);
        $customUrl = isset($_POST['home_page_custom_url']) ? trim((string) $_POST['home_page_custom_url']) : '';
        $this->setSetting('home_page_custom_url', $customUrl, self::GROUP_SYSTEM);

        $cronToken = isset($_POST['cron_token']) ? trim((string) $_POST['cron_token']) : '';
        $this->setSetting('cron_token', $cronToken, self::GROUP_SYSTEM);

        $composerPath = isset($_POST['composer_binary_path']) ? trim((string) $_POST['composer_binary_path']) : '';
        $this->setSetting('composer_binary_path', $composerPath, self::GROUP_SYSTEM);

        $npxPath = isset($_POST['npx_binary_path']) ? trim((string) $_POST['npx_binary_path']) : '';
        $this->setSetting('npx_binary_path', $npxPath, self::GROUP_SYSTEM);

        $action = isset($_POST['error_404_action']) && $_POST['error_404_action'] === 'redirect' ? 'redirect' : 'page';
        $this->setSetting('error_404_action', $action, self::GROUP_SYSTEM);
        $errUrl = isset($_POST['error_404_redirect_url']) ? trim((string) $_POST['error_404_redirect_url']) : '';
        $this->setSetting('error_404_redirect_url', $errUrl, self::GROUP_SYSTEM);
    }

    private const GROUP_STORAGE = 'storage';

    private function updateStorage(): void
    {
        $driver = isset($_POST['storage_driver']) && in_array($_POST['storage_driver'], ['local', 'aws_s3', 'r2'], true)
            ? $_POST['storage_driver'] : 'local';
        $this->setSetting('storage_driver', $driver, self::GROUP_STORAGE);

        $this->setSetting('storage_aws_s3_key', trim((string) ($_POST['storage_aws_s3_key'] ?? '')), self::GROUP_STORAGE);
        if (trim((string) ($_POST['storage_aws_s3_secret'] ?? '')) !== '') {
            $this->setSetting('storage_aws_s3_secret', trim((string) $_POST['storage_aws_s3_secret']), self::GROUP_STORAGE);
        }
        $this->setSetting('storage_aws_s3_region', trim((string) ($_POST['storage_aws_s3_region'] ?? 'us-east-1')) ?: 'us-east-1', self::GROUP_STORAGE);
        $this->setSetting('storage_aws_s3_bucket', trim((string) ($_POST['storage_aws_s3_bucket'] ?? '')), self::GROUP_STORAGE);
        $this->setSetting('storage_aws_s3_prefix', trim((string) ($_POST['storage_aws_s3_prefix'] ?? '')), self::GROUP_STORAGE);
        $this->setSetting('storage_aws_s3_cdn_url', trim((string) ($_POST['storage_aws_s3_cdn_url'] ?? '')), self::GROUP_STORAGE);

        $this->setSetting('storage_r2_key', trim((string) ($_POST['storage_r2_key'] ?? '')), self::GROUP_STORAGE);
        if (trim((string) ($_POST['storage_r2_secret'] ?? '')) !== '') {
            $this->setSetting('storage_r2_secret', trim((string) $_POST['storage_r2_secret']), self::GROUP_STORAGE);
        }
        $this->setSetting('storage_r2_endpoint', trim((string) ($_POST['storage_r2_endpoint'] ?? '')), self::GROUP_STORAGE);
        $this->setSetting('storage_r2_bucket', trim((string) ($_POST['storage_r2_bucket'] ?? '')), self::GROUP_STORAGE);
        $this->setSetting('storage_r2_prefix', trim((string) ($_POST['storage_r2_prefix'] ?? '')), self::GROUP_STORAGE);
        $this->setSetting('storage_r2_cdn_url', trim((string) ($_POST['storage_r2_cdn_url'] ?? '')), self::GROUP_STORAGE);

        $localPath = trim((string) ($_POST['storage_local_path'] ?? 'uploads'));
        if (in_array($localPath, ['uploads', 'Content/storage/uploads'], true)) {
            $this->setSetting('storage_local_path', $localPath, self::GROUP_STORAGE);
        }
    }

    private function updateSeo(): void
    {
        $mode = (string) ($_POST['sef_url_mode'] ?? 'id');
        if (in_array($mode, ['id', 'slug', 'random'], true)) {
            $this->setSetting('sef_url_mode', $mode, self::GROUP_SEO);
            $this->setSetting('sef_topic_url_mode', $mode, self::GROUP_SEO);
        }
        $keys = [
            'seo_site_name', 'seo_description', 'seo_keywords',
            'og_title', 'og_description', 'og_image', 'og_type',
        ];
        foreach ($keys as $key) {
            $v = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
            if ($key === 'og_type' && $v === '') {
                $v = 'website';
            }
            $this->setSetting($key, $v, self::GROUP_SEO);
        }
        $schemaJson = $this->buildSchemaJsonFromForm();
        $this->setSetting('schema_json', $schemaJson, self::GROUP_SEO);

        $this->setSetting('robots_txt_content', trim((string) ($_POST['robots_txt_content'] ?? '')), self::GROUP_SEO);
        $this->setSetting('custom_css', trim((string) ($_POST['custom_css'] ?? '')), self::GROUP_SEO);
        $this->setSetting('custom_js', trim((string) ($_POST['custom_js'] ?? '')), self::GROUP_SEO);
    }

    /** Mevcut schema_json'dan form için değerleri çıkarır. */
    private function parseSchemaJsonForForm(string $schemaJson): array
    {
        $baseUrl = rtrim((string) core_config('app.url', ''), '/');
        $defaults = [
            'schema_enabled' => false,
            'schema_type' => 'WebSite',
            'schema_name' => $this->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB'),
            'schema_description' => $this->getSetting('seo_description', ''),
            'schema_url' => $baseUrl,
            'schema_search_include' => true,
            'schema_search_url' => $baseUrl ? $baseUrl . '/search?q={search_term_string}' : '',
            'schema_publisher_name' => '',
            'schema_logo_url' => $this->getSetting('forum_logo_url', ''),
        ];
        if ($schemaJson === '') {
            return $defaults;
        }
        $data = @json_decode($schemaJson, true);
        if (!is_array($data)) {
            return $defaults;
        }
        $out = $defaults;
        $out['schema_enabled'] = true;
        $out['schema_name'] = $data['name'] ?? $defaults['schema_name'];
        $out['schema_description'] = $data['description'] ?? $defaults['schema_description'];
        $out['schema_url'] = $data['url'] ?? $defaults['schema_url'];
        $type = $data['@type'] ?? 'WebSite';
        $out['schema_type'] = $type === 'Organization' ? 'Organization' : 'WebSite';
        if (isset($data['potentialAction']['target']['urlTemplate'])) {
            $out['schema_search_include'] = true;
            $out['schema_search_url'] = $data['potentialAction']['target']['urlTemplate'];
        } else {
            $out['schema_search_include'] = false;
            $out['schema_search_url'] = $defaults['schema_search_url'];
        }
        if (isset($data['publisher']['name'])) {
            $out['schema_publisher_name'] = $data['publisher']['name'];
        }
        if (isset($data['publisher']['logo']['url'])) {
            $out['schema_logo_url'] = $data['publisher']['logo']['url'];
        } elseif (isset($data['logo']['url'])) {
            $out['schema_logo_url'] = $data['logo']['url'];
        }
        return $out;
    }

    /** Formdan gelen schema_* alanlarına göre JSON-LD üretir. */
    private function buildSchemaJsonFromForm(): string
    {
        $enabled = isset($_POST['schema_enabled']) && $_POST['schema_enabled'] === '1';
        if (!$enabled) {
            return '';
        }
        $type = (string) ($_POST['schema_type'] ?? 'WebSite');
        $name = trim((string) ($_POST['schema_name'] ?? ''));
        $description = trim((string) ($_POST['schema_description'] ?? ''));
        $url = trim((string) ($_POST['schema_url'] ?? ''));
        $searchInclude = isset($_POST['schema_search_include']) && $_POST['schema_search_include'] === '1';
        $searchUrl = trim((string) ($_POST['schema_search_url'] ?? ''));
        $publisherName = trim((string) ($_POST['schema_publisher_name'] ?? ''));
        $logoUrl = trim((string) ($_POST['schema_logo_url'] ?? ''));

        if ($name === '') {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type === 'Organization' ? 'Organization' : 'WebSite',
            'name' => $name,
        ];
        if ($description !== '') {
            $schema['description'] = $description;
        }
        if ($url !== '') {
            $schema['url'] = $url;
        }

        if ($type !== 'Organization' && $searchInclude && $searchUrl !== '' && str_contains($searchUrl, '{search_term_string}')) {
            $schema['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $searchUrl,
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        $publisher = null;
        if ($publisherName !== '') {
            $publisher = ['@type' => 'Organization', 'name' => $publisherName];
            if ($logoUrl !== '') {
                $publisher['logo'] = ['@type' => 'ImageObject', 'url' => $logoUrl];
            }
        } elseif ($logoUrl !== '') {
            $publisher = ['@type' => 'Organization', 'name' => $name, 'logo' => ['@type' => 'ImageObject', 'url' => $logoUrl]];
        }
        if ($publisher !== null && $type === 'WebSite') {
            $schema['publisher'] = $publisher;
        }
        if ($type === 'Organization' && $logoUrl !== '') {
            $schema['logo'] = ['@type' => 'ImageObject', 'url' => $logoUrl];
        }

        $encoded = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded !== false ? $encoded : '';
    }

    private function updateMenu(): void
    {
        $menuJson = trim((string) ($_POST['menu_json'] ?? ''));
        if ($menuJson !== '') {
            $decoded = json_decode($menuJson, true);
            if (is_array($decoded)) {
                $sanitized = [];
                foreach ($decoded as $i => $item) {
                    $label = trim((string) ($item['label'] ?? ''));
                    if ($label === '') {
                        continue;
                    }
                    $url = trim((string) ($item['url'] ?? ''));
                    if ($url !== '' && preg_match('#^javascript:|data:#i', $url)) {
                        $url = '#';
                    }
                    if ($url !== '' && strpos($url, '//') === 0 && strpos($url, 'http') !== 0) {
                        $url = '#';
                    }
                    $visible = isset($item['visible']) && (string) $item['visible'] === '1' ? '1' : '0';
                    $parentId = (int) ($item['parent_id'] ?? 0);
                    $sanitized[] = [
                        'id' => (int) ($item['id'] ?? $i),
                        'label' => $label,
                        'url' => $url,
                        'visible' => $visible,
                        'order' => count($sanitized),
                        'parent_id' => $parentId,
                    ];
                }
                $this->setSetting('top_menu_items', json_encode($sanitized, JSON_UNESCAPED_UNICODE), self::GROUP_SYSTEM);
                return;
            }
        }
        $labels = isset($_POST['menu_label']) && is_array($_POST['menu_label']) ? $_POST['menu_label'] : [];
        $urls = isset($_POST['menu_url']) && is_array($_POST['menu_url']) ? $_POST['menu_url'] : [];
        $visibles = isset($_POST['menu_visible']) && is_array($_POST['menu_visible']) ? $_POST['menu_visible'] : [];
        $depths = isset($_POST['menu_depth']) && is_array($_POST['menu_depth']) ? $_POST['menu_depth'] : [];
        $sanitized = [];
        $lastTopId = 0;
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $url = trim((string) ($urls[$i] ?? ''));
            if ($url !== '' && preg_match('#^javascript:|data:#i', $url)) {
                $url = '#';
            }
            if ($url !== '' && strpos($url, '//') === 0 && strpos($url, 'http') !== 0) {
                $url = '#';
            }
            $visible = isset($visibles[$i]) && (string) $visibles[$i] === '1' ? '1' : '0';
            $depth = isset($depths[$i]) && (string) $depths[$i] === '1' ? 1 : 0;
            $id = count($sanitized);
            $parentId = $depth === 1 ? $lastTopId : 0;
            if ($depth === 0) {
                $lastTopId = $id;
            }
            $sanitized[] = ['id' => $id, 'label' => $label, 'url' => $url, 'visible' => $visible, 'order' => $id, 'parent_id' => $parentId];
        }
        $sanitized = array_values(array_filter($sanitized, static fn ($a) => ($a['label'] ?? '') !== ''));
        foreach ($sanitized as $o => $item) {
            $sanitized[$o]['order'] = $o;
        }
        $this->setSetting('top_menu_items', json_encode($sanitized, JSON_UNESCAPED_UNICODE), self::GROUP_SYSTEM);
    }

    /** Eski depth formatını parent_id formatına çevirir; zaten parent_id varsa id atar. */
    private function normalizeMenuItemsForAdmin(array $items): array
    {
        if (empty($items)) {
            return [];
        }
        $hasParentId = isset($items[0]['parent_id']);
        if ($hasParentId) {
            $out = [];
            foreach ($items as $i => $it) {
                $out[] = [
                    'id' => (int) ($it['id'] ?? $i),
                    'label' => $it['label'] ?? '',
                    'url' => $it['url'] ?? '',
                    'visible' => (string) ($it['visible'] ?? '1'),
                    'order' => (int) ($it['order'] ?? $i),
                    'parent_id' => (int) ($it['parent_id'] ?? 0),
                ];
            }
            return $out;
        }
        $out = [];
        $lastTopId = 0;
        foreach ($items as $i => $it) {
            $depth = (int) ($it['depth'] ?? 0);
            $parentId = $depth === 1 ? $lastTopId : 0;
            if ($depth === 0) {
                $lastTopId = $i;
            }
            $out[] = [
                'id' => $i,
                'label' => $it['label'] ?? '',
                'url' => $it['url'] ?? '',
                'visible' => (string) ($it['visible'] ?? '1'),
                'order' => $i,
                'parent_id' => $parentId,
            ];
        }
        return $out;
    }

    /** Flat menü listesini (parent_id ile) admin görünümü için ağaç yapısına çevirir. Döngüye karşı korumalı. */
    private function buildMenuTreeForAdmin(array $items): array
    {
        $byParent = [];
        foreach ($items as $it) {
            $pid = (int) ($it['parent_id'] ?? 0);
            if (!isset($byParent[$pid])) {
                $byParent[$pid] = [];
            }
            $byParent[$pid][] = $it;
        }
        foreach ($byParent as $pid => $list) {
            usort($byParent[$pid], static fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        }
        $visited = [];
        $build = static function (int $parentId, array &$visited) use (&$build, &$byParent): array {
            $out = [];
            foreach ($byParent[$parentId] ?? [] as $it) {
                $id = (int) ($it['id'] ?? 0);
                if (isset($visited[$id])) {
                    continue;
                }
                $visited[$id] = true;
                $out[] = [
                    'id' => $id,
                    'label' => $it['label'] ?? '',
                    'url' => $it['url'] ?? '',
                    'visible' => (string) ($it['visible'] ?? '1'),
                    'order' => (int) ($it['order'] ?? 0),
                    'parent_id' => (int) ($it['parent_id'] ?? 0),
                    'children' => $build($id, $visited),
                ];
            }
            return $out;
        };
        return $build(0, $visited);
    }

    private function updateFooterMenu(): void
    {
        $labels = isset($_POST['footer_label']) && is_array($_POST['footer_label']) ? $_POST['footer_label'] : [];
        $urls = isset($_POST['footer_url']) && is_array($_POST['footer_url']) ? $_POST['footer_url'] : [];
        $visibles = isset($_POST['footer_visible']) && is_array($_POST['footer_visible']) ? $_POST['footer_visible'] : [];
        $sanitized = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $url = trim((string) ($urls[$i] ?? ''));
            if ($url !== '' && preg_match('#^javascript:|data:#i', $url)) {
                $url = '#';
            }
            if ($url !== '' && strpos($url, '//') === 0 && strpos($url, 'http') !== 0) {
                $url = '#';
            }
            $visible = isset($visibles[$i]) && (string) $visibles[$i] === '1' ? '1' : '0';
            $sanitized[] = ['label' => $label, 'url' => $url, 'visible' => $visible, 'order' => $i];
        }
        $sanitized = array_values(array_filter($sanitized, static fn ($a) => $a['label'] !== ''));
        foreach ($sanitized as $o => $item) {
            $sanitized[$o]['order'] = $o;
        }
        $this->setSetting('footer_menu_items', json_encode($sanitized, JSON_UNESCAPED_UNICODE), self::GROUP_SYSTEM);
    }

    private function updateFooterQuickLinks(): void
    {
        $labels = isset($_POST['quick_label']) && is_array($_POST['quick_label']) ? $_POST['quick_label'] : [];
        $urls = isset($_POST['quick_url']) && is_array($_POST['quick_url']) ? $_POST['quick_url'] : [];
        $icons = isset($_POST['quick_icon']) && is_array($_POST['quick_icon']) ? $_POST['quick_icon'] : [];
        $visibles = isset($_POST['quick_visible']) && is_array($_POST['quick_visible']) ? $_POST['quick_visible'] : [];
        $sanitized = [];
        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $url = trim((string) ($urls[$i] ?? ''));
            if ($url !== '' && preg_match('#^javascript:|data:#i', $url)) {
                $url = '#';
            }
            if ($url !== '' && strpos($url, '//') === 0 && strpos($url, 'http') !== 0) {
                $url = '#';
            }
            $icon = trim((string) ($icons[$i] ?? ''));
            $visible = isset($visibles[$i]) && (string) $visibles[$i] === '1' ? '1' : '0';
            $sanitized[] = ['label' => $label, 'url' => $url, 'icon' => $icon, 'visible' => $visible, 'order' => $i];
        }
        $sanitized = array_values(array_filter($sanitized, static fn ($a) => $a['label'] !== ''));
        foreach ($sanitized as $o => $item) {
            $sanitized[$o]['order'] = $o;
        }
        $this->setSetting('footer_quick_links', json_encode($sanitized, JSON_UNESCAPED_UNICODE), self::GROUP_SYSTEM);
    }

    private function updateMail(): void
    {
        $keys = [
            'mail_driver' => self::GROUP_MAIL,
            'smtp_host' => self::GROUP_MAIL,
            'smtp_port' => self::GROUP_MAIL,
            'smtp_username' => self::GROUP_MAIL,
            'smtp_password' => self::GROUP_MAIL,
            'smtp_encryption' => self::GROUP_MAIL,
            'mail_from_address' => self::GROUP_MAIL,
            'mail_from_name' => self::GROUP_MAIL,
        ];
        foreach ($keys as $key => $group) {
            $v = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
            if ($key === 'smtp_password' && $v === '') {
                continue;
            }
            if ($key === 'smtp_password' && $v !== '') {
                $this->setSetting($key, $v, $group);
            } elseif ($key !== 'smtp_password') {
                $this->setSetting($key, $v, $group);
            }
        }
        if (isset($_POST['smtp_password']) && trim((string) $_POST['smtp_password']) !== '') {
            $this->setSetting('smtp_password', trim((string) $_POST['smtp_password']), self::GROUP_MAIL);
        }
    }

    private function defaultMenuItems(): array
    {
        return [
            ['id' => 0, 'label' => lang('admin.sys_settings.menu_default_forum'), 'url' => '', 'visible' => '1', 'order' => 0, 'parent_id' => 0],
            ['id' => 1, 'label' => lang('admin.sys_settings.menu_default_members'), 'url' => 'members', 'visible' => '1', 'order' => 1, 'parent_id' => 0],
            ['id' => 2, 'label' => lang('admin.sys_settings.menu_default_timeline'), 'url' => 'timeline', 'visible' => '1', 'order' => 2, 'parent_id' => 0],
            ['id' => 3, 'label' => lang('admin.sys_settings.menu_default_rules'), 'url' => 'page/kurallar', 'visible' => '1', 'order' => 3, 'parent_id' => 0],
            ['id' => 4, 'label' => lang('admin.sys_settings.menu_default_contact'), 'url' => 'iletisim', 'visible' => '1', 'order' => 4, 'parent_id' => 0],
            ['id' => 5, 'label' => lang('admin.sys_settings.menu_default_latest'), 'url' => '#latest', 'visible' => '1', 'order' => 5, 'parent_id' => 0],
        ];
    }

    private function defaultFooterMenuItems(): array
    {
        return [
            ['label' => lang('admin.sys_settings.footer_menu_rules'), 'url' => 'page/kurallar', 'visible' => '1', 'order' => 0],
            ['label' => lang('admin.sys_settings.footer_menu_privacy'), 'url' => 'page/gizlilik', 'visible' => '1', 'order' => 1],
            ['label' => lang('admin.sys_settings.footer_menu_sitemap'), 'url' => 'sitemap.xml', 'visible' => '1', 'order' => 2],
            ['label' => lang('admin.sys_settings.footer_menu_back_top'), 'url' => '#', 'visible' => '1', 'order' => 3],
        ];
    }

    private function defaultFooterQuickLinks(): array
    {
        return [
            ['label' => lang('admin.sys_settings.quick_links_home'), 'url' => '', 'icon' => 'fa-solid fa-house', 'visible' => '1', 'order' => 0],
            ['label' => lang('admin.sys_settings.quick_links_forum'), 'url' => 'forum', 'icon' => 'fa-solid fa-comments', 'visible' => '1', 'order' => 1],
            ['label' => lang('admin.sys_settings.quick_links_members'), 'url' => 'members', 'icon' => 'fa-solid fa-users', 'visible' => '1', 'order' => 2],
            ['label' => lang('admin.sys_settings.quick_links_profile'), 'url' => 'profile/edit', 'icon' => 'fa-solid fa-user-gear', 'visible' => '1', 'order' => 3],
        ];
    }
}
