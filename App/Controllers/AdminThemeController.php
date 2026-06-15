<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Setting;
use Forecor\Core\Application;

/**
 * Admin: Theme management. Scans templates/frontend/ and templates/admin/ for
 * folders containing theme.json; "Activate" sets active_frontend_theme
 * veya active_admin_theme ayarını günceller.
 */
class AdminThemeController extends AdminController
{
    /** @var array<string, string> */
    private const QUICK_THEME_DEFAULTS = [
        'theme_primary_color' => '#206bc4',
        'theme_header_bg_color' => '#1f2937',
        'theme_header_text_color' => '#ffffff',
        'theme_menu_bg_color' => '#172029',
        'theme_menu_text_color' => '#ffffff',
        'theme_footer_bg_color' => '#111827',
        'theme_footer_text_color' => '#d1d5db',
        'theme_card_bg_color' => '#ffffff',
        'theme_card_border_color' => '#e5e7eb',
        'theme_button_radius' => '10',
    ];

    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /** Tema listesi (frontend + admin) */
    public function index(): string
    {
        $basePath = $this->app->getBasePath();
        $templatesDir = $basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Template';

        $frontendThemes = $this->scanThemes($templatesDir . DIRECTORY_SEPARATOR . 'frontend');
        $adminThemes = $this->scanThemes($templatesDir . DIRECTORY_SEPARATOR . 'admin');

        $activeFrontend = $this->matchStoredThemeSlug(
            (string) Setting::getCached('active_frontend_theme', 'default'),
            $frontendThemes
        );
        $activeAdmin = $this->matchStoredThemeSlug(
            (string) Setting::getCached('active_admin_theme', 'default'),
            $adminThemes
        );

        return $this->view('themes/index', [
            'pageTitle' => lang('admin.themes.title'),
            'frontendThemes' => $frontendThemes,
            'adminThemes' => $adminThemes,
            'activeFrontendTheme' => $activeFrontend,
            'activeAdminTheme' => $activeAdmin,
        ]);
    }

