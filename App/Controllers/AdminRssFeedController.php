<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Forum;
use App\Models\Prefix;
use App\Models\RssFeedSource;
use App\Models\User;
use App\Services\Rss\RssFeedImportRunner;
use App\Services\Rss\RssFeedParserService;
use App\Services\Rss\RssHttpFetchService;
use App\Services\TopicPrefixScopeService;
use Illuminate\Support\Collection;

class AdminRssFeedController extends AdminController
{
    private const CSRF_STORE = 'admin_rss_feed_store';

    private const CSRF_UPDATE = 'admin_rss_feed_update';

    private const CSRF_DELETE = 'admin_rss_feed_delete';

    private const CSRF_IMPORT = 'admin_rss_feed_import';

    public function index(): string
    {
        $feeds = RssFeedSource::query()
            ->with(['forum:id,name', 'user:id,username'])
            ->orderByDesc('id')
            ->get();

        return $this->view('rss_feeds/index', [
            'pageTitle' => lang('admin.rss_feeds.title'),
            'feeds' => $feeds,
        ]);
    }

    public function create(): string
    {
        $forums = Forum::with('category')->orderBy('category_id')->orderBy('sort_order')->orderBy('id')->get();

        return $this->view('rss_feeds/form', [
            'pageTitle' => lang('admin.rss_feeds.add'),
            'feed' => null,
            'forums' => $forums,
            'forum_prefixes_json' => $this->forumPrefixesOptionsJson($forums),
            'rssAuthorUsers' => $this->rssAuthorUsersForForm(null),
            'rssCurrentAuthorNotEligible' => false,
        ]);
    }

