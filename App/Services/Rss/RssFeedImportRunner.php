<?php

declare(strict_types=1);

namespace App\Services\Rss;

use App\Models\Forum;
use App\Models\RssFeedImportLog;
use App\Models\RssFeedSource;
use App\Models\User;
use App\Services\TopicPrefixScopeService;
use App\Services\TopicService;
use Forecor\Core\Application;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * XenForo XF\Service\Feed\Feeder ile aynı rol: çek → yeni öğeleri süz → konu aç → günlük tut.
 */
class RssFeedImportRunner
{
    private const MAX_NEW_PER_FEED = 30;

    public function __construct(
        private readonly Application $app,
        private readonly RssHttpFetchService $http = new RssHttpFetchService(),
        private readonly RssFeedParserService $parser = new RssFeedParserService(),
    ) {
    }

    /**
     * Zamanı gelen tüm aktif kaynakları işler (cron).
     *
     * @return array{processed:int,imported:int,errors:array<int,string>}
     */
    public function runDueFeeds(): array
    {
        $errors = [];
        $processed = 0;
        $imported = 0;
        $now = \now();

        $sources = RssFeedSource::query()
            ->where('is_active', 1)
            ->orderBy('id')
            ->get();

        foreach ($sources as $source) {
            $freq = max(1, (int) $source->frequency_minutes);
            if ($source->last_fetch_at !== null) {
                $next = $source->last_fetch_at->copy()->addMinutes($freq);
                if ($next->isFuture()) {
                    continue;
                }
            }

            $processed++;
            try {
                $imported += $this->processSource($source);
            } catch (\Throwable $e) {
                $errors[] = 'RSS #' . (int) $source->id . ': ' . $e->getMessage();
                $source->last_error = mb_substr($e->getMessage(), 0, 500);
                $source->last_fetch_at = $now;
                $source->save();
            }
        }

        return ['processed' => $processed, 'imported' => $imported, 'errors' => $errors];
    }

