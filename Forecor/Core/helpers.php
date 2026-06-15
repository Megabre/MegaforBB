<?php

declare(strict_types=1);

/**
 * Global helper functions.
 */

if (!function_exists('app')) {
    /**
     * Application singleton erişimi.
     * index.php'de set edilir, tüm helper'lar buradan kullanır.
     */
    function app(?\Forecor\Core\Application $instance = null): ?\Forecor\Core\Application
    {
        static $app = null;
        if ($instance !== null) {
            $app = $instance;
        }
        return $app;
    }
}

if (!function_exists('lang')) {
    /**
     * Kısa çeviri helper'ı. Twig ve PHP'de kullanılır.
     * Örn: lang('common.login'), lang('admin.menu.dashboard')
     */
    function lang(string $key, array $replace = []): string
    {
        $appInstance = app();
        if ($appInstance !== null) {
            return $appInstance->translator()->get($key, $replace);
        }
        return $key;
    }
}


if (!function_exists('now')) {
    /**
     * Şu anki zaman (Carbon instance). Laravel now() ile uyumlu.
     * @return \Carbon\Carbon
     */
    function now($timezone = null)
    {
        return $timezone !== null
            ? \Carbon\Carbon::now($timezone)
            : \Carbon\Carbon::now();
    }
}

if (!function_exists('core_event_dispatch')) {
    /**
     * Hook/event fırlat. Event::dispatch($name, $payload) ile aynı.
     * Eklentiler config/events.php ile listener ekleyebilir.
     * @param object|array $payload
     */
    function core_event_dispatch(string $eventName, $payload = []): object
    {
        return \Forecor\Core\Event::dispatch($eventName, $payload);
    }
}

if (!function_exists('app_url_base_path')) {
    /**
     * Tüm URL'lerin ve Router base path'inin tek kaynağı.
     * .htaccess/nginx rewrite kullanılmadan çalışır: istek path'i SCRIPT_NAME ile başlıyorsa
     * (örn. /MegaforBB/index.php/admin) base = SCRIPT_NAME olur; böylece nginx/Apache/IIS fark etmez.
     * Rewrite kullanılıyorsa (path SCRIPT_NAME ile başlamıyorsa) APP_URL path veya dirname(SCRIPT_NAME) kullanılır.
     */
    function app_url_base_path(): string
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $requestPath = isset($_SERVER['REQUEST_URI']) ? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
        if ($requestPath !== '') {
            $requestPath = '/' . trim(str_replace('\\', '/', $requestPath), '/');
        }
        if ($requestPath === '') {
            $requestPath = '/';
        }
        // İstek path'i script adıyla başlıyorsa: index.php URL'de (platform bağımsız mod)
        if ($scriptName !== '' && strpos($requestPath, $scriptName) === 0) {
            $cached = $scriptName;
            return $cached;
        }
        // Rewrite modu: APP_URL path veya dizin
        $url = function_exists('core_config') ? core_config('app.url', '') : '';
        if ($url !== '' && $url !== null) {
            $parsed = parse_url((string) $url, PHP_URL_PATH);
            if ($parsed !== null && $parsed !== '') {
                $base = '/' . trim(str_replace('\\', '/', $parsed), '/');
                $cached = ($base === '/') ? '' : $base;
                return $cached;
            }
        }
        if ($scriptName !== '') {
            $dir = trim(dirname($scriptName), '/');
            if ($dir !== '' && $dir !== '.') {
                $cached = '/' . $dir;
                return $cached;
            }
        }
        $cached = '';
        return $cached;
    }
}

if (!function_exists('url_path_sanitize')) {
    /**
     * URL path'ini platform bağımsız ve güvenli yapar: Türkçe/özel karakterleri slug'a çevirir.
     * Sayısal segmentler (id) ve dosya adları (içinde . olan, örn. style.css) olduğu gibi kalır.
     */
    function url_path_sanitize(string $path): string
    {
        $path = ltrim($path, '/');
        if ($path === '') {
            return '';
        }
        $segments = explode('/', $path);
        $out = [];
        foreach ($segments as $seg) {
            if ($seg === '') {
                continue;
            }
            if (ctype_digit($seg)) {
                $out[] = $seg;
                continue;
            }
            if (strpos($seg, '.') !== false) {
                $out[] = $seg;
                continue;
            }
            if (class_exists(\Forecor\Core\Str::class)) {
                $seg = \Forecor\Core\Str::slug($seg);
            } else {
                $seg = preg_replace('/[^a-z0-9\-]/', '', mb_strtolower($seg, 'UTF-8')) ?: $seg;
            }
            if ($seg !== '') {
                $out[] = $seg;
            }
        }
        return implode('/', $out);
    }
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base = app_url_base_path();
        $path = ltrim($path, '/');
        if ($path === '') {
            return $base !== '' ? $base . '/' : '/';
        }
        return $base !== '' ? $base . '/' . $path : '/' . $path;
    }
}

