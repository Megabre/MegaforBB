<?php

declare(strict_types=1);

namespace Plugins\AiAutoReply;

use App\Controllers\AdminController;
use App\Models\Forum;
use App\Models\Setting;
use App\Models\User;
use Forecor\Core\Application;
use Illuminate\Database\Capsule\Manager as DB;

final class AdminAiAutoReplyController extends AdminController
{
    private const CSRF_SAVE = 'admin_ai_auto_reply_save';

    public function __construct(Application $app)
    {
        parent::__construct($app);
        if (!$this->jobsTableExists()) {
            $this->app->session()->getFlashBag()->add('admin_error', 'AI Auto Reply eklentisi kurulu degil. Once eklentiyi yeniden etkinlestirin.');
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/plugins'));
        }
    }

    public function index(): string
    {
        $forums = Forum::with('category')->orderBy('category_id')->orderBy('sort_order')->orderBy('id')->get();
        $users = User::query()
            ->with(['role:id,name'])
            ->where('is_banned', 0)
            ->where('is_suspended', 0)
            ->whereNull('closed_at')
            ->orderBy('username')
            ->get(['id', 'username', 'role_id']);
        $savedKey = trim((string) Setting::getValue('ai_auto_reply_openai_api_key', ''));

        return $this->renderPlugin('@AiAutoReply/admin/settings.html.twig', [
            'pageTitle' => 'AI Otomatik Yanit',
            'forums' => $forums,
            'users' => $users,
            'selectedForums' => PluginHooks::selectedForumIds(),
            'settings' => [
                'enabled' => (string) Setting::getValue('ai_auto_reply_enabled', '0') === '1',
                'model' => (string) Setting::getValue('ai_auto_reply_model', 'gpt-4o-mini'),
                'base_url' => (string) Setting::getValue('ai_auto_reply_openai_base_url', 'https://api.openai.com/v1'),
                'bot_user_id' => (int) Setting::getValue('ai_auto_reply_bot_user_id', '1'),
                'max_chars' => (int) Setting::getValue('ai_auto_reply_max_chars', '1200'),
                'max_per_minute' => (int) Setting::getValue('ai_auto_reply_max_per_minute', '3'),
                'daily_quota' => (int) Setting::getValue('ai_auto_reply_daily_quota', '100'),
                'max_replies_per_topic' => (int) Setting::getValue('ai_auto_reply_max_replies_per_topic', '3'),
                'max_mention_replies_per_topic' => (int) Setting::getValue('ai_auto_reply_max_mention_replies_per_topic', '8'),
                'prompt' => (string) Setting::getValue('ai_auto_reply_prompt', ''),
                'has_api_key' => $savedKey !== '',
            ],
        ]);
    }

    public function save(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_SAVE, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url($adminPath . '/ai-auto-reply'));
            return;
        }

        $enabled = isset($_POST['enabled']) && (string) $_POST['enabled'] === '1' ? '1' : '0';
        $baseUrl = trim((string) ($_POST['base_url'] ?? 'https://api.openai.com/v1'));
        $model = trim((string) ($_POST['model'] ?? 'gpt-4o-mini'));
        $botUserId = max(1, (int) ($_POST['bot_user_id'] ?? 1));
        $botUserExists = User::query()
            ->where('id', $botUserId)
            ->where('is_banned', 0)
            ->where('is_suspended', 0)
            ->whereNull('closed_at')
            ->exists();
        if (!$botUserExists) {
            $botUserId = 1;
        }
        $maxChars = max(300, min(4000, (int) ($_POST['max_chars'] ?? 1200)));
        $maxPerMinute = max(1, min(50, (int) ($_POST['max_per_minute'] ?? 3)));
        $dailyQuota = max(1, min(5000, (int) ($_POST['daily_quota'] ?? 100)));
        $maxRepliesPerTopic = max(1, min(20, (int) ($_POST['max_replies_per_topic'] ?? 3)));
        $maxMentionRepliesPerTopic = max($maxRepliesPerTopic, min(100, (int) ($_POST['max_mention_replies_per_topic'] ?? 8)));
        $prompt = trim((string) ($_POST['prompt'] ?? ''));
        if ($prompt !== '' && mb_strlen($prompt) > 5000) {
            $prompt = mb_substr($prompt, 0, 5000);
        }

        $forumIds = $_POST['forum_ids'] ?? [];
        if (!is_array($forumIds)) {
            $forumIds = [];
        }
        $forumIds = array_values(array_unique(array_map('intval', $forumIds)));
        $validForumIds = Forum::query()->whereIn('id', $forumIds)->pluck('id')->map(static fn ($v) => (int) $v)->all();

        Setting::setValue('ai_auto_reply_enabled', $enabled, 'forum');
        Setting::setValue('ai_auto_reply_openai_base_url', $baseUrl !== '' ? $baseUrl : 'https://api.openai.com/v1', 'forum');
        Setting::setValue('ai_auto_reply_model', $model !== '' ? $model : 'gpt-4o-mini', 'forum');
        Setting::setValue('ai_auto_reply_bot_user_id', (string) $botUserId, 'forum');
        Setting::setValue('ai_auto_reply_max_chars', (string) $maxChars, 'forum');
        Setting::setValue('ai_auto_reply_max_per_minute', (string) $maxPerMinute, 'forum');
        Setting::setValue('ai_auto_reply_daily_quota', (string) $dailyQuota, 'forum');
        Setting::setValue('ai_auto_reply_max_replies_per_topic', (string) $maxRepliesPerTopic, 'forum');
        Setting::setValue('ai_auto_reply_max_mention_replies_per_topic', (string) $maxMentionRepliesPerTopic, 'forum');
        Setting::setValue('ai_auto_reply_prompt', $prompt, 'forum');
        Setting::setValue('ai_auto_reply_forum_ids', json_encode($validForumIds, JSON_UNESCAPED_UNICODE), 'forum');

        $apiKey = trim((string) ($_POST['openai_api_key'] ?? ''));
        if ($apiKey !== '') {
            Setting::setValue('ai_auto_reply_openai_api_key', $apiKey, 'forum');
        }

        $this->app->session()->getFlashBag()->add('admin_success', 'AI Auto Reply ayarlari kaydedildi.');
        $this->redirect(core_url($adminPath . '/ai-auto-reply'));
    }

    private function jobsTableExists(): bool
    {
        try {
            return DB::schema()->hasTable('ai_auto_reply_jobs');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderPlugin(string $twigName, array $data): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $data['pageTitle'] = $data['pageTitle'] ?? 'Admin';
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

        return $this->app->twig('admin')->render($twigName, $data);
    }
}