    public function store(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_STORE, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url($adminPath . '/rss-feeds'));
            return;
        }

        $row = $this->validatedRowFromPost(true);
        if ($row['error'] !== '') {
            $this->app->session()->getFlashBag()->add('admin_error', $row['error']);
            $this->redirect(core_url($adminPath . '/rss-feeds/create'));
            return;
        }

        RssFeedSource::create($row['data']);
        $this->app->session()->getFlashBag()->add('admin_success', lang('admin.rss_feeds.saved'));
        $this->redirect(core_url($adminPath . '/rss-feeds'));
    }

    public function edit(int $id): string
    {
        $feed = RssFeedSource::find((int) $id);
        if (!$feed) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rss-feeds'));
            return '';
        }

        $forums = Forum::with('category')->orderBy('category_id')->orderBy('sort_order')->orderBy('id')->get();

        return $this->view('rss_feeds/form', [
            'pageTitle' => lang('admin.rss_feeds.edit'),
            'feed' => $feed,
            'forums' => $forums,
            'forum_prefixes_json' => $this->forumPrefixesOptionsJson($forums),
            'rssAuthorUsers' => $this->rssAuthorUsersForForm($feed),
            'rssCurrentAuthorNotEligible' => !$this->isUserEligibleRssAuthor((int) $feed->user_id),
        ]);
    }

    public function update(int $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_UPDATE, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url($adminPath . '/rss-feeds'));
            return;
        }

        $feed = RssFeedSource::find((int) $id);
        if (!$feed) {
            $this->redirect(core_url($adminPath . '/rss-feeds'));
            return;
        }

        $row = $this->validatedRowFromPost(false);
        if ($row['error'] !== '') {
            $this->app->session()->getFlashBag()->add('admin_error', $row['error']);
            $this->redirect(core_url($adminPath . '/rss-feeds/edit/' . $id));
            return;
        }

        $feed->fill($row['data']);
        $feed->save();
        $this->app->session()->getFlashBag()->add('admin_success', lang('admin.rss_feeds.updated'));
        $this->redirect(core_url($adminPath . '/rss-feeds'));
    }

    public function delete(int $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_DELETE, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url($adminPath . '/rss-feeds'));
            return;
        }
        $feed = RssFeedSource::find((int) $id);
        if ($feed) {
            $feed->delete();
        }
        $this->redirect(core_url($adminPath . '/rss-feeds'));
    }

    /** POST: Bu kaynak için hemen içe aktar (bekleyen tüm yeni öğeler, üst sınıra kadar). */
    public function importNow(int $id): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        if (!core_csrf_valid(self::CSRF_IMPORT, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url($adminPath . '/rss-feeds'));
            return;
        }
        $feed = RssFeedSource::find((int) $id);
        if (!$feed) {
            $this->redirect(core_url($adminPath . '/rss-feeds'));
            return;
        }
        $runner = new RssFeedImportRunner($this->app);
        $n = $runner->processSource($feed);
        $this->app->session()->getFlashBag()->add('admin_success', lang('admin.rss_feeds.import_done', ['count' => $n]));
        $this->redirect(core_url($adminPath . '/rss-feeds'));
    }

    /** GET: URL doğrulama ve örnek başlık/gövde (önizleme). */
    public function preview(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $url = trim((string) ($_GET['url'] ?? ''));
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            echo json_encode(['ok' => false, 'message' => lang('admin.rss_feeds.invalid_url')]);
            return;
        }
        $raw = (new RssHttpFetchService())->get($url, 15);
        if ($raw === null) {
            echo json_encode(['ok' => false, 'message' => lang('admin.rss_feeds.fetch_failed')]);
            return;
        }
        $data = (new RssFeedParserService())->parse($raw);
        if ($data === null || empty($data['entries'])) {
            echo json_encode(['ok' => false, 'message' => lang('admin.rss_feeds.parse_failed')]);
            return;
        }
        $e = $data['entries'][0];
        echo json_encode([
            'ok' => true,
            'feed_title' => $data['title'] ?? '',
            'sample_title' => $e['title'] ?? '',
            'sample_link' => $e['link'] ?? '',
            'sample_plain' => mb_substr(strip_tags((string) ($e['content'] ?? '')), 0, 400),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{error:string,data:array<string,mixed>}
     */
    private function validatedRowFromPost(bool $isNew): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $url = trim((string) ($_POST['url'] ?? ''));
        $forumId = (int) ($_POST['forum_id'] ?? 0);
        $userId = (int) ($_POST['user_id'] ?? 0);
        $prefixId = (int) ($_POST['prefix_id'] ?? 0);
        if ($prefixId < 0) {
            $prefixId = 0;
        }
        $freq = (int) ($_POST['frequency_minutes'] ?? 60);
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;
        $titleTpl = trim((string) ($_POST['title_template'] ?? '{title}'));
        $bodyTpl = trim((string) ($_POST['body_template'] ?? ''));

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            return ['error' => lang('admin.rss_feeds.invalid_url'), 'data' => []];
        }
        $forumModel = Forum::find($forumId);
        if ($forumId <= 0 || !$forumModel) {
            return ['error' => lang('admin.rss_feeds.invalid_forum'), 'data' => []];
        }
        if ($prefixId > 0 && !TopicPrefixScopeService::isPrefixAllowedForForum($forumModel, $prefixId)) {
            $prefixId = 0;
        }
        if ($userId <= 0 || !$this->isUserEligibleRssAuthor($userId)) {
            return ['error' => lang('admin.rss_feeds.invalid_user'), 'data' => []];
        }
        $freq = max(5, min(10080, $freq));
        if ($titleTpl === '') {
            $titleTpl = '{title}';
        }
        if (mb_strlen($titleTpl) > 500) {
            $titleTpl = mb_substr($titleTpl, 0, 500);
        }

        $http = new RssHttpFetchService();
        $parser = new RssFeedParserService();
        $raw = $http->get($url, 20);
        if ($raw === null) {
            return ['error' => lang('admin.rss_feeds.fetch_failed'), 'data' => []];
        }
        $parsed = $parser->parse($raw);
        if ($parsed === null || empty($parsed['entries'])) {
            return ['error' => lang('admin.rss_feeds.parse_failed'), 'data' => []];
        }

        if ($title === '' && ($parsed['title'] ?? '') !== '') {
            $title = mb_substr((string) $parsed['title'], 0, 255);
        }
        if ($title === '') {
            $title = mb_substr($url, 0, 255);
        }

        $data = [
            'title' => $title,
            'url' => $url,
            'forum_id' => $forumId,
            'user_id' => $userId,
            'prefix_id' => $prefixId,
            'frequency_minutes' => $freq,
            'is_active' => $isActive,
            'title_template' => $titleTpl,
            'body_template' => $bodyTpl !== '' ? $bodyTpl : null,
        ];

        if ($isNew) {
            $data['last_fetch_at'] = null;
            $data['last_success_at'] = null;
            $data['last_error'] = null;
        }

        return ['error' => '', 'data' => $data];
    }

    /**
     * RSS ile açılacak konular için seçilebilir üyeler: banlı, askıda veya hesabı kapanmış üyeler dahil edilmez.
     *
     * @return Collection<int, User>
     */
    private function rssAuthorUsersForForm(?RssFeedSource $feed): Collection
    {
        $users = User::query()
            ->with(['role:id,name'])
            ->where('is_banned', 0)
            ->where('is_suspended', 0)
            ->whereNull('closed_at')
            ->orderBy('username')
            ->get(['id', 'username', 'role_id']);

        if ($feed !== null && (int) $feed->user_id > 0 && !$users->contains('id', (int) $feed->user_id)) {
            $current = User::query()
                ->with(['role:id,name'])
                ->where('id', (int) $feed->user_id)
                ->first(['id', 'username', 'role_id']);
            if ($current) {
                $users = $users->prepend($current);
            }
        }

        return $users;
    }

    private function isUserEligibleRssAuthor(int $userId): bool
    {
        return User::query()
            ->where('id', $userId)
            ->where('is_banned', 0)
            ->where('is_suspended', 0)
            ->whereNull('closed_at')
            ->exists();
    }

    /**
     * Her forum için hedef kapsamda izinli önekler (sıralı); JSON olarak şablona verilir.
     */
    private function forumPrefixesOptionsJson(Collection $forums): string
    {
        $map = [];
        foreach ($forums as $fo) {
            if (!$fo instanceof Forum) {
                continue;
            }
            $fid = (int) $fo->id;
            $map[(string) $fid] = TopicPrefixScopeService::prefixesForForum($fo)
                ->map(static function (Prefix $p): array {
                    return [
                        'id' => (int) $p->id,
                        'name' => (string) $p->name,
                    ];
                })
                ->values()
                ->all();
        }

        return json_encode(
            $map,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
        );
    }
}