if (!function_exists('core_url')) {
    /** Tema/controller'da link üretmek için: path segmentleri Türkçe karaktersiz normalize edilir; asset path'leri (.css, .js) korunur; query string korunur. */
    function core_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $query = '';
        if (($pos = strpos($path, '?')) !== false) {
            $query = substr($path, $pos);
            $path = substr($path, 0, $pos);
        }
        if ($path !== '') {
            $path = url_path_sanitize($path);
        }
        return base_url($path) . $query;
    }
}

if (!function_exists('full_site_url')) {
    /** Sosyal paylaşım vb. için tam URL (scheme + host + path). */
    function full_site_url(string $path = ''): string
    {
        $path = ltrim((string) $path, '/');
        $base = function_exists('core_config') ? rtrim((string) core_config('app.url', ''), '/') : '';
        if ($base === '') {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            if (class_exists(\App\Services\SecurityService::class)) {
                $isHttps = \App\Services\SecurityService::isHttpsRequest();
            }
            $scheme = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = $scheme . '://' . $host . (function_exists('app_url_base_path') ? app_url_base_path() : '');
        }
        return $base . ($path !== '' ? '/' . $path : '');
    }
}

/** SEF konu URL: ayara göre (id/slug/random) topic path segment'i. $topic = { id, slug?, url_key? } içeren obje. */
if (!function_exists('topic_url_path')) {
    function topic_url_path($topic): string
    {
        if ($topic === null) {
            return '0';
        }
        static $svc = null;
        if ($svc === null && class_exists(\App\Services\TopicUrlService::class)) {
            $svc = new \App\Services\TopicUrlService();
        }
        return $svc !== null ? $svc->pathForTopic($topic) : (string) ($topic->id ?? 0);
    }
}

/** Topic id ile konu URL path segment'i (topic yüklenmeden). */
if (!function_exists('topic_url_path_by_id')) {
    function topic_url_path_by_id(int $topicId): string
    {
        if ($topicId <= 0) {
            return '0';
        }
        if (class_exists(\App\Services\TopicUrlService::class)) {
            return (new \App\Services\TopicUrlService())->pathForTopicId($topicId);
        }
        return (string) $topicId;
    }
}

/** Konu için tam topic URL (örn. /topic/5 veya /topic/haftalik-guncelleme-5). */
if (!function_exists('topic_url')) {
    function topic_url($topic, string $suffix = ''): string
    {
        $path = topic_url_path($topic);
        $base = core_url('topic/' . $path);
        return $suffix !== '' ? $base . $suffix : $base;
    }
}

/** URL identifier'dan (sayı, slug veya url_key) topic id çözümler. */
if (!function_exists('resolve_topic_id')) {
    function resolve_topic_id(string $identifier): ?int
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }
        if (class_exists(\App\Services\TopicUrlService::class)) {
            return (new \App\Services\TopicUrlService())->resolveTopicId($identifier);
        }
        return ctype_digit($identifier) ? (int) $identifier : null;
    }
}

/** SEF: post path, post id çözümleme, conversation, notification, attachment, member. */
if (!function_exists('sef_service')) {
    function sef_service(): \App\Services\SefUrlService
    {
        static $svc = null;
        if ($svc === null && class_exists(\App\Services\SefUrlService::class)) {
            $svc = new \App\Services\SefUrlService();
        }
        return $svc;
    }
}
if (!function_exists('post_url_path')) {
    function post_url_path($post): string
    {
        return $post === null ? '0' : sef_service()->pathForPost($post);
    }
}
if (!function_exists('post_url_path_by_id')) {
    function post_url_path_by_id(int $postId): string
    {
        return $postId <= 0 ? '0' : sef_service()->pathForPostId($postId);
    }
}
if (!function_exists('resolve_post_id')) {
    function resolve_post_id(string $identifier): ?int
    {
        return class_exists(\App\Services\SefUrlService::class) ? sef_service()->resolvePostId($identifier) : (ctype_digit(trim($identifier)) ? (int) trim($identifier) : null);
    }
}
if (!function_exists('conversation_url_path')) {
    function conversation_url_path($conv): string
    {
        return $conv === null ? '0' : sef_service()->pathForConversation($conv);
    }
}
if (!function_exists('conversation_url_path_by_id')) {
    function conversation_url_path_by_id(int $convId): string
    {
        return $convId <= 0 ? '0' : sef_service()->pathForConversationId($convId);
    }
}
if (!function_exists('resolve_conversation_id')) {
    function resolve_conversation_id(string $identifier): ?int
    {
        return class_exists(\App\Services\SefUrlService::class) ? sef_service()->resolveConversationId($identifier) : (ctype_digit(trim($identifier)) ? (int) trim($identifier) : null);
    }
}
if (!function_exists('notification_url_path')) {
    function notification_url_path($n): string
    {
        return $n === null ? '0' : sef_service()->pathForNotification($n);
    }
}
if (!function_exists('resolve_notification_id')) {
    function resolve_notification_id(string $identifier): ?int
    {
        return class_exists(\App\Services\SefUrlService::class) ? sef_service()->resolveNotificationId($identifier) : (ctype_digit(trim($identifier)) ? (int) trim($identifier) : null);
    }
}
if (!function_exists('attachment_url_path')) {
    function attachment_url_path($att): string
    {
        return $att === null ? '0' : sef_service()->pathForAttachment($att);
    }
}
if (!function_exists('resolve_attachment_id')) {
    function resolve_attachment_id(string $identifier): ?int
    {
        return class_exists(\App\Services\SefUrlService::class) ? sef_service()->resolveAttachmentId($identifier) : (ctype_digit(trim($identifier)) ? (int) trim($identifier) : null);
    }
}
if (!function_exists('member_url_path')) {
    function member_url_path($user): string
    {
        return $user === null ? '0' : sef_service()->pathForMember($user);
    }
}
if (!function_exists('resolve_member')) {
    function resolve_member(string $identifier): ?\App\Models\User
    {
        return class_exists(\App\Services\SefUrlService::class) ? sef_service()->resolveMemberIdentifier($identifier) : null;
    }
}
if (!function_exists('article_url_path_by_id')) {
    function article_url_path_by_id(int $topicId): string
    {
        return $topicId <= 0 ? '0' : (class_exists(\App\Services\SefUrlService::class) ? sef_service()->pathForArticleId($topicId) : (string) $topicId);
    }
}

