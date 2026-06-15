<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig tabanlı tema motoru.
 * Aktif tema Setting::getCached('active_frontend_theme'|'active_admin_theme') ile okunur.
 * Fallback: templates/frontend/default veya templates/admin/default.
 */
class TemplateEngine
{
    private string $basePath;
    private string $context;
    private ?Environment $twig = null;

    /** @param 'frontend'|'admin' $context */
    public function __construct(string $basePath, string $context = 'frontend')
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->context = $context === 'admin' ? 'admin' : 'frontend';
    }

    public function getTwig(): Environment
    {
        if ($this->twig !== null) {
            return $this->twig;
        }

        $settingKey = $this->context === 'admin' ? 'active_admin_theme' : 'active_frontend_theme';
        $activeTheme = $this->getActiveTheme($settingKey);
        $templatesDir = $this->basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . $this->context;
        $themePath = $templatesDir . DIRECTORY_SEPARATOR . $activeTheme . DIRECTORY_SEPARATOR . 'views';
        $fallbackPath = $templatesDir . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'views';

        $paths = [];
        if (is_dir($themePath)) {
            $paths[] = $themePath;
        }
        if (is_dir($fallbackPath) && $fallbackPath !== $themePath) {
            $paths[] = $fallbackPath;
        }
        if ($paths === []) {
            $paths[] = $fallbackPath;
        }

        $loader = new FilesystemLoader($paths);
        if (class_exists(\App\Services\PluginLoader::class)) {
            foreach (\App\Services\PluginLoader::getPluginViewPaths($this->basePath) as $namespace => $pluginPath) {
                $loader->addPath($pluginPath, $namespace);
            }
        }
        $cachePath = $this->basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'views';
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0755, true);
        }
        $debug = function_exists('core_config') && core_config('app.debug', false);
        $this->twig = new Environment($loader, [
            'cache' => is_dir($cachePath) ? $cachePath : false,
            'auto_reload' => $debug, // Sadece debug modunda şablon değişikliği kontrolü (yerelde performans için false)
            'strict_variables' => false,
        ]);

        $this->twig->addGlobal('php_version', PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION);
        $this->registerFunctions();
        $this->registerFilters();
        return $this->twig;
    }

    private function registerFilters(): void
    {
        $this->twig->addFilter(new TwigFilter('schema_ld_json', function (?string $s): string {
            if ($s === null || $s === '') {
                return '';
            }
            $d = @json_decode($s);
            return $d !== null ? json_encode($d, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) : '';
        }));
        $this->twig->addFilter(new TwigFilter('rtrim', function (string $s, string $ch = ' '): string {
            return rtrim($s, $ch);
        }));
        $this->twig->addFilter(new TwigFilter('url_encode', function (?string $s): string {
            return $s !== null ? rawurlencode($s) : '';
        }));
        $this->twig->addFilter(new TwigFilter('json_decode_array', function (?string $s): array {
            if ($s === null || $s === '') {
                return [];
            }
            $d = @json_decode($s, true);
            return is_array($d) ? $d : [];
        }));
        $this->twig->addFilter(new TwigFilter('time_ago', function (?string $dateStr): string {
            if ($dateStr === null || $dateStr === '') {
                return '';
            }
            $diff = time() - (int) @strtotime($dateStr);
            if ($diff < 0) {
                return \lang('common.time_ago_just_now');
            }
            if ($diff < 60) {
                return \lang('common.time_ago_just_now');
            }
            if ($diff < 3600) {
                return floor($diff / 60) . \lang('common.time_ago_minutes');
            }
            if ($diff < 86400) {
                return floor($diff / 3600) . \lang('common.time_ago_hours');
            }
            if ($diff < 604800) {
                return floor($diff / 86400) . \lang('common.time_ago_days');
            }
            return date('d M', (int) @strtotime($dateStr));
        }));
        $this->twig->addFilter(new TwigFilter('clamp', function ($value, $min, $max) {
            $v = (int) $value;
            $min = (int) $min;
            $max = (int) $max;
            if ($v < $min) {
                return $min;
            }
            if ($v > $max) {
                return $max;
            }
            return $v;
        }));
        $this->twig->addFilter(new TwigFilter('floatval', function ($value) {
            return (float) $value;
        }));
        $this->twig->addFilter(new TwigFilter('filter_visible_columns', function (array $colOrder, $colVisible): array {
            $out = [];
            foreach ($colOrder as $c) {
                $v = \is_array($colVisible) ? ($colVisible[$c] ?? false) : (isset($colVisible->$c) ? $colVisible->$c : false);
                if ($v) {
                    $out[] = $c;
                }
            }
            return $out;
        }));
        $this->twig->addFilter(new TwigFilter('smileys', function (?string $s): string {
            if ($s === null || $s === '') {
                return '';
            }
            if (!\App\Helpers\SmileyHelper::isEnabled()) {
                return $s;
            }
            return \App\Helpers\SmileyHelper::parse($s);
        }));
    }

    private function getActiveTheme(string $settingKey): string
    {
        try {
            $raw = trim((string) Setting::getCached($settingKey, 'default'));
        } catch (\Throwable $e) {
            return 'default';
        }
        if ($raw === '') {
            return 'default';
        }
        $templatesDir = $this->basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . $this->context;
        $direct = $templatesDir . DIRECTORY_SEPARATOR . $raw;
        if (is_dir($direct)) {
            $rp = realpath($direct);
            return $rp !== false ? basename($rp) : $raw;
        }
        if (is_dir($templatesDir)) {
            foreach (new \DirectoryIterator($templatesDir) as $entry) {
                if (!$entry->isDir() || $entry->isDot()) {
                    continue;
                }
                if (strcasecmp($entry->getFilename(), $raw) === 0) {
                    return $entry->getFilename();
                }
            }
        }
        return 'default';
    }

    private function registerFunctions(): void
    {
        $fns = [
            'core_url' => 'core_url',
            'full_site_url' => 'full_site_url',
            'base_url' => 'base_url',
            'topic_url_path' => 'topic_url_path',
            'topic_url_path_by_id' => 'topic_url_path_by_id',
            'topic_url' => 'topic_url',
            'post_url_path' => 'post_url_path',
            'post_url_path_by_id' => 'post_url_path_by_id',
            'conversation_url_path' => 'conversation_url_path',
            'conversation_url_path_by_id' => 'conversation_url_path_by_id',
            'notification_url_path' => 'notification_url_path',
            'attachment_url_path' => 'attachment_url_path',
            'member_url_path' => 'member_url_path',
            'article_url_path_by_id' => 'article_url_path_by_id',
            'asset_url' => 'asset_url',
            'theme_asset_url' => 'theme_asset_url',
            'banned_avatar_url' => 'banned_avatar_url',
            'core__' => 'core__',
            'admin__' => 'admin__',
            'lang' => 'lang',
            'core_e' => 'core_e',
            'core_csrf_token' => 'core_csrf_token',
            'core_csrf_field' => 'core_csrf_field',
            'core_redirect_url_safe' => 'core_redirect_url_safe',
            'avatar_display_name' => 'avatar_display_name',
            'core_sanitize_html' => 'core_sanitize_html',
            'core_quote_bb_to_html' => 'core_quote_bb_to_html',
            'core_display_post_html' => 'core_display_post_html',
            'core_body_to_html' => 'core_body_to_html',
            'core_process_mentions' => 'core_process_mentions',
            'core_process_post_refs' => 'core_process_post_refs',
            'core_event_dispatch' => 'core_event_dispatch',
            'core_config' => 'core_config',
            'env' => 'env',
        ];

        foreach ($fns as $name => $callable) {
            if (!function_exists($callable)) {
                continue;
            }
            $options = $name === 'core_e' ? ['is_safe' => ['html']] : [];
            $this->twig->addFunction(new TwigFunction($name, $callable, $options));
        }

        $this->twig->addFunction(new TwigFunction('hook', function (string $name, array $payload = []) {
            return function_exists('core_event_dispatch') ? core_event_dispatch($name, $payload) : null;
        }));

        $this->twig->addFunction(new TwigFunction('flash', function (string $key): string {
            try {
                $bag = \Forecor\Core\SessionManager::get()->getFlashBag();
                $messages = $bag->get($key);
                $msg = is_array($messages) ? (string)($messages[0] ?? '') : (string)$messages;
                return $msg !== '' ? $msg : '';
            } catch (\Throwable $e) {
                return $key === 'error' ? ('Flash okuma hatası: ' . $e->getMessage()) : '';
            }
        }));

        $this->twig->addFunction(new TwigFunction('attachment_icon', function (string $mimeType, string $originalName): string {
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (strpos($mimeType, 'image/') === 0 || in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                return 'fa-solid fa-image';
            }
            if ($ext === 'pdf' || $mimeType === 'application/pdf') {
                return 'fa-solid fa-file-pdf';
            }
            if (in_array($ext, ['doc','docx'], true)) {
                return 'fa-solid fa-file-word';
            }
            if (in_array($ext, ['xls','xlsx'], true)) {
                return 'fa-solid fa-file-excel';
            }
            if (in_array($ext, ['zip','rar','7z'], true)) {
                return 'fa-solid fa-file-zipper';
            }
            return 'fa-solid fa-file';
        }));

        $this->twig->addFunction(new TwigFunction('attachment_format_size', function (int $bytes): string {
            if ($bytes < 1024) {
                return $bytes . ' B';
            }
            if ($bytes < 1024 * 1024) {
                return round($bytes / 1024, 1) . ' KB';
            }
            return round($bytes / (1024 * 1024), 1) . ' MB';
        }));

        $this->twig->addFunction(new TwigFunction('render_twig_string', function (string $template, array $context = []): string {
            $template = trim($template);
            if ($template === '') {
                return '';
            }
            try {
                return $this->twig->createTemplate($template)->render($context);
            } catch (\Throwable $e) {
                if (function_exists('core_config') && core_config('app.debug', false)) {
                    return '<!-- postbit template error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . ' -->';
                }
                return '';
            }
        }, ['is_safe' => ['html']]));
    }

    /** Şablonu render et ve HTML string döndür. */
    public function render(string $template, array $data = []): string
    {
        $name = strpos($template, '.') !== false ? $template : $template . '.html.twig';
        $html = $this->getTwig()->render($name, $data);
        if ($this->context === 'frontend') {
            $html = $this->injectFrontendCoreAssets($html);
        }
        return $html;
    }

    /** Şablonu doğrudan çıktıya bas. */
    public function display(string $template, array $data = []): void
    {
        echo $this->render($template, $data);
    }

    private function injectFrontendCoreAssets(string $html): string
    {
        if ($html === '' || stripos($html, '<html') === false) {
            return $html;
        }

        $cssUrl = function_exists('core_url') ? core_url('css/main.css') : '/css/main.css';
        $jsUrl = function_exists('core_url') ? core_url('js/main.js') : '/js/main.js';
        $version = (string) (function_exists('core_config') ? core_config('app.version', '1') : '1');
        $versionParam = '?v=' . rawurlencode($version);

        if (stripos($html, '/css/main.css') === false) {
            $cssTag = '<link rel="stylesheet" href="' . $cssUrl . $versionParam . '">';
            if (stripos($html, '</head>') !== false) {
                $html = preg_replace('/<\/head>/i', "  {$cssTag}\n</head>", $html, 1) ?? $html;
            }
        }

        if (stripos($html, '/js/main.js') === false) {
            $jsTag = '<script src="' . $jsUrl . $versionParam . '"></script>';
            if (stripos($html, '</body>') !== false) {
                $html = preg_replace('/<\/body>/i', "  {$jsTag}\n</body>", $html, 1) ?? $html;
            }
        }

        return $html;
    }
}
