<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
use App\Services\LayoutDataService;
use App\Services\StorageService;

abstract class BaseController
{
    protected \Forecor\Core\Application $app;

    /** @var LayoutDataService|null */
    private $layoutService;

    public function __construct(\Forecor\Core\Application $app)
    {
        $this->app = $app;
    }

    protected function layoutService(): LayoutDataService
    {
        if ($this->layoutService === null) {
            $this->layoutService = new LayoutDataService($this->app);
        }
        return $this->layoutService;
    }

    /** Birleşik depolama (yerel veya S3/R2); avatar, ek ve editör resimleri için. basePath = proje kökü. */
    protected function storage(): StorageService
    {
        return new StorageService($this->app->getBasePath());
    }

    /** Render a theme view (Twig template) with data */
    protected function view(string $view, array $data = []): string
    {
        try {
            return $this->app->twig('frontend')->render($view . '.html.twig', $data);
        } catch (\Throwable $e) {
            return '<!-- View not found: ' . htmlspecialchars($view) . ' -->';
        }
    }

    /** Render full page with header + content + footer (Twig). */
    protected function layout(string $contentView, array $data = [], bool $withSidebar = false): string
    {
        $data['pageTitle'] = $data['pageTitle'] ?? ($this->getSetting('seo_site_name', '') ?: core_config('app.name'));
        $data['locale'] = $data['locale'] ?? $this->locale();
        $data['user'] = $data['user'] ?? $this->app->auth()->user();
        $user = $data['user'];
        $rid = $user ? (int)($user->role_id ?? 0) : 0;
        $data['isStaff'] = ($rid === 1 || $rid === 2);
        $svc = $this->layoutService();
        if ($data['isStaff']) {
            $data['staffPendingReports'] = $svc->countPendingReports();
            $data['staffPendingApprovals'] = $svc->countPendingApprovals();
        } else {
            $data['staffPendingReports'] = 0;
            $data['staffPendingApprovals'] = 0;
        }
        $messagesEnabled = $this->getSetting('messages_enabled', '1') === '1';
        $notificationsEnabled = $this->getSetting('notifications_enabled', '1') === '1';
        if ($user && ($messagesEnabled || $notificationsEnabled)) {
            $blocked = $svc->getBlockedUserIds((int)$user->id);
            $data['unreadNotifications'] = $notificationsEnabled ? $svc->countUnreadNotifications((int)$user->id, $blocked) : 0;
            $data['unreadMessages'] = $messagesEnabled ? $svc->countUnreadConversations((int)$user->id, $blocked) : 0;
        } else {
            $data['unreadNotifications'] = 0;
            $data['unreadMessages'] = 0;
        }
        $data['messagesEnabled'] = $messagesEnabled;
        $data['notificationsEnabled'] = $notificationsEnabled;
        $data['notificationToastEnabled'] = $this->getSetting('notification_toast_enabled', '1') === '1';
        $data['forum_logo_url'] = $this->getSetting('forum_logo_url', '');
        $data['forum_favicon_url'] = $this->getSetting('forum_favicon_url', '');
        $data['lightbox_all_images_enabled'] = $this->getSetting('lightbox_all_images_enabled', '1') === '1';
        $data['show_site_title_next_to_logo'] = $this->getSetting('show_site_title_next_to_logo', '1') === '1';
        $data['social_facebook'] = $this->getSetting('social_facebook', '');
        $data['social_twitter'] = $this->getSetting('social_twitter', '');
        $data['social_instagram'] = $this->getSetting('social_instagram', '');
        $data['social_youtube'] = $this->getSetting('social_youtube', '');
        $data['social_linkedin'] = $this->getSetting('social_linkedin', '');
        $data['social_show_header'] = $this->getSetting('social_show_header', '1') === '1';
        $data['social_show_footer'] = $this->getSetting('social_show_footer', '1') === '1';
        $data['social_show_sidebar'] = $this->getSetting('social_show_sidebar', '0') === '1';
        $data['site_name'] = $this->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB');
        $data['seo_description'] = $this->getSetting('seo_description', '');
        $data['seo_keywords'] = $this->getSetting('seo_keywords', '');
        $data['og_title'] = $this->getSetting('og_title', '') ?: $data['site_name'];
        $data['og_description'] = $this->getSetting('og_description', '') ?: $data['seo_description'];
        $data['og_image'] = $this->getSetting('og_image', '');
        $data['og_type'] = $this->getSetting('og_type', 'website') ?: 'website';
        $data['schema_json'] = $this->getSetting('schema_json', '');
        $data['canonical_url'] = rtrim(core_config('app.url', ''), '/') . ($_SERVER['REQUEST_URI'] ?? '/');
        $menuJson = $this->getSetting('top_menu_items', '');
        $menuItems = $menuJson !== '' ? (array) json_decode($menuJson, true) : [];
        usort($menuItems, static fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        $menuItems = array_values(array_filter($menuItems, static fn ($a) => ((string) ($a['visible'] ?? '1')) === '1'));
        $tree = $this->buildMenuTree($menuItems);
        $membersListEnabled = $this->getSetting('members_list_enabled', '1') === '1';
        if (!$membersListEnabled) {
            $membersUrl = core_url('members');
            $tree = array_values(array_filter($tree, static fn ($t) => ($t['href'] ?? '') !== $membersUrl));
        }
        $data['top_menu_items'] = $tree;
        $footerMenuJson = $this->getSetting('footer_menu_items', '');
        $footerMenuRaw = $footerMenuJson !== '' ? (array) json_decode($footerMenuJson, true) : [];
        usort($footerMenuRaw, static fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        $footerMenuItems = [];
        foreach ($footerMenuRaw as $item) {
            if (((string) ($item['visible'] ?? '1')) !== '1') {
                continue;
            }
            $label = $item['label'] ?? '';
            $url = $item['url'] ?? '';
            $href = $this->menuItemHref($url, true);
            $footerMenuItems[] = ['label' => $this->translateMenuItemLabel($url, $label), 'url' => $url, 'href' => $href];
        }
        $data['footer_menu_items'] = $footerMenuItems;
        $footerQuickJson = $this->getSetting('footer_quick_links', '');
        $footerQuickRaw = $footerQuickJson !== '' ? (array) json_decode($footerQuickJson, true) : [];
        usort($footerQuickRaw, static fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        $footerQuickLinksItems = [];
        foreach ($footerQuickRaw as $item) {
            if (((string) ($item['visible'] ?? '1')) !== '1') {
                continue;
            }
            $label = $item['label'] ?? '';
            $url = $item['url'] ?? '';
            $href = $this->menuItemHref($url, false);
            $footerQuickLinksItems[] = ['label' => $this->translateMenuItemLabel($url, $label), 'url' => $url, 'href' => $href, 'icon' => trim((string) ($item['icon'] ?? ''))];
        }
        $data['footer_quick_links_items'] = $footerQuickLinksItems;
        $data['members_list_enabled'] = $membersListEnabled;
        $data['documentation_enabled'] = $this->getSetting('documentation_enabled', '0') === '1';
        $idelistEnabled = $this->app->cache()->get('idelist.enabled');
        if ($idelistEnabled === null) {
            try {
                $idelistEnabled = \App\Modules\Idelist\Models\IdelistSetting::getValue('module_enabled', '1') === '1';
                $this->app->cache()->set('idelist.enabled', $idelistEnabled, 60);
            } catch (\Throwable $e) {
                $idelistEnabled = true;
            }
        }
        $data['idelist_enabled'] = (bool) $idelistEnabled;
        $data['custom_css'] = $this->getSetting('custom_css', '');
        $postbitCustomCss = trim((string) $this->getSetting('postbit_simple_custom_css', ''));
        if ($postbitCustomCss !== '') {
            $data['custom_css'] .= "\n/* Postbit custom css */\n" . $postbitCustomCss;
        }
        $data['custom_js'] = $this->getSetting('custom_js', '');
        $data['theme_primary_color'] = $this->getSetting('theme_primary_color', '#206bc4');
        $data['theme_header_bg_color'] = $this->getSetting('theme_header_bg_color', '#1f2937');
        $data['theme_header_text_color'] = $this->getSetting('theme_header_text_color', '#ffffff');
        $data['theme_menu_bg_color'] = $this->getSetting('theme_menu_bg_color', '#172029');
        $data['theme_menu_text_color'] = $this->getSetting('theme_menu_text_color', '#ffffff');
        $data['theme_footer_bg_color'] = $this->getSetting('theme_footer_bg_color', '#111827');
        $data['theme_footer_text_color'] = $this->getSetting('theme_footer_text_color', '#d1d5db');
        $data['theme_card_bg_color'] = $this->getSetting('theme_card_bg_color', '#ffffff');
        $data['theme_card_border_color'] = $this->getSetting('theme_card_border_color', '#e5e7eb');
        $data['theme_button_radius'] = $this->getSetting('theme_button_radius', '10');
        $simpleFieldsRaw = (string) $this->getSetting('postbit_simple_enabled_fields', '[]');
        $simpleFields = json_decode($simpleFieldsRaw, true);
        if (!is_array($simpleFields)) {
            $simpleFields = [];
        }
        $simpleFields = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $simpleFields), static fn ($v) => $v !== ''));
        $simpleCustomFieldKeysRaw = (string) $this->getSetting('postbit_simple_custom_field_keys', '[]');
        $simpleCustomFieldKeys = json_decode($simpleCustomFieldKeysRaw, true);
        if (!is_array($simpleCustomFieldKeys)) {
            $simpleCustomFieldKeys = [];
        }
        $simpleCustomFieldKeys = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $simpleCustomFieldKeys), static fn ($v) => $v !== ''));
        $data['postbitSimple'] = [
            'enabledFields' => $simpleFields,
            'accentColor' => trim((string) $this->getSetting('postbit_simple_accent_color', '#1a252f')),
            'customFieldKeys' => $simpleCustomFieldKeys,
            'layout' => in_array((string) $this->getSetting('postbit_simple_layout', 'left'), ['left', 'top'], true) ? (string) $this->getSetting('postbit_simple_layout', 'left') : 'left',
            'topStatsPosition' => in_array((string) $this->getSetting('postbit_top_stats_position', 'right'), ['left', 'right'], true) ? (string) $this->getSetting('postbit_top_stats_position', 'right') : 'right',
            'topLeftBlocks' => is_array(json_decode((string) $this->getSetting('postbit_top_left_blocks', '["profile"]'), true)) ? json_decode((string) $this->getSetting('postbit_top_left_blocks', '["profile"]'), true) : ['profile'],
            'topRightBlocks' => is_array(json_decode((string) $this->getSetting('postbit_top_right_blocks', '["stats","meta"]'), true)) ? json_decode((string) $this->getSetting('postbit_top_right_blocks', '["stats","meta"]'), true) : ['stats', 'meta'],
            'topLeftItems' => is_array(json_decode((string) $this->getSetting('postbit_top_left_items', '["profile"]'), true)) ? json_decode((string) $this->getSetting('postbit_top_left_items', '["profile"]'), true) : ['profile'],
            'topRightItems' => is_array(json_decode((string) $this->getSetting('postbit_top_right_items', '["post_count","like_count","reputation","reward_points","warning_points","joined_date","location"]'), true)) ? json_decode((string) $this->getSetting('postbit_top_right_items', '["post_count","like_count","reputation","reward_points","warning_points","joined_date","location"]'), true) : ['post_count', 'like_count', 'reputation', 'reward_points', 'warning_points', 'joined_date', 'location'],
        ];
        $data['postbitAdvanced'] = [
            'enabled' => $this->getSetting('postbit_advanced_enabled', '0') === '1',
            'template' => (string) $this->getSetting('postbit_advanced_template', ''),
        ];
        $homeType = $this->getSetting('home_page_type', $this->getSetting('portal_enabled', '0') === '1' ? 'portal' : 'forum');
        $data['portal_enabled'] = in_array($homeType, ['portal', 'articles'], true);
        $data['ads'] = $svc->loadAdsByPosition();
        $data['announcements'] = $svc->loadActiveAnnouncements($user ? (int) $user->id : 0);
        if (!isset($data['stats'])) {
            $data['stats'] = $svc->getStats();
        }
        if (!isset($data['onlineStats'])) {
            $data['onlineStats'] = $svc->getOnlineStats();
        }
        if (!isset($data['online'])) {
            $data['online'] = $data['onlineStats']->members ?? [];
        }
        $data['hero_title'] = $this->getSetting('hero_title', '') ?: core__('stats.forums');
        $data['hero_description'] = $this->getSetting('hero_description', '') ?: core__('stats.forums_desc');

        $data['hero_f1_icon'] = $this->getSetting('hero_f1_icon', 'fa-solid fa-gem');
        $data['hero_f1_title'] = $this->getSetting('hero_f1_title', 'Pırlanta Kalite');
        $data['hero_f1_desc'] = $this->getSetting('hero_f1_desc', 'Modern mimari, güvenli altyapı ve sınırsız özelleştirme ile forum yazılımının zirvesi.');

        $data['hero_f2_icon'] = $this->getSetting('hero_f2_icon', 'fa-solid fa-bolt');
        $data['hero_f2_title'] = $this->getSetting('hero_f2_title', 'Hızlı & Akıcı');
        $data['hero_f2_desc'] = $this->getSetting('hero_f2_desc', 'Laravel ve Symfony gücüyle optimize edilmiş, her ölçekte kusursuz performans.');

        $data['hero_f3_icon'] = $this->getSetting('hero_f3_icon', 'fa-solid fa-palette');
        $data['hero_f3_title'] = $this->getSetting('hero_f3_title', 'Özelleştirilebilir');
        $data['hero_f3_desc'] = $this->getSetting('hero_f3_desc', 'Tema, eklenti ve modül desteği ile hayalinizdeki topluluğu kurun.');

        $data['hero_f4_icon'] = $this->getSetting('hero_f4_icon', 'fa-solid fa-shield-halved');
        $data['hero_f4_title'] = $this->getSetting('hero_f4_title', 'Güvenli & Kararlı');
        $data['hero_f4_desc'] = $this->getSetting('hero_f4_desc', 'Güncel güvenlik standartları ve düzenli güncellemelerle güvende kalın.');

        if ($contentView === 'portal') {
            $data['hero_visible'] = false;
            $data['portalPage'] = $this->getPortalPageSettings($data['site_name'] ?? '');
        } else {
            $data['hero_visible'] = $data['hero_visible'] ?? ($this->getSetting('hero_visible', '1') !== '0');
        }
        $data['jquery_enabled'] = $this->getSetting('jquery_enabled', '1') === '1';
        $data['jquery_cdn_url'] = trim((string) $this->getSetting('jquery_cdn_url', ''));
        $data['ajax_enabled'] = $this->getSetting('ajax_enabled', '1') === '1';
        $data['editor_type'] = $this->normalizeEditorType($this->getSetting('post_editor', 'toast_ui'));
        $data['pwa_enabled'] = $this->getSetting('pwa_enabled', '0') === '1';
        $data['pwa_theme_color'] = $this->getSetting('pwa_theme_color', '#1a252f');
        $data['pwa_background_color'] = $this->getSetting('pwa_background_color', '#ececec');
        $pwaIcon192 = trim((string) $this->getSetting('pwa_icon_192', ''));
        $pwaIcon512 = trim((string) $this->getSetting('pwa_icon_512', ''));
        $data['pwa_icon_192'] = $pwaIcon192;
        $data['pwa_icon_512'] = $pwaIcon512;
        $data['pwa_icons_maskable'] = $this->getSetting('pwa_icons_maskable', '0') === '1';
        $data['pwa_manifest_url'] = core_url('manifest.json');
        $pwaBaseUrl = rtrim((string) core_config('app.url', ''), '/');
        if ($pwaBaseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $pwaBaseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . (function_exists('app_url_base_path') ? app_url_base_path() : '');
        }
        $pwaBaseUrl = rtrim($pwaBaseUrl, '/');
        $data['pwa_icon_192_url'] = $pwaIcon192 !== '' ? (strpos($pwaIcon192, 'http') === 0 ? $pwaIcon192 : $pwaBaseUrl . (strpos($pwaIcon192, '/') === 0 ? '' : '/') . $pwaIcon192) : '';
        $data['pwa_icon_512_url'] = $pwaIcon512 !== '' ? (strpos($pwaIcon512, 'http') === 0 ? $pwaIcon512 : $pwaBaseUrl . (strpos($pwaIcon512, '/') === 0 ? '' : '/') . $pwaIcon512) : '';
        $data['withSidebar'] = $withSidebar;
        if ($withSidebar && !isset($data['topTags'])) {
            $data['topTags'] = $this->getTopTags();
        }
        $data['sidebar_blocks'] = $this->app->hooks()->doAction('layout.sidebar_blocks', $this->app);
        $data['header_extra'] = $this->app->hooks()->doAction('layout.header_extra', $this->app);
        $data['footer_extra'] = $this->app->hooks()->doAction('layout.footer_extra', $this->app);
        if ($user) {
            $data['modal_forums'] = $this->getModalForums();
        } else {
            $data['modal_forums'] = [];
        }
        $data = $this->app->hooks()->applyFilters('layout.view_data', $data, $contentView);
        return $this->app->twig('frontend')->render($contentView . '.html.twig', $data);
    }

    /** Category/forum list (id, name, slug) for "Create new" modal. Makale kategorileri hariç (sadece normal konu açılacak forumlar). */
    protected function getModalForums(): array
    {
        $categories = \App\Models\Category::forForumList()->orderBy('sort_order')->orderBy('id')->get();
        $out = [];
        foreach ($categories as $cat) {
            $forums = Forum::where('category_id', (int) $cat->id)->whereNull('parent_id')
                ->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'slug' => $f->slug])
                ->all();
            if (!empty($forums)) {
                $out[] = ['name' => $cat->name ?? '', 'forums' => $forums];
            }
        }
        return $out;
    }

    /** Kullanıcıya gösterilecek veritabanı hata mesajı; app.debug açıksa gerçek hata eklenir. */
    protected function dbErrorMessage(\Throwable $e): string
    {
        $msg = core__('common.db_error');
        if (core_config('app.debug', false)) {
            $msg .= ' [' . $e->getMessage() . ']';
        }
        return $msg;
    }

    /** Redirect (send Location header) */
    protected function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    /** JSON yanıt (AJAX/API). ApiResponse üzerinden tek noktadan. */
    protected function json(array $data, int $statusCode = 200): void
    {
        \App\Services\ApiResponse::send($data, $statusCode);
    }

    /** İstek AJAX mı (fetch/XHR) */
    protected function isAjaxRequest(): bool
    {
        $with = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        return $with === 'xmlhttprequest' || stripos($accept, 'application/json') !== false;
    }

    /** Get current locale from translator (session / cookie / user preference). */
    protected function locale(): string
    {
        return $this->app->translator()->getLocale();
    }

    /** Known menu URLs → lang keys (footer + header items stored in Turkish in DB). */
    protected function menuLabelLangKeyForUrl(string $url): ?string
    {
        $normalized = strtolower(trim($url));
        $normalized = trim($normalized, '/');
        if ($normalized === '' || $normalized === '#') {
            return $normalized === '#' ? 'footer.back_top' : 'common.home';
        }
        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return null;
        }
        $map = [
            'forum' => 'common.forum',
            'members' => 'common.members',
            'member' => 'common.members',
            'profile/edit' => 'footer.profile_settings',
            'profile' => 'common.profile',
            'articles' => 'article.articles',
            'documentation' => 'documentation.title',
            'idelist' => 'idelist.nav_label',
            'iletisim' => 'common.contact',
            'contact' => 'common.contact',
            'online' => 'online.page_title',
            'login' => 'common.login',
            'register' => 'common.register',
            'page/kurallar' => 'footer.rules',
            'page/gizlilik' => 'footer.privacy',
            'page/rules' => 'footer.rules',
            'page/privacy' => 'footer.privacy',
            'sitemap.xml' => 'footer.sitemap',
        ];
        return $map[$normalized] ?? null;
    }

    protected function translateMenuItemLabel(string $url, string $storedLabel): string
    {
        $key = $this->menuLabelLangKeyForUrl($url);
        if ($key !== null) {
            $translated = lang($key);
            if ($translated !== $key) {
                return $translated;
            }
        }
        return $storedLabel;
    }


    protected function disableBrowserCacheForHtml(): void
    {
        if (headers_sent()) {
            return;
        }
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    protected function getSetting(string $key, $default = null)
    {
        static $cache = [];
        if (!isset($cache[$key])) {
            $v = \App\Models\Setting::getValue($key, $default);
            $cache[$key] = $v;
        }
        return $cache[$key] ?? $default;
    }

    protected function setSetting(string $key, string $value, string $group = 'forum'): void
    {
        \App\Models\Setting::setValue($key, $value, $group);
    }

    private function normalizeEditorType(string $value): string
    {
        return in_array($value, ['toast_ui', 'tinymce', 'ckeditor'], true) ? $value : 'toast_ui';
    }

    /**
     * Portal sayfası metinleri ve buton URL'leri (admin ayarları + dil varsayılanları).
     *
     * @return array{
     *   badge: string,
     *   title: string,
     *   description: string,
     *   btn_primary_label: string,
     *   btn_primary_url: string,
     *   btn_secondary_label: string,
     *   btn_secondary_url: string,
     *   features_intro: string,
     *   cta_title: string,
     *   cta_text: string,
     *   cta_btn_label: string,
     *   cta_btn_url: string,
     *   card_view_all: string,
     *   block_empty: string
     * }
     */
    protected function getPortalPageSettings(string $siteName = ''): array
    {
        $siteName = trim($siteName) !== '' ? trim($siteName) : (trim((string) $this->getSetting('seo_site_name', '')) ?: (string) core_config('app.name', 'MegaforBB'));

        $title = trim((string) $this->getSetting('portal_hero_title', ''));
        if ($title === '') {
            $title = trim((string) $this->getSetting('hero_title', ''));
        }
        if ($title === '') {
            $title = core__('portal.default_hero_title');
        }

        $description = trim((string) $this->getSetting('portal_hero_description', ''));
        if ($description === '') {
            $description = trim((string) $this->getSetting('hero_description', ''));
        }
        if ($description === '') {
            $description = core__('portal.default_hero_desc');
        }

        return [
            'badge' => $this->resolvePortalCopy('portal_hero_badge', 'portal.hero_badge', $siteName),
            'title' => $title,
            'description' => $description,
            'btn_primary_label' => $this->resolvePortalCopy('portal_hero_btn_primary_label', 'portal.btn_join', $siteName, false),
            'btn_primary_url' => $this->resolvePortalUrl((string) $this->getSetting('portal_hero_btn_primary_url', ''), 'register'),
            'btn_secondary_label' => $this->resolvePortalCopy('portal_hero_btn_secondary_label', 'portal.btn_explore', $siteName, false),
            'btn_secondary_url' => $this->resolvePortalUrl((string) $this->getSetting('portal_hero_btn_secondary_url', ''), 'forum'),
            'features_intro' => $this->resolvePortalCopy('portal_features_intro', 'portal.features_intro', $siteName),
            'cta_title' => $this->resolvePortalCopy('portal_cta_title', 'portal.cta_title', $siteName, false),
            'cta_text' => $this->resolvePortalCopy('portal_cta_text', 'portal.cta_text', $siteName, false),
            'cta_btn_label' => $this->resolvePortalCopy('portal_cta_btn_label', 'portal.cta_btn', $siteName, false),
            'cta_btn_url' => $this->resolvePortalUrl((string) $this->getSetting('portal_cta_btn_url', ''), 'forum'),
            'card_view_all' => $this->resolvePortalCopy('portal_card_view_all_label', 'portal.card_view_all', $siteName, false),
            'block_empty' => $this->resolvePortalCopy('portal_block_empty_text', 'portal.block_empty', $siteName, false),
        ];
    }

    protected function resolvePortalCopy(string $settingKey, string $langKey, string $siteName, bool $replaceSiteName = true): string
    {
        $value = trim((string) $this->getSetting($settingKey, ''));
        if ($value === '') {
            $value = $replaceSiteName
                ? core__($langKey, ['site_name' => $siteName])
                : core__($langKey);
        } elseif ($replaceSiteName) {
            $value = str_replace(['{site_name}', ':site_name'], $siteName, $value);
        }
        return $value;
    }

    protected function resolvePortalUrl(string $url, string $defaultRoute): string
    {
        $url = trim($url);
        if ($url === '') {
            return core_url($defaultRoute);
        }
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }
        if (strpos($url, '#') === 0) {
            return core_url('') . $url;
        }
        return core_url(ltrim($url, '/'));
    }

    /**
     * Menü öğesi için href: dış link (http/https) olduğunda site adresi eklenmez.
     * @param string $url Menü URL (boş, #anchor, relative path veya tam URL)
     * @param bool $footer true ise boş/# için '#', değilse ana sayfa
     */
    protected function menuItemHref(string $url, bool $footer): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return $footer ? '#' : core_url('');
        }
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            return $url;
        }
        if (strpos($url, '#') === 0) {
            return $footer ? $url : (core_url('') . $url);
        }
        return core_url($url);
    }

    /**
     * Flat menü listesini ağaç yapısına çevirir. Hem parent_id hem depth (0/1) formatını destekler.
     * @param array<int, array{label?: string, url?: string, depth?: int, parent_id?: int, order?: int}> $menuItems
     * @return array<int, array{label: string, href: string, children: array}>
     */
    protected function buildMenuTree(array $menuItems): array
    {
        $items = array_values($menuItems);
        if (empty($items)) {
            return [];
        }
        $hasParentId = isset($items[0]['parent_id']);
        if ($hasParentId) {
            $byId = [];
            foreach ($items as $i => $item) {
                $id = $item['id'] ?? $i;
                $itemUrl = $item['url'] ?? '';
                $byId[$id] = [
                    'id' => $id,
                    'label' => $this->translateMenuItemLabel($itemUrl, $item['label'] ?? ''),
                    'url' => $itemUrl,
                    'href' => $this->menuItemHref($itemUrl, false),
                    'parent_id' => (int) ($item['parent_id'] ?? 0),
                    'order' => (int) ($item['order'] ?? $i),
                    'children' => [],
                ];
            }
            foreach ($byId as $id => $node) {
                $pid = $node['parent_id'];
                if ($pid === 0) {
                    continue;
                }
                if (isset($byId[$pid])) {
                    $byId[$pid]['children'][] = ['label' => $this->translateMenuItemLabel($node['url'], $node['label']), 'href' => $node['href'], 'url' => $node['url'], 'order' => $node['order']];
                }
            }
            foreach ($byId as $id => $node) {
                usort($byId[$id]['children'], static fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
            }
            $roots = array_filter($byId, static fn ($n) => $n['parent_id'] === 0);
            uasort($roots, static fn ($a, $b) => $a['order'] <=> $b['order']);
            $tree = [];
            foreach ($roots as $r) {
                $tree[] = [
                    'label' => $this->translateMenuItemLabel($r['url'], $r['label']),
                    'href' => $r['href'],
                    'url' => $r['url'],
                    'children' => $r['children'],
                ];
            }
            return $tree;
        }
        $tree = [];
        $currentTop = null;
        foreach ($items as $item) {
            $depth = (int) ($item['depth'] ?? 0);
            $label = $item['label'] ?? '';
            $url = $item['url'] ?? '';
            $href = $this->menuItemHref($url, false);
            $translatedLabel = $this->translateMenuItemLabel($url, $label);
            if ($depth === 0 || $currentTop === null) {
                $currentTop = ['label' => $translatedLabel, 'url' => $url, 'href' => $href, 'children' => []];
                $tree[] = $currentTop;
            } else {
                $currentTop['children'][] = ['label' => $translatedLabel, 'url' => $url, 'href' => $href];
            }
        }
        return $tree;
    }

    /** Engelleyen + engellenen taraftaki kullanıcı id listesi (blocked olanlar). */
    protected function getBlockedUserIds(int $userId): array
    {
        return $this->layoutService()->getBlockedUserIds($userId);
    }

    protected function countUnreadNotifications(int $userId, array $blockedUserIds): int
    {
        return $this->layoutService()->countUnreadNotifications($userId, $blockedUserIds);
    }

    protected function countUnreadConversations(int $userId, array $blockedUserIds): int
    {
        return $this->layoutService()->countUnreadConversations($userId, $blockedUserIds);
    }

    /** Forum istatistikleri. */
    protected function getStats(): object
    {
        return $this->layoutService()->getStats();
    }

    /** Çevrimiçi kullanıcılar (son 15 dk aktivite; Eloquent). */
    protected function getOnlineUsers(): array
    {
        $threshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        return User::where('last_activity_at', '>=', $threshold)
            ->orderBy('username')
            ->limit(50)
            ->get(['id', 'username'])
            ->all();
    }

    /**
     * Forum/liste görünümlerinde listelenecek konu tipleri. Eklentiler filter ile ekleyebilir (örn. auction).
     * @return array<int, string>
     */
    protected function getTopicListTypes(): array
    {
        return $this->app->hooks()->applyFilters('topic_list_types', ['topic', 'question']);
    }

    /** Son 10 konu (sidebar vb.; Eloquent). */
    protected function getRecentTopics(): array
    {
        $user = $this->app->auth()->user();
        $userId = $user ? (int) $user->id : null;
        $isStaff = $user && $user->role && $user->role->is_staff;
        return Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->with(['user:id,username', 'lastPostUser:id,username,avatar_path'])
            ->whereIn('type', $this->getTopicListTypes())
            ->whereNull('deleted_at')
            ->orderByRaw('COALESCE(last_post_at, created_at) DESC')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'title', 'slug', 'reply_count', 'view_count', 'user_id', 'last_post_user_id', 'last_post_at', 'created_at'])
            ->map(function ($t) {
                return (object)[
                    'id' => $t->id,
                    'title' => $t->title,
                    'slug' => $t->slug,
                    'reply_count' => $t->reply_count,
                    'view_count' => $t->view_count,
                    'username' => $t->user->username ?? null,
                    'last_post_at' => $t->last_post_at,
                    'last_post_username' => $t->lastPostUser->username ?? null,
                    'last_post_avatar_path' => $t->lastPostUser->avatar_path ?? null,
                ];
            })
            ->all();
    }

    /** Popüler 5 konu (Eloquent). */
    protected function getPopularTopics(): array
    {
        $user = $this->app->auth()->user();
        $userId = $user ? (int) $user->id : null;
        $isStaff = $user && $user->role && $user->role->is_staff;
        return Topic::visibleToUserWithPrivacy($userId, $isStaff)
            ->whereIn('type', $this->getTopicListTypes())
            ->whereNull('deleted_at')
            ->orderByDesc('view_count')
            ->orderByDesc('reply_count')
            ->limit(5)
            ->get(['id', 'title', 'slug'])
            ->all();
    }

    /** En çok kullanılan 10 etiket (tag cloud sidebar için). use_count'a göre font boyutu 85%–170% aralığında. */
    protected function getTopTags(): array
    {
        try {
            $tags = \App\Models\Tag::orderByDesc('use_count')
                ->limit(50)
                ->get(['id', 'name', 'slug', 'use_count'])
                ->all();
            if (empty($tags)) {
                return [];
            }
            $counts = array_map(fn ($t) => (int)$t->use_count, $tags);
            $min = min($counts);
            $max = max($counts);
            $range = $max > $min ? ($max - $min) : 1;
            $out = [];
            foreach ($tags as $t) {
                $size = 0.85 + ((int)$t->use_count - $min) / $range * 0.85;
                $out[] = (object)['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug, 'use_count' => (int)$t->use_count, 'size' => round($size * 100)];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