/** Redirect için güvenli URL: sadece relative veya aynı host. Open redirect önlemi. */
if (!function_exists('core_redirect_url_safe')) {
    function core_redirect_url_safe(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return core_url('');
        }
        $basePath = function_exists('app_url_base_path') ? app_url_base_path() : '';
        $normalizeInternalPath = static function (string $path) use ($basePath): string {
            $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
            if ($basePath !== '' && ($path === $basePath || strpos($path, $basePath . '/') === 0)) {
                $path = substr($path, strlen($basePath));
                if ($path === false) {
                    $path = '/';
                }
                if ($path === '') {
                    $path = '/';
                }
            }
            return $path;
        };
        // Başında / olan path (örn. /topic/19): base path dahil doğru URL üret (alt dizin desteği)
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $path = $normalizeInternalPath($url);
            return core_url(ltrim($path, '/'));
        }
        $appHost = '';
        if (function_exists('core_config')) {
            $appUrl = (string) core_config('app.url', '');
            if ($appUrl !== '') {
                $parsed = parse_url($appUrl);
                $appHost = isset($parsed['host']) ? strtolower($parsed['host']) : '';
            }
        }
        $parsed = parse_url($url);
        if (!isset($parsed['scheme'])) {
            $path = $normalizeInternalPath($url);
            return core_url(ltrim($path, '/'));
        }
        $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';
        if ($appHost !== '' && $host === $appHost) {
            $path = isset($parsed['path']) ? $parsed['path'] : '/';
            $path = $normalizeInternalPath($path);
            $normalizedPath = ($basePath !== '' ? $basePath : '') . $path;
            $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
            $port = '';
            if (isset($parsed['port']) && !in_array((int) $parsed['port'], [80, 443], true)) {
                $port = ':' . $parsed['port'];
            }
            $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
            $fragment = isset($parsed['fragment']) && $parsed['fragment'] !== '' ? '#' . $parsed['fragment'] : '';
            return $scheme . $host . $port . $normalizedPath . $query . $fragment;
        }
        return core_url('');
    }
}