    /**
     * Tek kaynak — manuel içe aktarma veya cron.
     *
     * @return int içe aktarılan yeni konu sayısı
     */
    public function processSource(RssFeedSource $source): int
    {
        $now = \now();
        $raw = $this->http->get($source->url, 25);
        if ($raw === null) {
            $source->last_error = 'RSS içeriği alınamadı veya HTTP hatası.';
            $source->last_fetch_at = $now;
            $source->save();

            return 0;
        }

        $data = $this->parser->parse($raw);
        if ($data === null || empty($data['entries'])) {
            $source->last_error = $data === null ? 'RSS/Atom ayrıştırılamadı.' : 'Akışta öğe yok.';
            $source->last_fetch_at = $now;
            $source->save();

            return 0;
        }

        $forum = Forum::find((int) $source->forum_id);
        $user = User::find((int) $source->user_id);
        if (!$forum || !$user) {
            $source->last_error = function_exists('lang')
                ? lang('admin.rss_feeds.err_forum_or_user_missing')
                : 'RSS: forum or user missing.';
            $source->last_fetch_at = $now;
            $source->save();

            return 0;
        }
        if ((int) ($user->is_banned ?? 0) === 1 || (int) ($user->is_suspended ?? 0) === 1 || $user->closed_at !== null) {
            $source->last_error = function_exists('lang')
                ? lang('admin.rss_feeds.err_author_ineligible')
                : 'RSS: topic author is banned, suspended, or closed.';
            $source->last_fetch_at = $now;
            $source->save();

            return 0;
        }
        if (!$user->hasPermission('forum.create_thread', $forum)) {
            $source->last_error = function_exists('lang')
                ? lang('admin.rss_feeds.err_no_create_permission')
                : 'RSS: user cannot create threads in target forum.';
            $source->last_fetch_at = $now;
            $source->save();

            return 0;
        }

        $ids = [];
        foreach ($data['entries'] as $e) {
            $ids[$e['id']] = true;
        }
        $idList = array_keys($ids);
        if ($idList === []) {
            $source->last_fetch_at = $now;
            $source->last_success_at = $now;
            $source->last_error = null;
            $source->save();

            return 0;
        }

        $existing = RssFeedImportLog::query()
            ->where('rss_feed_source_id', (int) $source->id)
            ->whereIn('unique_entry_id', $idList)
            ->pluck('unique_entry_id')
            ->all();
        $existingSet = array_fill_keys($existing, true);

        $pending = array_values(array_filter($data['entries'], static function (array $e) use ($existingSet): bool {
            return !isset($existingSet[$e['id']]);
        }));
        $pending = array_reverse($pending);

        $count = 0;
        $topicService = core_make(TopicService::class, null, $this->app);
        $maxTitle = (int) (DB::table('settings')->where('key', 'max_topic_title_length')->value('value') ?? 200);
        $maxPost = (int) (DB::table('settings')->where('key', 'max_post_length')->value('value') ?? 65535);
        if ($maxTitle < 10) {
            $maxTitle = 200;
        }
        if ($maxPost < 100) {
            $maxPost = 65535;
        }

        $pfx = (int) $source->prefix_id;
        if ($pfx > 0 && !TopicPrefixScopeService::isPrefixAllowedForForum($forum, $pfx)) {
            $pfx = 0;
        }

        $titleTpl = trim((string) $source->title_template) !== '' ? (string) $source->title_template : '{title}';
        $bodyTpl = trim((string) ($source->body_template ?? '')) !== ''
            ? (string) $source->body_template
            : "{content}\n\n[b]Kaynak:[/b] [url={link}]{link}[/url]";

        foreach ($pending as $entry) {
            if ($count >= self::MAX_NEW_PER_FEED) {
                break;
            }
            $title = $this->applyTemplate($titleTpl, $entry);
            $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($title === '') {
                continue;
            }
            $title = mb_substr($title, 0, $maxTitle);

            $body = $this->applyTemplate($bodyTpl, $entry);
            $body = $this->htmlToBbishPlain($body, $maxPost);

            try {
                $topic = $topicService->createTopic(
                    $forum,
                    $user,
                    $title,
                    $body,
                    'topic',
                    false,
                    false,
                    false,
                    null,
                    $pfx,
                    [],
                    [],
                    [],
                    []
                );
                RssFeedImportLog::create([
                    'rss_feed_source_id' => (int) $source->id,
                    'unique_entry_id' => $entry['id'],
                    'topic_id' => (int) $topic->id,
                    'created_at' => $now,
                ]);
                $count++;
            } catch (\Throwable) {
                // Tek öğe hatası — diğerlerine devam
            }
        }

        $source->last_fetch_at = $now;
        $source->last_success_at = $now;
        $source->last_error = null;
        if ($source->title === '' && ($data['title'] ?? '') !== '') {
            $source->title = mb_substr((string) $data['title'], 0, 255);
        }
        $source->save();

        return $count;
    }

    /**
     * @param array{id:string,title:string,link:string,content:string,author:string} $entry
     */
    private function applyTemplate(string $template, array $entry): string
    {
        $plainContent = $this->htmlToPlainSnippet((string) $entry['content'], 20000);
        $repl = [
            '{title}' => (string) $entry['title'],
            '{link}' => (string) $entry['link'],
            '{content}' => $plainContent,
            '{author}' => (string) $entry['author'],
        ];

        return str_replace(array_keys($repl), array_values($repl), $template);
    }

    private function htmlToPlainSnippet(string $html, int $maxLen): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if (mb_strlen($t) > $maxLen) {
            return mb_substr($t, 0, $maxLen) . '…';
        }

        return $t;
    }

    private function htmlToBbishPlain(string $body, int $maxLen): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }
        if (mb_strlen($body) > $maxLen) {
            $body = mb_substr($body, 0, $maxLen) . '…';
        }

        return $body;
    }
}