    /**
     * Tema klasörünü çözümler.
     * URL eşlemesinden önce url_path_sanitize() slug'ı küçük harfe çevirdiği için (örn. RetroDSG → retrdsg)
     * burada büyük/küçük harfe duyarsız eşleme ve gerçek klasör adını kullanırız.
     */
    private function resolveThemePath(string $basePath, string $subdir, string $slug): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            return null;
        }
        $base = $basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . $subdir;
        $realBase = realpath($base);
        if ($realBase === false || !is_dir($realBase)) {
            return null;
        }
        $direct = $base . DIRECTORY_SEPARATOR . $slug;
        $resolved = is_dir($direct) ? realpath($direct) : false;
        if ($resolved === false || strpos($resolved, $realBase) !== 0) {
            $resolved = null;
            foreach (new \DirectoryIterator($realBase) as $entry) {
                if (!$entry->isDir() || $entry->isDot()) {
                    continue;
                }
                if (strcasecmp($entry->getFilename(), $slug) !== 0) {
                    continue;
                }
                $rp = realpath($entry->getPathname());
                if ($rp !== false && strpos($rp, $realBase) === 0) {
                    $resolved = $rp;
                    break;
                }
            }
        }
        if ($resolved === null || !is_dir($resolved)) {
            return null;
        }
        return $resolved;
    }

    /** Aktif frontend temasını ayarla */
    public function activateFrontend(string $slug): string
    {
        $basePath = $this->app->getBasePath();
        $themePath = $this->resolveThemePath($basePath, 'frontend', $slug);
        if ($themePath === null || !is_file($themePath . DIRECTORY_SEPARATOR . 'theme.json')) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes'));
            return '';
        }
        $canonical = basename($themePath);
        Setting::setValue('active_frontend_theme', $canonical, 'forum');
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes'));
        return '';
    }

    /** Aktif admin temasını ayarla */
    public function activateAdmin(string $slug): string
    {
        $basePath = $this->app->getBasePath();
        $themePath = $this->resolveThemePath($basePath, 'admin', $slug);
        if ($themePath === null || !is_file($themePath . DIRECTORY_SEPARATOR . 'theme.json')) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes'));
            return '';
        }
        $canonical = basename($themePath);
        Setting::setValue('active_admin_theme', $canonical, 'forum');
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes'));
        return '';
    }

    /**
     * Verilen dizin altındaki her klasörü tema olarak tara; theme.json varsa meta oku.
     * @return array<int, array{slug: string, name: string, version: string, author: string, description: string}>
     */
    private function scanThemes(string $dir): array
    {
        $list = [];
        if (!is_dir($dir)) {
            return $list;
        }
        $it = new \DirectoryIterator($dir);
        foreach ($it as $entry) {
            if (!$entry->isDir() || $entry->isDot()) {
                continue;
            }
            $slug = $entry->getFilename();
            $metaPath = $entry->getPathname() . DIRECTORY_SEPARATOR . 'theme.json';
            if (!is_file($metaPath)) {
                continue;
            }
            $meta = $this->readThemeJson($metaPath);
            $list[] = [
                'slug' => $slug,
                'name' => $meta['name'] ?? $slug,
                'version' => $meta['version'] ?? '1.0.0',
                'author' => $meta['author'] ?? '',
                'description' => $meta['description'] ?? '',
            ];
        }
        return $list;
    }

    /**
     * Eski kayıtlar (sanitize sonrası küçük harf slug) ile disk üstündeki klasör adını hizalar.
     *
     * @param array<int, array{slug: string, ...}> $themes
     */
    private function matchStoredThemeSlug(string $stored, array $themes): string
    {
        foreach ($themes as $t) {
            $slug = (string) ($t['slug'] ?? '');
            if ($slug !== '' && strcasecmp($slug, $stored) === 0) {
                return $slug;
            }
        }
        return $stored;
    }

    private function readThemeJson(string $path): array
    {
        $json = @file_get_contents($path);
        if ($json === false) {
            return [];
        }
        $data = @json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /** Theme File Editor */
    public function editor(string $slug): string
    {
        $basePath = $this->app->getBasePath();
        $themePath = $this->resolveThemePath($basePath, 'frontend', $slug);
        if ($themePath === null) {
            $this->app->session()->getFlashBag()->add('theme_error', lang('admin.themes.theme_not_found'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes'));
            return '';
        }

        $files = $this->getThemeFiles($themePath);
        $fileParam = $_GET['file'] ?? '';
        $currentFile = '';
        $fileContent = '';

        if (!empty($fileParam) && in_array($fileParam, $files, true)) {
            $currentFile = $fileParam;
            $fullPath = $themePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileParam);
            $fullPathReal = realpath($fullPath);
            $themePathReal = realpath($themePath);
            if ($fullPathReal !== false && $themePathReal !== false && strpos($fullPathReal, $themePathReal) === 0 && is_file($fullPathReal)) {
                $fileContent = file_get_contents($fullPathReal) ?: '';
            }
        }

        return $this->view('themes/editor', [
            'pageTitle' => lang('admin.themes.editor_title', ['slug' => $slug]),
            'slug' => $slug,
            'files' => $files,
            'currentFile' => $currentFile,
            'fileContent' => $fileContent,
            'flashOk' => $this->app->session()->getFlashBag()->get('editor_ok')[0] ?? null,
            'flashError' => $this->app->session()->getFlashBag()->get('editor_error')[0] ?? null,
        ]);
    }

    public function editorSave(string $slug): void
    {
        if (!core_csrf_valid('admin_theme_editor', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('editor_error', lang('admin.performance.csrf_failed'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes/editor/' . rawurlencode($slug)));
            return;
        }

        $basePath = $this->app->getBasePath();
        $themePath = $this->resolveThemePath($basePath, 'frontend', $slug);
        if ($themePath === null) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes'));
            return;
        }

        $fileParam = (string)($_POST['file'] ?? '');
        $content = (string)($_POST['content'] ?? '');

        $files = $this->getThemeFiles($themePath);
        if (!in_array($fileParam, $files, true)) {
            $this->app->session()->getFlashBag()->add('editor_error', lang('admin.themes.editor_invalid_file'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes/editor/' . rawurlencode($slug)));
            return;
        }

        $fullPath = $themePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileParam);
        $fullPathReal = realpath($fullPath);
        $themePathReal = realpath($themePath);
        if ($fullPathReal === false || $themePathReal === false || strpos($fullPathReal, $themePathReal) !== 0 || !is_file($fullPathReal)) {
            $this->app->session()->getFlashBag()->add('editor_error', lang('admin.themes.editor_invalid_file'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes/editor/' . rawurlencode($slug)));
            return;
        }
        if (is_writable($fullPathReal)) {
            file_put_contents($fullPathReal, $content);
            $this->app->session()->getFlashBag()->add('editor_ok', lang('admin.themes.editor_saved'));
        } else {
            $this->app->session()->getFlashBag()->add('editor_error', lang('admin.themes.editor_not_writable'));
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes/editor/' . rawurlencode($slug) . '?file=' . urlencode($fileParam)));
    }

    /** AJAX: Search file names inside current theme */
    public function editorSearchFiles(string $slug): string
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        if ($query === '') {
            $this->json(['files' => []]);
            return '';
        }

        $basePath = $this->app->getBasePath();
        $themePath = $this->resolveThemePath($basePath, 'frontend', $slug);
        if ($themePath === null) {
            $this->json(['files' => [], 'error' => lang('admin.themes.theme_not_found')], 404);
            return '';
        }

        $queryLower = mb_strtolower($query);
        $files = $this->getThemeFiles($themePath);
        $matches = [];
        foreach ($files as $file) {
            if (mb_strpos(mb_strtolower($file), $queryLower) === false) {
                continue;
            }
            $matches[] = [
                'file' => $file,
                'url' => core_url(env('ADMIN_PATH', 'admin') . '/themes/editor/' . rawurlencode($slug) . '?file=' . rawurlencode($file)),
            ];
            if (count($matches) >= 100) {
                break;
            }
        }

        $this->json(['files' => $matches]);
        return '';
    }

    /** AJAX: Search content inside selected file only */
    public function editorSearchContent(string $slug): string
    {
        $query = trim((string) ($_GET['q'] ?? ''));
        $fileParam = (string) ($_GET['file'] ?? '');
        if ($query === '') {
            $this->json(['results' => []]);
            return '';
        }

        $basePath = $this->app->getBasePath();
        $themePath = $this->resolveThemePath($basePath, 'frontend', $slug);
        if ($themePath === null) {
            $this->json(['results' => [], 'error' => lang('admin.themes.theme_not_found')], 404);
            return '';
        }

        $files = $this->getThemeFiles($themePath);
        if ($fileParam === '' || !in_array($fileParam, $files, true)) {
            $this->json(['results' => [], 'error' => lang('admin.themes.editor_invalid_file')], 400);
            return '';
        }

        $queryLower = mb_strtolower($query);
        $results = [];
        $fullPath = $themePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileParam);
        $fullPathReal = realpath($fullPath);
        $themePathReal = realpath($themePath);
        if ($fullPathReal === false || $themePathReal === false || strpos($fullPathReal, $themePathReal) !== 0 || !is_file($fullPathReal)) {
            $this->json(['results' => [], 'error' => lang('admin.themes.editor_invalid_file')], 400);
            return '';
        }

        $content = file_get_contents($fullPathReal);
        if ($content === false || $content === '') {
            $this->json(['results' => []]);
            return '';
        }

        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $lineMatches = [];
        foreach ($lines as $idx => $line) {
            if (mb_strpos(mb_strtolower($line), $queryLower) === false) {
                continue;
            }
            $lineMatches[] = [
                'line' => $idx + 1,
                'text' => mb_substr(trim($line), 0, 220),
            ];
            if (count($lineMatches) >= 200) {
                break;
            }
        }

        if ($lineMatches !== []) {
            $results[] = [
                'file' => $fileParam,
                'url' => core_url(env('ADMIN_PATH', 'admin') . '/themes/editor/' . rawurlencode($slug) . '?file=' . rawurlencode($fileParam)),
                'matches' => $lineMatches,
            ];
        }

        $this->json(['results' => $results]);
        return '';
    }

    private function getThemeFiles(string $dir): array
    {
        $files = [];
        if (!is_dir($dir)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $ext = strtolower($fileinfo->getExtension());
                if (in_array($ext, ['twig', 'css', 'js', 'json', 'yml', 'yaml', 'php'])) {
                    $relPath = str_replace($dir . DIRECTORY_SEPARATOR, '', $fileinfo->getPathname());
                    $files[] = str_replace('\\', '/', $relPath);
                }
            }
        }
        sort($files);
        return $files;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /** Serve Theme Screenshot securely */
    public function previewImage(string $type, string $slug): void
    {
        if (!in_array($type, ['frontend', 'admin'], true)) {
            http_response_code(404);
            return;
        }
        $basePath = $this->app->getBasePath();
        $themePath = $this->resolveThemePath($basePath, $type, $slug);
        if ($themePath === null) {
            http_response_code(404);
            return;
        }
        $png = $themePath . DIRECTORY_SEPARATOR . 'screenshot.png';
        $jpg = $themePath . DIRECTORY_SEPARATOR . 'screenshot.jpg';

        $file = null;
        if (is_file($png)) {
            $file = $png;
            $mime = 'image/png';
        } elseif (is_file($jpg)) {
            $file = $jpg;
            $mime = 'image/jpeg';
        }

        if ($file) {
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($file));
            header('Cache-Control: max-age=86400, public');
            readfile($file);
            exit;
        }

        // Return a default blank image or 404
        http_response_code(404);
        exit;
    }

    /** Simple Settings Editor for Themes */
    public function simpleSettings(): string
    {
        return $this->view('themes/settings', [
            'pageTitle' => lang('admin.themes.settings_title'),
            'settings' => [
                'seo_site_name' => \App\Models\Setting::getValue('seo_site_name', ''),
                'seo_description' => \App\Models\Setting::getValue('seo_description', ''),
                'forum_logo_url' => \App\Models\Setting::getValue('forum_logo_url', ''),
                'theme_primary_color' => \App\Models\Setting::getValue('theme_primary_color', self::QUICK_THEME_DEFAULTS['theme_primary_color']),
                'theme_header_bg_color' => \App\Models\Setting::getValue('theme_header_bg_color', self::QUICK_THEME_DEFAULTS['theme_header_bg_color']),
                'theme_header_text_color' => \App\Models\Setting::getValue('theme_header_text_color', self::QUICK_THEME_DEFAULTS['theme_header_text_color']),
                'theme_menu_bg_color' => \App\Models\Setting::getValue('theme_menu_bg_color', self::QUICK_THEME_DEFAULTS['theme_menu_bg_color']),
                'theme_menu_text_color' => \App\Models\Setting::getValue('theme_menu_text_color', self::QUICK_THEME_DEFAULTS['theme_menu_text_color']),
                'theme_footer_bg_color' => \App\Models\Setting::getValue('theme_footer_bg_color', self::QUICK_THEME_DEFAULTS['theme_footer_bg_color']),
                'theme_footer_text_color' => \App\Models\Setting::getValue('theme_footer_text_color', self::QUICK_THEME_DEFAULTS['theme_footer_text_color']),
                'theme_card_bg_color' => \App\Models\Setting::getValue('theme_card_bg_color', self::QUICK_THEME_DEFAULTS['theme_card_bg_color']),
                'theme_card_border_color' => \App\Models\Setting::getValue('theme_card_border_color', self::QUICK_THEME_DEFAULTS['theme_card_border_color']),
                'theme_button_radius' => \App\Models\Setting::getValue('theme_button_radius', self::QUICK_THEME_DEFAULTS['theme_button_radius']),
                'custom_css' => \App\Models\Setting::getValue('custom_css', ''),
                'custom_js' => \App\Models\Setting::getValue('custom_js', ''),
            ],
            'flashOk' => $this->app->session()->getFlashBag()->get('settings_ok')[0] ?? null,
            'flashError' => $this->app->session()->getFlashBag()->get('settings_error')[0] ?? null,
        ]);
    }

    public function simpleSettingsSave(): void
    {
        if (!core_csrf_valid('admin_theme_settings', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('settings_error', lang('admin.performance.csrf_failed'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes/settings'));
            return;
        }

        if ((string) ($_POST['action'] ?? '') === 'reset_defaults') {
            foreach (self::QUICK_THEME_DEFAULTS as $key => $value) {
                \App\Models\Setting::setValue($key, $value, 'forum');
            }
            \App\Models\Setting::setValue('custom_css', '', 'forum');
            \App\Models\Setting::setValue('custom_js', '', 'forum');
            $this->app->session()->getFlashBag()->add('settings_ok', lang('admin.themes.settings_reset_defaults'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes/settings'));
            return;
        }

        $fields = [
            'seo_site_name' => 'system',
            'seo_description' => 'system',
            'forum_logo_url' => 'system',
            'theme_primary_color' => 'forum',
            'theme_header_bg_color' => 'forum',
            'theme_header_text_color' => 'forum',
            'theme_menu_bg_color' => 'forum',
            'theme_menu_text_color' => 'forum',
            'theme_footer_bg_color' => 'forum',
            'theme_footer_text_color' => 'forum',
            'theme_card_bg_color' => 'forum',
            'theme_card_border_color' => 'forum',
            'theme_button_radius' => 'forum',
            'custom_css' => 'forum',
            'custom_js' => 'forum',
        ];

        foreach ($fields as $key => $group) {
            $val = (string)($_POST[$key] ?? '');
            if (in_array($key, [
                'theme_primary_color',
                'theme_header_bg_color',
                'theme_header_text_color',
                'theme_menu_bg_color',
                'theme_menu_text_color',
                'theme_footer_bg_color',
                'theme_footer_text_color',
                'theme_card_bg_color',
                'theme_card_border_color',
            ], true)) {
                $val = $this->sanitizeHexColor($val, self::QUICK_THEME_DEFAULTS[$key]);
            } elseif ($key === 'theme_button_radius') {
                $radius = (int) $val;
                if ($radius < 0) {
                    $radius = 0;
                } elseif ($radius > 32) {
                    $radius = 32;
                }
                $val = (string) $radius;
            }
            \App\Models\Setting::setValue($key, $val, $group);
        }

        $this->app->session()->getFlashBag()->add('settings_ok', lang('admin.themes.settings_saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/themes/settings'));
    }

    private function sanitizeHexColor(string $value, string $fallback): string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }
        return $fallback;
    }
}