if (!function_exists('theme_asset_url')) {
    /** Tema asset dosyaları (CSS, JS) için URL. templates/frontend/{theme}/assets/ üzerinden theme-assets route ile sunulur. */
    function theme_asset_url(string $path): string
    {
        return base_url('theme-assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('banned_avatar_url')) {
    /** Banlanan kullanıcılar için varsayılan profil resmi URL'i. */
    function banned_avatar_url(): string
    {
        return theme_asset_url('images/banned-avatar.svg');
    }
}

if (!function_exists('asset_url')) {
    /** Yüklenen dosyalar (avatar, kapak vb.) için tam URL. S3/R2 açıksa CDN URL kullanılır. */
    function asset_url(string $path): string
    {
        $path = ltrim($path, '/');
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        if (class_exists(\App\Models\Setting::class) && strpos($path, 'uploads/') === 0) {
            $driver = \App\Models\Setting::getValue('storage_driver', 'local');
            $cdnUrl = '';
            if ($driver === 'aws_s3') {
                $cdnUrl = rtrim((string) \App\Models\Setting::getValue('storage_aws_s3_cdn_url', ''), '/');
            } elseif ($driver === 'r2') {
                $cdnUrl = rtrim((string) \App\Models\Setting::getValue('storage_r2_cdn_url', ''), '/');
            }
            if ($cdnUrl !== '') {
                return $cdnUrl . '/' . $path;
            }
        }
        $base = function_exists('core_config') ? rtrim((string) core_config('app.url', ''), '/') : '';
        if ($base === '') {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            if (class_exists(\App\Services\SecurityService::class)) {
                $isHttps = \App\Services\SecurityService::isHttpsRequest();
            }
            $scheme = $isHttps ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = $scheme . '://' . $host . (function_exists('app_url_base_path') ? app_url_base_path() : '');
        }
        return $base . '/' . $path;
    }
}

if (!function_exists('core__')) {
    /**
     * Translate key (frontend). Backward-compat: çevirileri yeni Translator'dan alır.
     * Eski dot-notation key'ler (common.login) yeni flat yapıyla aynı formatta.
     */
    function core__(string $key, array $replace = [], ?string $locale = null): string
    {
        $appInstance = app();
        if ($appInstance !== null) {
            return $appInstance->translator()->get($key, $replace, $locale);
        }
        // Fallback: eski static yükleme (App bootstrap öncesi)
        static $lang = [];
        $locale = $locale ?? core_config('app.locale', 'tr');
        $ns = 'frontend';
        if (!isset($lang[$ns][$locale])) {
            $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $ns . DIRECTORY_SEPARATOR . $locale . '.php';
            $lang[$ns][$locale] = is_file($path) ? require $path : [];
        }
        $value = $lang[$ns][$locale];
        foreach (explode('.', $key) as $part) {
            $value = is_array($value) && array_key_exists($part, $value) ? $value[$part] : $key;
        }
        $value = is_string($value) ? $value : $key;
        foreach ($replace as $k => $v) {
            $value = str_replace(':' . $k, (string) $v, $value);
        }
        return $value;
    }
}

if (!function_exists('admin__')) {
    /**
     * Translate key (admin). Backward-compat: çevirileri yeni Translator'dan alır.
     * Eski key formatı (common.dashboard) admin.common.dashboard'a dönüştürülür.
     */
    function admin__(string $key, array $replace = [], ?string $locale = null): string
    {
        $appInstance = app();
        if ($appInstance !== null) {
            $adminKey = 'admin.' . $key;
            $result = $appInstance->translator()->get($adminKey, $replace, $locale);
            if ($result !== $adminKey) {
                return $result;
            }
            // Eski key ile de dene (geriye uyumluluk)
            return $appInstance->translator()->get($key, $replace, $locale);
        }
        // Fallback: eski static yükleme
        static $lang = [];
        $locale = $locale ?? core_config('app.locale', 'tr');
        $ns = 'admin';
        if (!isset($lang[$ns][$locale])) {
            $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $ns . DIRECTORY_SEPARATOR . $locale . '.php';
            $lang[$ns][$locale] = is_file($path) ? require $path : [];
        }
        $value = $lang[$ns][$locale];
        foreach (explode('.', $key) as $part) {
            $value = is_array($value) && array_key_exists($part, $value) ? $value[$part] : $key;
        }
        $value = is_string($value) ? $value : $key;
        foreach ($replace as $k => $v) {
            $value = str_replace(':' . $k, (string) $v, $value);
        }
        return $value;
    }
}

if (!function_exists('core_e')) {
    function core_e(?string $s)
    {
        $escaped = $s === null ? '' : htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        if (class_exists(\Twig\Markup::class)) {
            return new \Twig\Markup($escaped, 'UTF-8');
        }

        return $escaped;
    }
}

/** ui-avatars.com için baş harfler: kullanıcı adının ilk 1–2 karakteri (boşsa 'User'). */
if (!function_exists('avatar_display_name')) {
    function avatar_display_name(?string $username): string
    {
        $s = trim($username ?? '');
        if ($s === '') {
            return 'User';
        }
        $len = mb_strlen($s);
        if ($len === 1) {
            return mb_strtoupper($s);
        }
        return mb_strtoupper(mb_substr($s, 0, 1)) . ' ' . mb_strtoupper(mb_substr($s, 1, 1));
    }
}

/** Avatar yokken kullanılacak ui-avatars.com URL’si (baş harfler). */
if (!function_exists('avatar_fallback_url')) {
    function avatar_fallback_url(?string $username, int $size = 64, string $background = '1a252f', string $color = 'ffffff'): string
    {
        $name = avatar_display_name($username);
        return 'https://ui-avatars.com/api/?' . http_build_query([
            'name' => $name,
            'size' => $size,
            'background' => $background,
            'color' => $color,
        ]);
    }
}

if (!function_exists('core_fix_utf8_for_editor')) {
    function core_fix_utf8_for_editor(string $s): string
    {
        return $s;
    }
}


if (!function_exists('core_quote_bb_to_html')) {
    function core_quote_bb_to_html(string $text): string
    {
        return preg_replace_callback('/\[quote([^\]]*)\](.*?)\[\/quote\]/is', function ($m) {
            $attrs = (string) ($m[1] ?? '');
            $author = '';
            $postId = '';

            if (preg_match('/\bauthor=(?:"([^"]*)"|\'([^\']*)\'|([^\]\s]+))/i', $attrs, $authorMatch)) {
                $author = trim((string) ($authorMatch[1] !== '' ? $authorMatch[1] : ($authorMatch[2] !== '' ? $authorMatch[2] : $authorMatch[3])));
            }

            if (preg_match('/\bpost=(?:"(\d+)"|\'(\d+)\'|(\d+))/i', $attrs, $postMatch)) {
                $postId = trim((string) ($postMatch[1] !== '' ? $postMatch[1] : ($postMatch[2] !== '' ? $postMatch[2] : $postMatch[3])));
            }

            $content = nl2br(htmlspecialchars((string) ($m[2] ?? ''), ENT_QUOTES, 'UTF-8'));
            $authorAttr = $author !== '' ? ' data-author="' . core_e($author) . '"' : '';
            $postAttr = $postId !== '' ? ' data-post="' . core_e($postId) . '"' : '';
            $header = $author !== ''
                ? '<div class="mfbb-quote-header"><i class="fa-solid fa-quote-left" aria-hidden="true"></i><a href="' . core_e(core_url('member/' . rawurlencode($author))) . '" class="mention mfbb-quote-author" data-mention-username="' . core_e($author) . '">@' . core_e($author) . '</a></div>'
                : '';

            return '<blockquote class="mfbb-quote"' . $authorAttr . $postAttr . '>' . $header . '<div class="mfbb-quote-body">' . $content . '</div></blockquote>';
        }, $text);
    }
}


if (!function_exists('core_body_to_html')) {
    function core_body_to_html(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }
        $body = preg_replace('/\n{3,}/', "\n\n", $body);
        if (class_exists(\App\Services\Import\BBCodeConverter::class) && preg_match('/\[(?:IMG|URL|B|I|U|S|CODE|QUOTE|LIST|COLOR|SIZE|MEDIA|SPOILER)\]/i', $body)) {
            $body = \App\Services\Import\BBCodeConverter::convert($body);
        }
        $body = core_quote_bb_to_html($body);

        $html = core_sanitize_html($body);

        if (class_exists(\App\Services\OembedService::class)) {
            $html = core_make(\App\Services\OembedService::class)->parseLinks($html);
        }

        return $html;
    }
}


if (!function_exists('core_pm_body_to_html')) {
    function core_pm_body_to_html(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }
        $body = preg_replace('/\n{3,}/', "\n\n", $body);
        $escaped = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return core_sanitize_html('<p class="mfbb-pm-plain">' . nl2br($escaped, false) . '</p>');
    }
}


