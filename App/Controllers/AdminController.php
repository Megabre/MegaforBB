<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\DocPage;
use App\Models\Forum;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostReport;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserCustomField;
use App\Models\UserReputation;
use App\Services\SecurityLogUserLookup;
use Forecor\Core\Application;
use Illuminate\Database\Capsule\Manager as DB;

class AdminController extends BaseController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);

        // Security Check
        $this->ensureAdminAccess();
    }

    private function ensureAdminAccess(): void
    {
        $user = $this->app->auth()->user();

        if (!$user) {
            $this->redirect(core_url('login'));
        }

        if (!$user->role || !$user->role->is_staff) {
            http_response_code(403);
            echo "403 Forbidden - Access Denied";
            exit;
        }

        // Admin 2FA: soru-cevap ile ek doğrulama (sadece 2FA açıksa ve bu sayfa 2FA sayfası değilse)
        $adminPath = env('ADMIN_PATH', 'admin');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $twoFaRoutes = ['/' . $adminPath . '/twofa', '/' . $adminPath . '/security/twofa'];
        $isTwoFaRoute = false;
        foreach ($twoFaRoutes as $r) {
            if (strpos($uri, $r) !== false) {
                $isTwoFaRoute = true;
                break;
            }
        }
        $twoFaQuestion = $user->admin_twofa_question ?? '';
        if ($twoFaQuestion !== '' && !$isTwoFaRoute) {
            $session = \Forecor\Core\SessionManager::get();
            $verifiedId = (int) $session->get('admin_2fa_verified_user_id', 0);
            if ($verifiedId !== (int) $user->id) {
                $this->redirect(core_url($adminPath . '/twofa'));
                exit;
            }
        }
    }

    /**
     * Returns admin menu groups and active page info (for Twig and PHP views).
     */
    protected function getAdminNavData(): array
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/' . $adminPath;

        $navGroups = [
            [
                'id' => 'dashboard',
                'icon' => 'ti-dashboard',
                'label' => lang('admin.menu.dashboard'),
                'url' => core_url($adminPath),
                'children' => [],
            ],
            [
                'id' => 'forum', 'icon' => 'ti-messages', 'label' => lang('admin.menu.forum'),
                'children' => [
                    ['icon' => 'ti-folders', 'label' => lang('admin.menu.categories'), 'url' => core_url($adminPath . '/forums'), 'match' => '/' . $adminPath . '/forums'],
                    ['icon' => 'ti-tag', 'label' => lang('admin.menu.prefixes'), 'url' => core_url($adminPath . '/prefixes'), 'match' => '/' . $adminPath . '/prefixes'],
                    ['icon' => 'ti-tags', 'label' => lang('admin.menu.tags'), 'url' => core_url($adminPath . '/tags'), 'match' => '/' . $adminPath . '/tags'],
                    ['icon' => 'ti-bulb', 'label' => lang('idelist.admin_title'), 'url' => core_url($adminPath . '/idelist'), 'match' => '/' . $adminPath . '/idelist'],
                    ['icon' => 'ti-list-details', 'label' => lang('admin.menu.topic_settings'), 'url' => core_url($adminPath . '/topic-settings'), 'match' => '/' . $adminPath . '/topic-settings'],
                    ['icon' => 'ti-rss', 'label' => lang('admin.menu.rss_feeds'), 'url' => core_url($adminPath . '/rss-feeds'), 'match' => '/' . $adminPath . '/rss-feeds'],
                    ['icon' => 'ti-trash', 'label' => lang('admin.menu.trash'), 'url' => core_url($adminPath . '/trash'), 'match' => '/' . $adminPath . '/trash'],
                ],
            ],
            [
                'id' => 'users', 'icon' => 'ti-users', 'label' => lang('admin.menu.users'),
                'children' => [
                    ['icon' => 'ti-user', 'label' => lang('admin.menu.user_list'), 'url' => core_url($adminPath . '/users'), 'match' => '/' . $adminPath . '/users'],
                    ['icon' => 'ti-user-plus', 'label' => lang('admin.menu.invitations'), 'url' => core_url($adminPath . '/invitations'), 'match' => '/' . $adminPath . '/invitations'],
                    ['icon' => 'ti-users-group', 'label' => lang('admin.menu.roles_groups'), 'url' => core_url($adminPath . '/roles'), 'match' => '/' . $adminPath . '/roles'],
                    ['icon' => 'ti-lock-access', 'label' => lang('admin.menu.group_permissions'), 'url' => core_url($adminPath . '/group-permissions'), 'match' => '/' . $adminPath . '/group-permissions'],
                    ['icon' => 'ti-forms', 'label' => lang('admin.menu.custom_fields'), 'url' => core_url($adminPath . '/custom-fields'), 'match' => '/' . $adminPath . '/custom-fields'],
                    ['separator' => true],
                    ['icon' => 'ti-ban', 'label' => lang('admin.menu.bans'), 'url' => core_url($adminPath . '/policies/bans'), 'match' => '/' . $adminPath . '/policies/bans'],
                    ['icon' => 'ti-alert-triangle', 'label' => lang('admin.menu.warnings'), 'url' => core_url($adminPath . '/policies/warnings'), 'match' => '/' . $adminPath . '/policies/warnings'],
                    ['separator' => true],
                    ['icon' => 'ti-star', 'label' => lang('admin.menu.reputation'), 'url' => core_url($adminPath . '/reputations'), 'match' => '/' . $adminPath . '/reputations'],
                    ['icon' => 'ti-award', 'label' => lang('admin.menu.rewards'), 'url' => core_url($adminPath . '/rewards'), 'match' => '/' . $adminPath . '/rewards'],
                ],
            ],
            [
                'id' => 'content', 'icon' => 'ti-file-text', 'label' => lang('admin.menu.content'),
                'children' => [
                    ['icon' => 'ti-file-text', 'label' => lang('admin.menu.pages'), 'url' => core_url($adminPath . '/pages'), 'match' => '/' . $adminPath . '/pages'],
                    ['icon' => 'ti-mood-smile', 'label' => lang('admin.menu.smileys'), 'url' => core_url($adminPath . '/smileys'), 'match' => '/' . $adminPath . '/smileys'],
                    ['icon' => 'ti-speakerphone', 'label' => lang('admin.menu.announcements_short'), 'url' => core_url($adminPath . '/announcements'), 'match' => '/' . $adminPath . '/announcements'],
                    ['icon' => 'ti-ad-2', 'label' => lang('admin.menu.ads_short'), 'url' => core_url($adminPath . '/ads'), 'match' => '/' . $adminPath . '/ads'],
                    ['icon' => 'ti-book-2', 'label' => lang('admin.menu.documentation'), 'url' => core_url($adminPath . '/documentation-settings'), 'match' => '/' . $adminPath . '/documentation-settings'],
                ],
            ],
            [
                'id' => 'portal', 'icon' => 'ti-layout-dashboard', 'label' => lang('admin.menu.portal'),
                'children' => [
                    ['icon' => 'ti-news', 'label' => lang('admin.menu.portal_articles'), 'url' => core_url($adminPath . '/portal-settings'), 'match' => '/' . $adminPath . '/portal-settings'],
                    ['icon' => 'ti-layout-list', 'label' => lang('admin.menu.son_olaylar'), 'url' => core_url($adminPath . '/son-olaylar'), 'match' => '/' . $adminPath . '/son-olaylar'],
                    ['icon' => 'ti-layout-dashboard', 'label' => lang('admin.menu.hero_card'), 'url' => core_url($adminPath . '/hero'), 'match' => '/' . $adminPath . '/hero'],
                    ['icon' => 'ti-layout-sidebar', 'label' => lang('admin.menu.widget_management'), 'url' => core_url($adminPath . '/widgets'), 'match' => '/' . $adminPath . '/widgets'],
                ],
            ],
            [
                'id' => 'appearance', 'icon' => 'ti-color-swatch', 'label' => lang('admin.menu.appearance'),
                'children' => [
                    ['icon' => 'ti-color-swatch', 'label' => lang('admin.menu.theme_management'), 'url' => core_url($adminPath . '/themes'), 'match' => '/' . $adminPath . '/themes'],
                    ['icon' => 'ti-menu-2', 'label' => lang('admin.menu.menus'), 'url' => core_url($adminPath . '/settings/menu'), 'match' => '/' . $adminPath . '/settings/menu'],
                ],
            ],
            [
                'id' => 'settings', 'icon' => 'ti-settings', 'label' => lang('admin.menu.settings'),
                'children' => [
                    ['icon' => 'ti-home-cog', 'label' => lang('admin.menu.general_settings'), 'url' => core_url($adminPath . '/settings/general'), 'match' => '/' . $adminPath . '/settings/general'],
                    ['icon' => 'ti-user-cog', 'label' => lang('admin.menu.user_settings'), 'url' => core_url($adminPath . '/user-settings'), 'match' => '/' . $adminPath . '/user-settings'],
                    ['icon' => 'ti-message-cog', 'label' => lang('admin.menu.forum_post'), 'url' => core_url($adminPath . '/topic-post-settings'), 'match' => '/' . $adminPath . '/topic-post-settings'],
                    ['icon' => 'ti-bell', 'label' => lang('admin.menu.notification_settings'), 'url' => core_url($adminPath . '/communication-settings'), 'match' => '/' . $adminPath . '/communication-settings'],
                    ['separator' => true],
                    ['icon' => 'ti-mail-cog', 'label' => lang('admin.menu.mail_smtp'), 'url' => core_url($adminPath . '/settings/mail'), 'match' => '/' . $adminPath . '/settings/mail'],
                    ['icon' => 'ti-search', 'label' => lang('admin.menu.seo_meta'), 'url' => core_url($adminPath . '/settings/seo'), 'match' => '/' . $adminPath . '/settings/seo'],
                    ['icon' => 'ti-device-mobile', 'label' => lang('admin.menu.pwa_settings'), 'url' => core_url($adminPath . '/pwa-settings'), 'match' => '/' . $adminPath . '/pwa-settings'],
                    ['icon' => 'ti-cloud-upload', 'label' => lang('admin.menu.storage_s3_r2'), 'url' => core_url($adminPath . '/settings/storage'), 'match' => '/' . $adminPath . '/settings/storage'],
                    ['icon' => 'ti-bug', 'label' => lang('admin.menu.debug_mode'), 'url' => core_url($adminPath . '/settings/debug'), 'match' => '/' . $adminPath . '/settings/debug'],
                    ['icon' => 'ti-language', 'label' => lang('admin.menu.languages'), 'url' => core_url($adminPath . '/languages'), 'match' => '/' . $adminPath . '/languages'],
                ],
            ],
            [
                'id' => 'communication', 'icon' => 'ti-mail', 'label' => lang('admin.menu.communication'),
                'children' => [
                    ['icon' => 'ti-mail-opened', 'label' => lang('admin.menu.incoming_messages'), 'url' => core_url($adminPath . '/contact'), 'match' => '/' . $adminPath . '/contact'],
                    ['icon' => 'ti-send', 'label' => lang('admin.menu.send_mail'), 'url' => core_url($adminPath . '/communication/send'), 'match' => '/' . $adminPath . '/communication/send'],
                    ['icon' => 'ti-mail-forward', 'label' => lang('admin.menu.bulk_mail'), 'url' => core_url($adminPath . '/communication/bulk-mail'), 'match' => '/' . $adminPath . '/communication/bulk-mail'],
                    ['icon' => 'ti-users', 'label' => lang('admin.menu.bulk_message'), 'url' => core_url($adminPath . '/communication/bulk'), 'match' => '/' . $adminPath . '/communication/bulk'],
                    ['icon' => 'ti-file-text', 'label' => lang('admin.menu.message_templates'), 'url' => core_url($adminPath . '/communication/message-templates'), 'match' => '/' . $adminPath . '/communication/message-templates'],
                    ['icon' => 'ti-template', 'label' => lang('admin.menu.mail_templates'), 'url' => core_url($adminPath . '/communication/mail-templates'), 'match' => '/' . $adminPath . '/communication/mail-templates'],
                ],
            ],
            [
                'id' => 'security', 'icon' => 'ti-shield-lock', 'label' => lang('admin.menu.security'),
                'children' => [
                    ['icon' => 'ti-activity', 'label' => lang('admin.menu.live_traffic'), 'url' => core_url($adminPath . '/analytics'), 'match' => '/' . $adminPath . '/analytics'],
                    ['icon' => 'ti-shield-cog', 'label' => lang('admin.menu.security_rules'), 'url' => core_url($adminPath . '/security'), 'match' => '/' . $adminPath . '/security'],
                    ['icon' => 'ti-filter', 'label' => lang('admin.menu.censorship'), 'url' => core_url($adminPath . '/censorship'), 'match' => '/' . $adminPath . '/censorship'],
                    ['icon' => 'ti-key', 'label' => lang('admin.menu.twofa'), 'url' => core_url($adminPath . '/security/twofa'), 'match' => '/' . $adminPath . '/security/twofa'],
                    ['icon' => 'ti-robot', 'label' => lang('admin.menu.spam_zombie'), 'url' => core_url($adminPath . '/spam-zombie'), 'match' => '/' . $adminPath . '/spam-zombie'],
                    ['icon' => 'ti-file-alert', 'label' => lang('admin.menu.security_log'), 'url' => core_url($adminPath . '/security/log'), 'match' => '/' . $adminPath . '/security/log'],
                ],
            ],
            [
                'id' => 'performance', 'icon' => 'ti-rocket', 'label' => lang('admin.menu.performance'),
                'children' => [
                    ['icon' => 'ti-database', 'label' => lang('admin.menu.cache_redis'), 'url' => core_url($adminPath . '/performance/cache'), 'match' => '/' . $adminPath . '/performance/cache'],
                    ['icon' => 'ti-rocket', 'label' => lang('admin.menu.varnish_fpc'), 'url' => core_url($adminPath . '/performance/varnish'), 'match' => '/' . $adminPath . '/performance/varnish'],
                    ['icon' => 'ti-file-zip', 'label' => lang('admin.menu.minify_cdn'), 'url' => core_url($adminPath . '/performance/minify'), 'match' => '/' . $adminPath . '/performance/minify'],
                    ['icon' => 'ti-photo', 'label' => lang('admin.menu.image_lazy_load'), 'url' => core_url($adminPath . '/performance/assets'), 'match' => '/' . $adminPath . '/performance/assets'],
                ],
            ],
            [
                'id' => 'tools', 'icon' => 'ti-tool', 'label' => lang('admin.menu.tools'),
                'children' => [
                    ['icon' => 'ti-file-check', 'label' => lang('admin.menu.file_verification'), 'url' => core_url($adminPath . '/file-verification'), 'match' => '/' . $adminPath . '/file-verification'],
                    ['icon' => 'ti-database-export', 'label' => lang('admin.menu.backup'), 'url' => core_url($adminPath . '/backup'), 'match' => '/' . $adminPath . '/backup'],
                    ['icon' => 'ti-shield-check', 'label' => lang('admin.menu.stop_forum_spam'), 'url' => core_url($adminPath . '/stop-forum-spam'), 'match' => '/' . $adminPath . '/stop-forum-spam'],
                    ['icon' => 'ti-file-text', 'label' => lang('admin.menu.error_log'), 'url' => core_url($adminPath . '/error-log'), 'match' => '/' . $adminPath . '/error-log'],
                    ['icon' => 'ti-clock-play', 'label' => lang('admin.menu.cronjobs'), 'url' => core_url($adminPath . '/cronjobs'), 'match' => '/' . $adminPath . '/cronjobs'],
                    ['icon' => 'ti-refresh', 'label' => lang('admin.menu.rebuild'), 'url' => core_url($adminPath . '/rebuild'), 'match' => '/' . $adminPath . '/rebuild'],
                    ['icon' => 'ti-database-import', 'label' => lang('admin.menu.import'), 'url' => core_url($adminPath . '/import'), 'match' => '/' . $adminPath . '/import'],
                    ['icon' => 'ti-puzzle', 'label' => lang('admin.menu.plugins'), 'url' => core_url($adminPath . '/plugins'), 'match' => '/' . $adminPath . '/plugins'],
                    ['icon' => 'ti-alert-octagon', 'label' => lang('admin.menu.reset'), 'url' => core_url($adminPath . '/reset'), 'match' => '/' . $adminPath . '/reset'],
                    ['separator' => true],
                    ['icon' => 'ti-certificate', 'label' => 'Lisans Yönetimi', 'url' => core_url($adminPath . '/license'), 'match' => '/' . $adminPath . '/license'],
                ],
            ],
        ];

        if ($this->app->hooks()) {
            $navGroups = $this->app->hooks()->applyFilters('admin.menu', $navGroups);
        }

        $adminActiveGroup = 'dashboard';
        $adminActiveChildUrl = '';
        $bestMatchLen = 0;
        $fullUri = $requestUri;

        foreach ($navGroups as $group) {
            $children = $group['children'] ?? [];
            foreach ($children as $child) {
                if (isset($child['separator'])) {
                    continue;
                }
                $m = $child['match'] ?? '';
                if ($m !== '' && strpos($fullUri, $m) !== false && strlen($m) > $bestMatchLen) {
                    $bestMatchLen = strlen($m);
                    $adminActiveGroup = $group['id'];
                    $adminActiveChildUrl = $child['url'];
                }
            }
        }
        if ($bestMatchLen === 0) {
            foreach ($navGroups as $group) {
                $children = $group['children'] ?? [];
                foreach ($children as $child) {
                    if (isset($child['separator'])) {
                        continue;
                    }
                    $m = $child['match'] ?? '';
                    $basePath = $m !== '' ? strtok($m, '?') : '';
                    if ($basePath !== false && $basePath !== '' && strpos($fullUri, $basePath) !== false && strlen($basePath) > $bestMatchLen) {
                        $bestMatchLen = strlen($basePath);
                        $adminActiveGroup = $group['id'];
                        $adminActiveChildUrl = $child['url'];
                    }
                }
            }
        }

        return [
            'adminNavGroups' => $navGroups,
            'adminActiveGroup' => $adminActiveGroup,
            'adminActiveChildUrl' => $adminActiveChildUrl,
            'adminHasSubnav' => $adminActiveGroup !== 'dashboard',
        ];
    }

    protected function view(string $view, array $data = []): string
    {
        $basePath = $this->app->getBasePath();
        $adminPath = env('ADMIN_PATH', 'admin');
        $viewPath = str_replace('admin/', '', $view);
        $twigTemplate = $viewPath . '.html.twig';
        $twigPath = $basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $viewPath) . '.html.twig';

        $data['pageTitle'] = $data['pageTitle'] ?? lang('admin.common.admin_panel');
        $data['locale'] = $data['locale'] ?? $this->locale();
        $data['user'] = $data['user'] ?? $this->app->auth()->user();
        $data['adminPath'] = $adminPath;
        $data['app'] = $this->app;
        $data['attackModeOn'] = $this->app->getSettingRaw('security_attack_mode', '0') === '1';
        $data = array_merge($data, $this->getAdminNavData());
        $data['version_upgrade_available'] = \App\Services\VersionCheckService::isUpgradeAvailable();
        $data['version_latest_remote'] = \App\Services\VersionCheckService::getLatestRemoteVersion();
        $data['version_current'] = \App\Version::VERSION;
        $data['version_file_status'] = \App\Services\VersionCheckService::getFileVerificationStatus();
        $data['version_integrity_problem'] = \App\Services\VersionCheckService::hasIntegrityProblems();
        $data['version_integrity_message'] = \App\Services\VersionCheckService::getIntegrityMessage();

        if (is_file($twigPath)) {
            return $this->app->twig('admin')->render($twigTemplate, $data);
        }

        // Admin paneli tamamen Twig kullanır; PHP fallback kaldırıldı.
        http_response_code(500);
        return '<!-- Admin view not found (Twig only): ' . htmlspecialchars($viewPath . '.html.twig') . ' -->';
    }

    public function index(): string
    {
        $today = date('Y-m-d');
        $stats = [];

        try {
            $stats['totalUsers'] = (int) User::count();
        } catch (\Throwable $e) {
            $stats['totalUsers'] = 0;
        }
        try {
            $stats['totalTopics'] = (int) DB::table('topics')->whereNull('deleted_at')->count();
        } catch (\Throwable $e) {
            $stats['totalTopics'] = 0;
        }
        try {
            $stats['totalPosts'] = (int) DB::table('posts as p')
                ->join('topics as t', 't.id', '=', 'p.topic_id')
                ->whereNull('p.deleted_at')
                ->whereNull('t.deleted_at')
                ->count();
        } catch (\Throwable $e) {
            $stats['totalPosts'] = 0;
        }
        try {
            $stats['pendingReports'] = (int) PostReport::where('status', 'pending')->count();
        } catch (\Throwable $e) {
            $stats['pendingReports'] = 0;
        }
        try {
            $stats['bannedUsers'] = (int) User::where('is_banned', 1)->count();
        } catch (\Throwable $e) {
            $stats['bannedUsers'] = 0;
        }
        try {
            $stats['totalCategories'] = (int) Category::count();
        } catch (\Throwable $e) {
            $stats['totalCategories'] = 0;
        }
        try {
            $stats['totalForums'] = (int) Forum::count();
        } catch (\Throwable $e) {
            $stats['totalForums'] = 0;
        }
        try {
            $stats['totalLikes'] = (int) PostLike::count();
        } catch (\Throwable $e) {
            $stats['totalLikes'] = 0;
        }
        try {
            $stats['totalViews'] = (int) DB::table('topics')->whereNull('deleted_at')->sum('view_count');
        } catch (\Throwable $e) {
            $stats['totalViews'] = 0;
        }
        try {
            $stats['totalCustomFields'] = (int) UserCustomField::count();
        } catch (\Throwable $e) {
            $stats['totalCustomFields'] = 0;
        }
        try {
            $stats['totalArticles'] = (int) DB::table('topics')->whereNull('deleted_at')->where('type', 'article')->count();
        } catch (\Throwable $e) {
            $stats['totalArticles'] = 0;
        }
        try {
            $stats['totalReplies'] = (int) DB::table('topics')->whereNull('deleted_at')->sum('reply_count');
        } catch (\Throwable $e) {
            $stats['totalReplies'] = 0;
        }
        try {
            $stats['totalRep'] = (int) UserReputation::count();
        } catch (\Throwable $e) {
            $stats['totalRep'] = 0;
        }
        try {
            $stats['positiveRep'] = (int) UserReputation::where('value', 1)->count();
        } catch (\Throwable $e) {
            $stats['positiveRep'] = 0;
        }
        try {
            $stats['negativeRep'] = (int) UserReputation::where('value', -1)->count();
        } catch (\Throwable $e) {
            $stats['negativeRep'] = 0;
        }
        try {
            $stats['unreadContactCount'] = (int) ContactMessage::where('is_read', 0)->count();
        } catch (\Throwable $e) {
            $stats['unreadContactCount'] = 0;
        }

        try {
            $stats['todayUsers'] = (int) User::whereDate('created_at', $today)->count();
        } catch (\Throwable $e) {
            $stats['todayUsers'] = 0;
        }
        try {
            $stats['todayTopics'] = (int) DB::table('topics')
                ->whereNull('deleted_at')
                ->whereDate('created_at', $today)
                ->count();
        } catch (\Throwable $e) {
            $stats['todayTopics'] = 0;
        }
        try {
            $stats['todayPosts'] = (int) DB::table('posts as p')
                ->join('topics as t', 't.id', '=', 'p.topic_id')
                ->whereNull('p.deleted_at')
                ->whereNull('t.deleted_at')
                ->whereDate('p.created_at', $today)
                ->count();
        } catch (\Throwable $e) {
            $stats['todayPosts'] = 0;
        }

        $threshold = time() - 900;
        try {
            $stats['onlineUsers'] = (int) (DB::table('sessions')->whereNotNull('user_id')->where('last_activity', '>', $threshold)->selectRaw('COUNT(DISTINCT user_id) as cnt')->value('cnt') ?? 0);
        } catch (\Throwable $e) {
            $stats['onlineUsers'] = 0;
        }

        $recentUsers = [];
        try {
            $recentUsers = User::with('role')->orderByDesc('created_at')->limit(5)->get()->map(fn ($u) => (object)['id' => $u->id, 'username' => $u->username, 'email' => $u->email, 'avatar_path' => $u->avatar_path, 'created_at' => $u->created_at?->format('Y-m-d H:i:s'), 'is_banned' => $u->is_banned, 'is_verified' => $u->is_verified, 'role_name' => $u->role?->name, 'role_color' => $u->role?->color])->all();
        } catch (\Throwable $e) {
        }

        $recentTopics = [];
        try {
            $recentTopics = Topic::visibleToUserWithPrivacy($this->app->auth()->user()->id ?? null, true)->with(['user', 'forum'])->orderByDesc('created_at')->limit(5)->get()->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'view_count' => $t->view_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'username' => $t->user?->username, 'forum_name' => $t->forum?->name])->all();
        } catch (\Throwable $e) {
        }

        $recentArticles = [];
        try {
            $recentArticles = Topic::visibleToUserWithPrivacy($this->app->auth()->user()->id ?? null, true)->whereRaw("COALESCE(type, 'topic') = 'article'")->with(['user', 'forum'])->orderByDesc('created_at')->limit(5)->get()->map(fn ($t) => (object)['id' => $t->id, 'title' => $t->title, 'slug' => $t->slug, 'reply_count' => $t->reply_count, 'created_at' => $t->created_at?->format('Y-m-d H:i:s'), 'username' => $t->user?->username, 'forum_name' => $t->forum?->name])->all();
        } catch (\Throwable $e) {
        }

        $recentDocPages = [];
        try {
            $recentDocPages = DocPage::with('section')->orderByDesc('id')->limit(5)->get()->map(fn ($p) => (object)['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug, 'section_name' => $p->section?->title ?? '—', 'section_id' => $p->section_id])->all();
        } catch (\Throwable $e) {
        }

        $recentPosts = [];
        try {
            $recentPosts = Post::with(['topic', 'user'])->orderByDesc('created_at')->limit(10)->get()->map(function ($p) {
                $topicId = (int) ($p->topic_id ?? 0);
                $url = $topicId > 0 ? core_url('topic/' . topic_url_path_by_id($topicId) . '#post-' . (int) $p->id) : '#';
                return (object)[
                    'id' => $p->id,
                    'topic_id' => $topicId,
                    'topic_title' => $p->topic?->title ?? '—',
                    'topic_url' => $url,
                    'username' => $p->user?->username ?? '—',
                    'body_excerpt' => mb_substr(strip_tags((string) ($p->body_html ?? $p->body ?? '')), 0, 80) . (mb_strlen(strip_tags((string) ($p->body_html ?? $p->body ?? ''))) > 80 ? '…' : ''),
                    'created_at' => $p->created_at?->format('Y-m-d H:i:s'),
                ];
            })->all();
        } catch (\Throwable $e) {
        }

        $recentReps = [];
        try {
            $recentReps = UserReputation::with(['fromUser:id,username', 'toUser:id,username'])
                ->orderByDesc('created_at')
                ->limit(3)
                ->get()
                ->map(fn ($r) => (object) [
                    'value' => $r->value,
                    'comment' => $r->comment,
                    'created_at' => $r->created_at?->format('Y-m-d H:i:s'),
                    'from_username' => $r->fromUser->username ?? null,
                    'to_username' => $r->toUser->username ?? null,
                ])
                ->all();
        } catch (\Throwable $e) {
        }

        $recentContactMessages = [];
        try {
            $recentContactMessages = ContactMessage::orderByDesc('created_at')->limit(5)->get(['id', 'name', 'email', 'subject', 'is_read', 'created_at'])->all();
        } catch (\Throwable $e) {
        }

        $trafficLogEntries = [];
        $trafficLogRetentionMinutes = 20;
        try {
            $trafficLogRetentionMinutes = (int) $this->app->getSetting('analytics_log_retention_minutes', '20');
            $trafficLogRetentionMinutes = in_array($trafficLogRetentionMinutes, [10, 20, 30, 60], true) ? $trafficLogRetentionMinutes : 20;
            $trafficLogEntries = \App\Services\AnalyticsLogger::readLastMinutes(30, $trafficLogRetentionMinutes);
        } catch (\Throwable $e) {
        }
        $securityLogEntries = [];
        try {
            $retentionDays = (int) $this->app->getSetting('security_log_retention_days', '7');
            $securityLogEntries = \App\Services\SecurityLogger::read(15, $retentionDays > 0 ? $retentionDays : 7);
        } catch (\Throwable $e) {
        }

        $systemInfo = ['php' => PHP_VERSION, 'db' => 'MySQL'];
        try {
            $v = DB::selectOne('SELECT VERSION() as v');
            $systemInfo['db'] = 'MySQL ' . ($v->v ?? '');
        } catch (\Throwable $e) {
        }

        $systemInfo['dbSize'] = 0;
        try {
            $row = DB::selectOne("SELECT SUM(data_length + index_length) as total FROM information_schema.tables WHERE table_schema = DATABASE()");
            $systemInfo['dbSize'] = $row ? (int) ($row->total ?? 0) : 0;
        } catch (\Throwable $e) {
        }

        $systemInfo['uploadSize'] = 0;
        $uploadRoot = trim(str_replace('\\', '/', (string) \App\Models\Setting::getValue('storage_local_path', 'uploads')), '/');
        $uploadRoot = $uploadRoot !== '' && preg_match('#\.\.#', $uploadRoot) === 0 ? $uploadRoot : 'uploads';
        $uploadDir = $this->app->getBasePath() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $uploadRoot);
        if (is_dir($uploadDir)) {
            try {
                $sz = 0;
                $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadDir, \FilesystemIterator::SKIP_DOTS));
                foreach ($iter as $f) {
                    if ($f->isFile()) {
                        $sz += $f->getSize();
                    }
                }
                $systemInfo['uploadSize'] = $sz;
            } catch (\Throwable $e) {
            }
        }

        $mergeIps = array_merge(
            SecurityLogUserLookup::collectIpsFromAnalyticsEntries($trafficLogEntries),
            SecurityLogUserLookup::collectIpsFromAuditEntries($securityLogEntries)
        );
        $ipLabels = SecurityLogUserLookup::labelsForIps($mergeIps);

        $logLines = [];
        foreach ($trafficLogEntries as $e) {
            $logLines[] = ['ts' => (int) ($e['ts'] ?? 0), 'msg' => \App\Services\AnalyticsLogger::formatMessage($e, $ipLabels)];
        }
        if (empty($logLines) && !empty($securityLogEntries)) {
            foreach (array_slice($securityLogEntries, 0, 15) as $e) {
                $msg = date('H:i:s', (int) ($e['ts'] ?? 0)) . ' — ' . \App\Services\SecurityLogger::eventLabel($e['event'] ?? '') . ' (IP: ' . ($e['ip'] ?? '?') . ')';
                $ip = trim((string) ($e['ip'] ?? $e['client_ip'] ?? ''));
                if ($ip !== '' && isset($ipLabels[$ip])) {
                    $msg .= ' — ' . lang('analytics.ip_resolved_users', ['users' => $ipLabels[$ip]]);
                }
                $logLines[] = [
                    'ts' => (int) ($e['ts'] ?? 0),
                    'msg' => $msg,
                ];
            }
        }
        usort($logLines, fn ($a, $b) => $b['ts'] - $a['ts']);
        $logLines = array_slice($logLines, 0, 25);

        return $this->view('index', [
            'pageTitle' => admin__('menu.dashboard'),
            'user' => $this->app->auth()->user(),
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentTopics' => $recentTopics,
            'recentArticles' => $recentArticles,
            'recentDocPages' => $recentDocPages,
            'recentPosts' => $recentPosts,
            'recentReps' => $recentReps,
            'recentContactMessages' => $recentContactMessages,
            'trafficLogEntries' => $trafficLogEntries,
            'trafficLogRetentionMinutes' => $trafficLogRetentionMinutes,
            'securityLogEntries' => $securityLogEntries,
            'systemInfo' => $systemInfo,
            'logLines' => $logLines,
        ]);
    }
}