if (!function_exists('core_display_post_html')) {
    function core_display_post_html(string $body, string $bodyHtml): string
    {
        $body = trim($body);
        $bodyHtml = trim($bodyHtml);
        if ($bodyHtml === '' && $body !== '' && function_exists('core_body_to_html')) {
            return core_body_to_html($body);
        }
        if ($bodyHtml !== '' && strpos($bodyHtml, '[IMG') === false) {
            $hasImgAlt = preg_match('/\[IMG\s+alt=/i', $body);
            $hasImgTag = preg_match('/<img\s/i', $bodyHtml);
            if (!($hasImgAlt && !$hasImgTag)) {
                return $bodyHtml;
            }
        }
        if ($body !== '' && class_exists(\App\Services\Import\BBCodeConverter::class) && (strpos($bodyHtml, '[IMG') !== false || preg_match('/\[IMG\s+alt=/i', $body))) {
            $converted = \App\Services\Import\BBCodeConverter::convert($body);
            return core_sanitize_html(core_quote_bb_to_html($converted));
        }
        return $bodyHtml;
    }
}

/**
 * @username → kullanıcı profil linki (sadece kayıtlı kullanıcılar).
 * Capsule/Eloquent ile kullanıcı kontrolü yapılır.
 */
if (!function_exists('core_process_mentions')) {
    function core_process_mentions(string $html): string
    {
        $db = \Illuminate\Database\Capsule\Manager::class;
        if (!class_exists($db)) {
            return $html;
        }
        // Zaten link olan mention'ları düz @username'e çevir; sonra hepsini tek seferde işleyelim (düzenlemede çift link önlenir)
        $html = preg_replace('/<a\s[^>]*class="[^"]*mention[^"]*"[^>]*>@([^<]+)<\/a>/iu', '@$1', $html);
        return preg_replace_callback('/(^|[>\s])@([a-zA-Z0-9_\x80-\xFF]+)/u', function ($m) {
            $prefix = $m[1];
            $username = $m[2];
            $exists = \Illuminate\Database\Capsule\Manager::table('users')->where('username', $username)->exists();
            if ($exists) {
                $link = '<a href="' . core_e(core_url('member/' . rawurlencode($username))) . '" class="mention font-semibold text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors" data-mention-username="' . core_e($username) . '">@' . core_e($username) . '</a>';
                return $prefix . $link;
            }
            return $m[0];
        }, $html);
    }
}

/**
 * #N → konu içi N. mesaja link (#post-N). Sadece bu konu sayfasında geçerlidir.
 */
if (!function_exists('core_process_post_refs')) {
    function core_process_post_refs(string $html, int $topicId = 0): string
    {
        return preg_replace_callback('/(^|[>\s])#(\d{1,5})\b/u', function ($m) use ($topicId) {
            $prefix = $m[1];
            $num = $m[2];
            $url = $topicId > 0 ? core_url('topic/' . (function_exists('topic_url_path_by_id') ? topic_url_path_by_id($topicId) : $topicId) . '/post-by-pos/' . (int)$num) : '#post-' . (int)$num;
            $dataAttr = $topicId > 0 ? 'data-topic-id="' . $topicId . '" data-post-pos="' . (int)$num . '"' : 'data-post-ref-id="' . (int)$num . '"';
            return $prefix . '<a href="' . core_e($url) . '" class="post-ref inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors" ' . $dataAttr . '>#' . $num . '</a>';
        }, $html);
    }
}

/**
 * Misafir + ayar açıkken: [secret], <secret_content> ve <details class="mfbb-spoiler"> içeriğini gizler.
 * Spoiler (mfbb-spoiler) aksi halde yalnızca tıklanınca açılır; üye-only gizleme için bu fonksiyon + admin ayarı kullanılır.
 */
if (!function_exists('core_hide_guest_content')) {
    function core_hide_guest_content(string $html, bool $isSecretHidden): string
    {
        $guestLockHtml = '<div class="my-4 p-4 rounded-md border-l-4 border-red-500 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 font-semibold">' .
            '<i class="fa-solid fa-lock mr-2"></i>' .
            'Bu içeriği görmek için kayıt olmalısınız. <a href="' . core_e(core_url('login')) . '" class="underline hover:text-red-900">Giriş Yap</a>' .
            '</div>';

        $pattern = '/(?:\[secret\]|<secret_content>)(.*?)(?:\[\/secret\]|<\/secret_content>)/is';
        $html = preg_replace_callback($pattern, function ($m) use ($isSecretHidden, $guestLockHtml) {
            if ($isSecretHidden) {
                return $guestLockHtml;
            }
            // Ziyaretçi değil veya ayar kapalı:
            $content = $m[1];

            return '<div class="my-4 p-4 rounded-md border-l-4 border-green-500 bg-green-50 dark:bg-green-900/30"><div class="text-green-800 dark:text-green-300 font-bold mb-2"><i class="fa-solid fa-unlock mr-2"></i>Gizli İçerik:</div>' .
                   '<div class="text-gray-800 dark:text-gray-200">' . $content . '</div></div>';
        }, $html);

        if ($isSecretHidden) {
            // Editör spoiler’ı (<details class="mfbb-spoiler">); strip_tags class’ı korur, burada misafire tüm blok gizlenir.
            $html = preg_replace('/<details\b[^>]*\bmfbb-spoiler\b[^>]*>.*?<\/details>/is', $guestLockHtml, $html);
        }

        return $html;
    }
}

/** İmza alanı: sadece b, i, u, a, br; max 260 karakter (tag’ler sayılmaz). */
if (!function_exists('core_sanitize_signature')) {
    function core_sanitize_signature(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $allowed = '<b><i><u><a><br>';
        $html = strip_tags($html, $allowed);
        $html = preg_replace_callback('/<a\s[^>]*href=(["\']?)([^"\'>\s]+)\1[^>]*>/i', function ($m) {
            $url = $m[2];
            if (preg_match('#^(https?://|/)#i', $url)) {
                return $m[0];
            }
            return '<a href="#">';
        }, $html);
        $plain = strip_tags($html);
        if (mb_strlen($plain) > 260) {
            return htmlspecialchars(mb_substr($plain, 0, 260), ENT_QUOTES, 'UTF-8');
        }
        return $html;
    }
}

/** Editörden gelen HTML'i güvenli etiketlerle filtreler (XSS önleme). Gösterim için body_html kaydederken kullanın. */
if (!function_exists('core_sanitize_html')) {
    function core_sanitize_html(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        // Editörden gelen aşırı boşluk: ardışık <br> ve boş/yarı-boş paragrafları azalt
        $html = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $html);
        $html = preg_replace('/(<p(?:[^>]*)>\s*<\/p>\s*){2,}/i', '', $html);
        // Tekil boş paragraf ve <p><br></p> / <p>&nbsp;</p> gibi etiketleri kaldır (Toast UI vb. ekstra satır boşluğu)
        $html = preg_replace('/<p(\s[^>]*)?>\s*<\/p>/i', '', $html);
        $html = preg_replace('/<p(\s[^>]*)?>\s*(<br\s*\/?>\s*|&nbsp;)*\s*<\/p>/i', '', $html);
        // Tüm <pre> kod bloklarına tema sınıfı ekle (editör + BBCode aynı görünsün)
        $html = preg_replace_callback('/<pre(\s[^>]*)?>/i', function ($m) {
            $rest = $m[1] ?? '';
            if (stripos($rest, 'mfbb-code-block') !== false) {
                return $m[0];
            }
            if (preg_match('/\bclass\s*=\s*["\']([^"\']*)["\']/', $rest, $cx)) {
                $rest = preg_replace('/\bclass\s*=\s*["\'][^"\']*["\']/', 'class="' . trim($cx[1] . ' mfbb-code-block') . '"', $rest);
                return '<pre' . $rest . '>';
            }
            return '<pre class="mfbb-code-block"' . $rest . '>';
        }, $html);
        // Eğer içerik escape edilmiş HTML ise (örn. &lt;span&gt;) önce decode et; böylece render doğru çalışır
        if (preg_match('/&lt;(?:p|span|div|strong|em|mark|sup|sub|u|br|a\b)/i', $html)) {
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (preg_match('/<script|javascript:\s|data:\s*text\/html|on\w+\s*=\s*["\']?/i', $html) && class_exists(\App\Services\SecurityLogger::class)) {
            \App\Services\SecurityLogger::log('xss_attempt', ['snippet' => substr($html, 0, 500)]);
        }
        $allowedTags = '<p><br><strong><b><em><i><u><a><ul><ol><li><img><h2><h3><h4><h5><h6><h1><blockquote><code><pre><span><div><table><thead><tbody><tr><th><td><video><source><iframe><embed><figure><figcaption><oembed><hr><s><strike><del><sub><sup><mark><details><summary><input>';
        $html = strip_tags($html, $allowedTags);
        // Tüm event handler attribute'larını kaldır (onclick, onerror, onload vb.) — strip_tags bunları silmez
        $html = preg_replace('/\s+on\w+\s*=\s*(?:["\'][^"\']*["\']|[^\s>]+)/i', '', $html);
        // href ve src sadece güvenli URL olsun (javascript:, data: vb. engelle)
        $html = preg_replace_callback('/<a\s[^>]*href=(["\']?)([^"\'>\s]+)\1[^>]*>/i', function ($m) {
            $url = $m[2];
            if (preg_match('#^(https?://|/|\#)#i', $url)) {
                return $m[0];
            }
            return '<a href="#">';
        }, $html);
        // img: çift/tek tırnaklı src (TinyMCE + smiley URL'leri); eski regex boş veya bozuk src'yi yanlış eşleştiriyordu
        $html = preg_replace_callback('/<img\b[^>]*>/is', function ($m) {
            $tag = $m[0];
            $url = '';
            if (preg_match('/\bsrc\s*=\s*"([^"]*)"/i', $tag, $sm)) {
                $url = trim(html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            } elseif (preg_match("/\bsrc\s*=\s*'([^']*)'/i", $tag, $sm)) {
                $url = trim(html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            } elseif (preg_match('/\bsrc\s*=\s*([^\s>]+)/i', $tag, $sm)) {
                $url = trim(html_entity_decode($sm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            if ($url === '' || !preg_match('#^(https?://|/)#i', $url)) {
                return '';
            }

            return $tag;
        }, $html);
        // iframe sadece YouTube/Dailymotion embed için (tüm etiket bloğunu değiştir)
        $html = preg_replace_callback('/<iframe\s[^>]*src=(["\']?)([^"\'>\s]+)\1[^>]*>.*?<\/iframe>/is', function ($m) {
            $url = $m[2];
            if (preg_match('#^https?://(?:www\.)?(?:youtube\.com/embed/|youtube-nocookie\.com/embed/|dailymotion\.com/embed/video/)#i', $url)) {
                return '<iframe src="' . htmlspecialchars($url) . '" allowfullscreen loading="lazy"></iframe>';
            }
            return '';
        }, $html);
        // Sarmalayıcı div yoksa (eski içerik / yapıştırma) 16:9 konteyner ekle; video taşmasın
        if (preg_match('/<iframe\s[^>]*src=/i', $html) && strpos($html, 'mfbb-media-embed') === false) {
            $html = preg_replace_callback('/<iframe\s[^>]*>.*?<\/iframe>/is', function ($m) {
                return '<div class="mfbb-media-embed ratio ratio-16x9">' . $m[0] . '</div>';
            }, $html);
        }
        // input sadece task list checkbox için: type=checkbox ve checked/disabled kalsın
        $html = preg_replace_callback('/<input\s[^>]*>/i', function ($m) {
            $tag = $m[0];
            if (!preg_match('/\btype\s*=\s*["\']?checkbox["\']?/i', $tag)) {
                return '';
            }
            return preg_replace('/\s+(?!type|checked|disabled)[a-z-]+\s*=\s*["\'][^"\']*["\']/i', '', $tag);
        }, $html);
        return $html;
    }
}

if (!function_exists('core_csrf_manager')) {
    /**
     * Tekil CSRF manager. Namespace sabit '' (HTTPS/HTTP farkı sunucuda token eşleşmemesine yol açmasın).
     * Önce uygulama session'ı başlatılır ki token aynı session'da saklansın.
     */
    function core_csrf_manager(): ?\Symfony\Component\Security\Csrf\CsrfTokenManagerInterface
    {
        static $manager = null;
        if ($manager !== null) {
            return $manager;
        }
        if (!class_exists(\Symfony\Component\Security\Csrf\CsrfTokenManager::class)) {
            return null;
        }
        if (class_exists(\Forecor\Core\SessionManager::class)) {
            \Forecor\Core\SessionManager::get();
        }
        $manager = new \Symfony\Component\Security\Csrf\CsrfTokenManager(null, null, '');
        return $manager;
    }
}

if (!function_exists('core_csrf_token')) {
    function core_csrf_token(string $tokenId = 'csrf'): string
    {
        $manager = core_csrf_manager();
        if ($manager === null) {
            return '';
        }
        return $manager->getToken($tokenId)->getValue();
    }
}

if (!function_exists('core_csrf_field')) {
    function core_csrf_field(string $tokenId = 'csrf', string $fieldName = '_token'): string
    {
        $token = core_csrf_token($tokenId);
        return $token ? '<input type="hidden" name="' . core_e($fieldName) . '" value="' . core_e($token) . '">' : '';
    }
}

if (!function_exists('core_csrf_valid')) {
    function core_csrf_valid(string $tokenId, string $value): bool
    {
        $manager = core_csrf_manager();
        if ($manager === null) {
            return false;
        }
        $valid = $manager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($tokenId, $value));
        if (!$valid && class_exists(\App\Services\SecurityLogger::class)) {
            \App\Services\SecurityLogger::log('csrf_failed', ['token_id' => $tokenId]);
        }
        return $valid;
    }
}

/** Metinden @username mention'larını çıkarıp ilgili kullanıcı id'lerini döndürür (tekrarsız). */
if (!function_exists('core_extract_mentioned_user_ids')) {
    function core_extract_mentioned_user_ids(string $text): array
    {
        $ids = [];
        if (!class_exists(\Illuminate\Database\Capsule\Manager::class)) {
            return $ids;
        }
        if (preg_match_all('/(^|[>\s])@([a-zA-Z0-9_\x80-\xFF]+)/u', $text, $m, PREG_SET_ORDER)) {
            $usernames = array_unique(array_map(function ($x) {
                return $x[2];
            }, $m));
            foreach ($usernames as $username) {
                $id = \Illuminate\Database\Capsule\Manager::table('users')->where('username', $username)->value('id');
                if ($id !== null) {
                    $ids[(int)$id] = true;
                }
            }
        }
        return array_keys($ids);
    }
}

if (!function_exists('validator')) {
    /**
     * Create a new Validator instance for request data.
     *
     * @param array $data Data to validate (e.g. $_POST)
     * @param array $rules Validation rules (e.g. ['title' => 'required|min:5'])
     * @param array $messages Custom error messages
     * @return \Forecor\Core\Validation\Validator
     */
    function validator(array $data, array $rules, array $messages = []): \Forecor\Core\Validation\Validator
    {
        return \Forecor\Core\Validation\Validator::make($data, $rules, $messages);
    }
}

if (class_exists(\App\Core\ClassProxyManager::class)) {
    \App\Core\ClassProxyManager::registerAutoloader();
}

if (!function_exists('core_extend_class')) {
    /**
     * Çekirdek bir sınıfı genişletmek (extend) için eklenti (plugin) kaydı oluşturur.
     * Bootstrap aşamasında (Örn: Plugin/Bootstrapper.php) çağrılmalıdır.
     *
     * @param string $originalClass Genişletilecek asıl sınıf (örn: App\Services\TopicService)
     * @param string $extensionClass Genişleten eklenti sınıfı (örn: MyPlugin\XFCP_TopicService)
     */
    function core_extend_class(string $originalClass, string $extensionClass): void
    {
        if (class_exists(\App\Core\ClassProxyManager::class)) {
            \App\Core\ClassProxyManager::extend($originalClass, $extensionClass);
        }
    }
}

if (!function_exists('core_make')) {
    /**
     * ClassProxyManager üzerinden sınıfın son halini (varsa genişletilmiş halini) döndürerek nesneleştirir.
     * Artık `new SınıfAdı(...)` yerine `core_make(SınıfAdı::class, ...$args)` kullanılmalıdır.
     *
     * @param string $className Nesneleştirilecek sınıf
     * @param mixed ...$args Constructor'a geçilecek parametreler
     * @return mixed
     */
    function core_make(string $className, ...$args)
    {
        if (class_exists(\App\Core\ClassProxyManager::class)) {
            return \App\Core\ClassProxyManager::instantiate($className, ...$args);
        }

        // Geriye dönük uyumluluk (Eğer ClassProxyManager yoksa normal nesne yarat)
        return new $className(...$args);
    }
}
