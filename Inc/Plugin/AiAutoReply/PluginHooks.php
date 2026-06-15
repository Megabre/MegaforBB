<?php

declare(strict_types=1);

namespace Plugins\AiAutoReply;

use App\Models\Post;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\User;
use App\Services\TopicService;
use Forecor\Core\Application;
use Illuminate\Database\Capsule\Manager as DB;

final class PluginHooks
{
    private static bool $queueSchemaChecked = false;
    private const STATUS_PENDING = 'pending';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_DONE = 'done';
    private const STATUS_FAILED = 'failed';

    /**
     * @param array<string, string|int|float> $lines
     * @return array<string, string|int|float>
     */
    public static function translatorLines(array $lines, string $locale): array
    {
        $extra = [
            'plugin.ai_auto_reply.menu' => $locale === 'en' ? 'AI auto reply' : 'AI otomatik yanit',
        ];
        return array_merge($lines, $extra);
    }

    /**
     * @param list<array<string, mixed>> $navGroups
     * @return list<array<string, mixed>>
     */
    public static function adminMenu(array $navGroups): array
    {
        $adminPath = function_exists('env') ? (string) env('ADMIN_PATH', 'admin') : 'admin';
        foreach ($navGroups as &$group) {
            if (($group['id'] ?? '') !== 'forum') {
                continue;
            }
            $children = $group['children'] ?? [];
            $children[] = [
                'icon' => 'ti-sparkles',
                'label' => lang('plugin.ai_auto_reply.menu'),
                'url' => core_url($adminPath . '/ai-auto-reply'),
                'match' => '/' . $adminPath . '/ai-auto-reply',
            ];
            $group['children'] = $children;
        }
        unset($group);

        return $navGroups;
    }

    public static function enqueueTopicReply(Topic $topic, $forum): void
    {
        self::ensureQueueSchema();
        if (!self::isEnabled()) {
            return;
        }
        if (!self::hasApiKey()) {
            return;
        }
        if (!self::isForumAllowed((int) ($topic->forum_id ?? 0))) {
            return;
        }
        if (!self::isTopicFresh($topic)) {
            return;
        }
        if (self::topicReplyLimitReached((int) ($topic->id ?? 0), 'topic')) {
            return;
        }

        try {
            $queued = false;
            if (DB::table('ai_auto_reply_jobs')
                ->where('topic_id', (int) $topic->id)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_DONE])
                ->exists()) {
                self::triggerAsyncWorker();
                return;
            }

            DB::table('ai_auto_reply_jobs')->insert([
                'topic_id' => (int) $topic->id,
                'forum_id' => (int) ($topic->forum_id ?? 0),
                'trigger_post_id' => null,
                'trigger_type' => 'topic',
                'status' => self::STATUS_PENDING,
                'attempts' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $queued = true;
            if ($queued) {
                self::triggerAsyncWorker();
            }
        } catch (\Throwable $e) {
            // Eklenti hatasi konu olusturma akisini bozmamali.
        }
    }

    public static function enqueuePostReply(Post $post, Topic $topic, User $author): void
    {
        self::ensureQueueSchema();
        if (!self::isEnabled() || !self::hasApiKey()) {
            self::debug('enqueuePostReply.skip', ['reason' => 'disabled_or_no_key']);
            return;
        }
        if (!self::isForumAllowed((int) ($topic->forum_id ?? 0))) {
            self::debug('enqueuePostReply.skip', ['reason' => 'forum_not_allowed', 'forum_id' => (int) ($topic->forum_id ?? 0)]);
            return;
        }
        if (!self::isPostFresh($post)) {
            self::debug('enqueuePostReply.skip', ['reason' => 'post_not_fresh', 'post_id' => (int) ($post->id ?? 0)]);
            return;
        }

        $botUserId = (int) self::setting('ai_auto_reply_bot_user_id', '1');
        if ((int) ($author->id ?? 0) === $botUserId) {
            self::debug('enqueuePostReply.skip', ['reason' => 'author_is_bot', 'post_id' => (int) ($post->id ?? 0)]);
            return;
        }

        $triggerType = self::detectPostTriggerType($post, $botUserId);
        if ($triggerType === null) {
            self::debug('enqueuePostReply.skip', ['reason' => 'no_trigger_detected', 'post_id' => (int) ($post->id ?? 0)]);
            return;
        }
        if (self::topicReplyLimitReached((int) ($topic->id ?? 0), $triggerType)) {
            self::debug('enqueuePostReply.skip', ['reason' => 'topic_limit_reached', 'topic_id' => (int) ($topic->id ?? 0), 'trigger_type' => $triggerType]);
            return;
        }

        try {
            $postId = (int) ($post->id ?? 0);
            if ($postId <= 0) {
                return;
            }
            if (DB::table('ai_auto_reply_jobs')
                ->where('trigger_post_id', $postId)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_DONE])
                ->exists()) {
                self::triggerAsyncWorker();
                self::debug('enqueuePostReply.skip', ['reason' => 'duplicate_trigger_post', 'post_id' => $postId]);
                return;
            }

            DB::table('ai_auto_reply_jobs')->insert([
                'topic_id' => (int) ($topic->id ?? 0),
                'forum_id' => (int) ($topic->forum_id ?? 0),
                'trigger_post_id' => $postId,
                'trigger_type' => $triggerType,
                'status' => self::STATUS_PENDING,
                'attempts' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            self::debug('enqueuePostReply.queued', ['post_id' => $postId, 'topic_id' => (int) ($topic->id ?? 0), 'trigger_type' => $triggerType]);
            self::triggerAsyncWorker();
        } catch (\Throwable $e) {
            // Eklenti hatasi akisi bozmamali.
            self::debug('enqueuePostReply.error', ['message' => $e->getMessage()]);
        }
    }

    public static function isValidWorkerToken(string $token): bool
    {
        try {
            return hash_equals(self::workerToken(), $token);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array{processed:int,replied:int,failed:int,errors:list<string>}
     */
    public static function runWorkerNow(Application $app): array
    {
        if (!self::isEnabled()) {
            return ['processed' => 0, 'replied' => 0, 'failed' => 0, 'errors' => ['Pasif']];
        }
        if (!self::hasApiKey()) {
            return ['processed' => 0, 'replied' => 0, 'failed' => 0, 'errors' => ['API key yok']];
        }
        $limit = max(1, min(20, (int) self::setting('ai_auto_reply_jobs_per_run', '5')));
        return self::processPendingJobs($app, $limit);
    }

    /**
     * @return array{processed:int,replied:int,failed:int,errors:list<string>}
     */
    public static function processPendingJobs(Application $app, int $limit): array
    {
        self::ensureQueueSchema();
        if (!self::underGlobalQuota()) {
            return [
                'processed' => 0,
                'replied' => 0,
                'failed' => 0,
                'errors' => ['Kota/limit dolu, worker sonraki tetiklemeyi bekliyor.'],
            ];
        }

        $cutoff = date('Y-m-d H:i:s', time() - (self::maxTopicAgeMinutes() * 60));
        DB::table('ai_auto_reply_jobs')
            ->where('status', self::STATUS_PENDING)
            ->where('created_at', '<', $cutoff)
            ->update([
                'status' => self::STATUS_FAILED,
                'last_error' => 'Stale job: konu cok eski',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $jobs = DB::table('ai_auto_reply_jobs')
            ->where('status', self::STATUS_PENDING)
            ->where('created_at', '>=', $cutoff)
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        $processed = 0;
        $replied = 0;
        $failed = 0;
        $errors = [];

        foreach ($jobs as $job) {
            $processed++;
            $jobId = (int) ($job->id ?? 0);
            if ($jobId <= 0) {
                continue;
            }

            DB::table('ai_auto_reply_jobs')
                ->where('id', $jobId)
                ->update([
                    'status' => self::STATUS_PROCESSING,
                    'attempts' => DB::raw('attempts + 1'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            try {
                $ok = self::runSingleJob(
                    $app,
                    $jobId,
                    (int) ($job->topic_id ?? 0),
                    (int) ($job->trigger_post_id ?? 0),
                    (string) ($job->trigger_type ?? 'topic')
                );
                if ($ok) {
                    $replied++;
                } else {
                    $failed++;
                    $errors[] = 'job#' . $jobId . ': yanit olusturulamadi';
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = 'job#' . $jobId . ': ' . $e->getMessage();
                DB::table('ai_auto_reply_jobs')->where('id', $jobId)->update([
                    'status' => self::STATUS_FAILED,
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'replied' => $replied,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    private static function triggerAsyncWorker(): void
    {
        $token = self::workerToken();
        $url = self::workerUrl($token);
        if ($url === '') {
            return;
        }

        try {
            if (!function_exists('curl_init')) {
                return;
            }
            $ch = curl_init($url);
            if ($ch === false) {
                return;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_NOSIGNAL => true,
                CURLOPT_TIMEOUT_MS => 800,
                CURLOPT_CONNECTTIMEOUT_MS => 200,
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE => true,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            // Fire-and-forget; hata yutulur.
        }
    }

    private static function runSingleJob(Application $app, int $jobId, int $topicId, int $triggerPostId = 0, string $triggerType = 'topic'): bool
    {
        $topic = Topic::with('forum')->where('id', $topicId)->first();
        if (!$topic) {
            self::finishAsFailed($jobId, 'Konu bulunamadi');
            return false;
        }
        if (!self::isForumAllowed((int) ($topic->forum_id ?? 0))) {
            self::finishAsFailed($jobId, 'Forum AI icin kapali');
            return false;
        }
        $isConversationTrigger = ($triggerType === 'mention' || $triggerType === 'quote');
        if (!$isConversationTrigger && !self::isTopicFresh($topic)) {
            self::finishAsFailed($jobId, 'Konu eski oldugu icin atlandi');
            return false;
        }
        if (self::topicReplyLimitReached((int) ($topic->id ?? 0), $triggerType)) {
            self::finishAsFailed($jobId, 'Konu basina AI yanit limiti dolu');
            return false;
        }
        if (!self::underGlobalQuota()) {
            self::finishAsFailed($jobId, 'Kota/limit asildi');
            return false;
        }

        $botUserId = (int) self::setting('ai_auto_reply_bot_user_id', '1');
        $botUser = User::where('id', $botUserId)->first();
        if (!$botUser) {
            self::finishAsFailed($jobId, 'AI bot kullanicisi bulunamadi');
            return false;
        }

        $alreadyReplied = Post::where('topic_id', (int) $topic->id)
            ->where('user_id', (int) $botUser->id)
            ->exists();
        // Ilk konu tetiklemesinde bir kez yanit yeterli; mention/quote akisinda devam etmeli.
        if ($alreadyReplied && $triggerType === 'topic') {
            self::finishAsDone($jobId);
            return true;
        }

        $firstPost = Post::where('topic_id', (int) $topic->id)->where('is_first_post', 1)->orderBy('id')->first();
        $triggerPost = $triggerPostId > 0 ? Post::where('id', $triggerPostId)->where('topic_id', (int) $topic->id)->first() : null;
        $content = trim((string) ($triggerPost->body ?? $firstPost->body ?? ''));
        if ($content === '') {
            self::finishAsFailed($jobId, 'Konu icerigi bos');
            return false;
        }

        $triggerAuthorUsername = '';
        if ($triggerPost !== null) {
            $triggerAuthorUsername = (string) (User::query()->where('id', (int) ($triggerPost->user_id ?? 0))->value('username') ?? '');
        }

        $prompt = self::buildPrompt(
            $topic->title ?? '',
            (string) ($topic->forum->name ?? ''),
            $content,
            trim((string) ($firstPost->body ?? '')),
            $triggerType,
            (string) ($botUser->username ?? ''),
            $triggerAuthorUsername
        );
        $similarTopics = self::findSimilarTopics(
            (int) $topic->id,
            (int) ($topic->forum_id ?? 0),
            (string) ($topic->title ?? ''),
            trim((string) ($firstPost->body ?? '')),
            $content,
            3
        );
        if (!empty($similarTopics)) {
            $prompt .= "\n\nBenzer konular (forumdan bulundu):\n" . self::formatSimilarTopicsForPrompt($similarTopics);
        }
        $reply = self::callOpenAi($prompt);
        $reply = self::normalizeReply($reply);
        $reply = self::sanitizeSelfMention($reply, (string) ($botUser->username ?? ''), $triggerAuthorUsername);
        if (!empty($similarTopics)) {
            $reply = self::appendSimilarTopicsToReply($reply, $similarTopics);
        }
        if ($reply === '') {
            self::finishAsFailed($jobId, 'Model bos cevap dondu');
            return false;
        }

        $maxChars = max(300, (int) self::setting('ai_auto_reply_max_chars', '1200'));
        if (mb_strlen($reply) > $maxChars) {
            $reply = mb_substr($reply, 0, $maxChars) . '...';
        }

        $body = $reply;
        $bodyHtml = function_exists('core_body_to_html') ? core_body_to_html($body) : nl2br(htmlspecialchars($body));
        $bodyHtml = function_exists('core_process_mentions') ? core_process_mentions($bodyHtml) : $bodyHtml;
        $bodyHtml = function_exists('core_process_post_refs') ? core_process_post_refs($bodyHtml, (int) $topic->id) : $bodyHtml;

        $topicService = new TopicService(null, $app);
        $topicService->replyTopic($topic, $botUser, $body, $bodyHtml, null);

        self::finishAsDone($jobId);
        return true;
    }

    private static function buildPrompt(
        string $title,
        string $forumName,
        string $body,
        string $topicOpeningBody,
        string $triggerType,
        string $botUsername,
        string $triggerAuthorUsername
    ): string
    {
        $customPrompt = trim((string) self::setting('ai_auto_reply_prompt', ''));
        $defaultInstruction = implode("\n", [
            'Sen forumda teknik yardim veren bir asistansin.',
            'Yalnizca verilen baslik ve icerige dayan; uydurma yapma.',
            'Cevap bot gibi degil, dogal ve net olsun.',
            '3-6 cumle kullan; gereksiz giris ve tekrar yapma.',
            'Konu bir duyuru/changelog/release notu ise:',
            '- 2-4 kisa maddeyle ne degistigini ozetle.',
            '- "ne denedin" gibi alakasiz sorular sorma.',
            '- Gerekirse en sonda tek satirlik etkisini belirt (or. guncelleme onerilir).',
            'Konu bir soruysa dogrudan cozum oner; sadece gerekliyse en fazla 1 net soru sor.',
            'Asla "daha fazla detay verir misiniz?" gibi genel bosluk dolduran cumleler kurma.',
            'Reklamvari, asiri genel veya ezber cevaplar yazma.',
        ]);
        $instruction = $customPrompt !== '' ? $customPrompt : $defaultInstruction;

        return $instruction . "\n\n"
            . "Tetikleme Turu: " . $triggerType . "\n"
            . "AI Kullanici Adi: " . $botUsername . "\n"
            . "Mesaji Yazan Kullanici: " . $triggerAuthorUsername . "\n"
            . "Forum: " . $forumName . "\n"
            . "Konu Basligi: " . $title . "\n"
            . "Konu Acilis Mesaji:\n" . $topicOpeningBody . "\n\n"
            . "Yanit Verilecek Mesaj:\n" . $body;
    }

    private static function callOpenAi(string $prompt): string
    {
        $apiKey = trim((string) self::setting('ai_auto_reply_openai_api_key', ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key bos');
        }

        $baseUrl = rtrim((string) self::setting('ai_auto_reply_openai_base_url', 'https://api.openai.com/v1'), '/');
        $model = trim((string) self::setting('ai_auto_reply_model', 'gpt-4o-mini'));
        if ($model === '') {
            $model = 'gpt-4o-mini';
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'Cevaplari Turkce ver. Topluluk kurallarina uygun yaz.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.25,
            'max_tokens' => 400,
        ];

        $ch = curl_init($baseUrl . '/chat/completions');
        if ($ch === false) {
            throw new \RuntimeException('cURL baslatilamadi');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 25,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!is_string($response) || $response === '') {
            throw new \RuntimeException('API bos yanit verdi: ' . $curlError);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('API hata kodu: ' . $httpCode . ' yanit: ' . mb_substr($response, 0, 250));
        }

        $decoded = json_decode($response, true);
        $text = (string) ($decoded['choices'][0]['message']['content'] ?? '');
        return $text;
    }

    private static function normalizeReply(string $text): string
    {
        $text = str_replace("\r\n", "\n", trim($text));
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
    }

    private static function sanitizeSelfMention(string $reply, string $botUsername, string $targetUsername): string
    {
        $reply = trim($reply);
        if ($reply === '' || $botUsername === '') {
            return $reply;
        }

        $botEscaped = preg_quote($botUsername, '/');
        if ($targetUsername !== '') {
            $replacement = '@' . $targetUsername;
            $reply = preg_replace('/@' . $botEscaped . '\b/iu', $replacement, $reply) ?? $reply;
        } else {
            $reply = preg_replace('/@' . $botEscaped . '\b/iu', $botUsername, $reply) ?? $reply;
        }

        return trim($reply);
    }

    /**
     * @return list<array{id:int,title:string,url:string}>
     */
    private static function findSimilarTopics(
        int $currentTopicId,
        int $forumId,
        string $title,
        string $topicOpeningBody,
        string $triggerBody,
        int $limit = 3
    ): array
    {
        $limit = max(1, min(5, $limit));
        if ($forumId <= 0) {
            return [];
        }
        $triggerKeywords = self::extractKeywords($triggerBody, 6);
        $contextKeywords = self::extractKeywords($title . ' ' . $topicOpeningBody, 8);
        $keywords = array_values(array_unique(array_merge($triggerKeywords, $contextKeywords)));
        if (empty($keywords) || count($triggerKeywords) < 2) {
            return [];
        }

        try {
            $q = Topic::query()
                ->published()
                ->where('id', '!=', $currentTopicId)
                ->where('forum_id', $forumId)
                ->whereNull('deleted_at');

            $q->where(function ($w) use ($keywords): void {
                foreach ($keywords as $kw) {
                    $w->orWhere('title', 'like', '%' . $kw . '%');
                }
            });

            $rows = $q->orderByDesc('last_post_at')->limit(40)->get(['id', 'title', 'slug', 'url_key', 'first_post_id']);
            $postBodies = [];
            $firstPostIds = array_values(array_unique(array_filter(array_map(static fn ($r) => (int) ($r->first_post_id ?? 0), $rows->all()))));
            if (!empty($firstPostIds)) {
                $postBodies = Post::query()
                    ->whereIn('id', $firstPostIds)
                    ->pluck('body', 'id')
                    ->map(static fn ($v) => (string) $v)
                    ->all();
            }

            $scored = [];
            foreach ($rows as $t) {
                $candidateText = (string) ($t->title ?? '') . ' ' . (string) ($postBodies[(int) ($t->first_post_id ?? 0)] ?? '');
                $score = self::scoreSimilarity($keywords, $triggerKeywords, $candidateText, $title);
                if (($score['trigger_overlap'] ?? 0) < 1) {
                    continue;
                }
                if (($score['overlap'] ?? 0) < 3) {
                    continue;
                }
                if (($score['score'] ?? 0.0) < 0.58) {
                    continue;
                }
                $scored[] = [
                    'id' => (int) $t->id,
                    'title' => (string) ($t->title ?? ''),
                    'url' => function_exists('topic_url') ? topic_url($t) : core_url('topic/' . (string) $t->id),
                    '_score' => (float) $score['score'],
                ];
            }

            usort($scored, static fn ($a, $b) => ($b['_score'] <=> $a['_score']));
            $out = [];
            foreach ($scored as $t) {
                $out[] = [
                    'id' => (int) $t['id'],
                    'title' => (string) $t['title'],
                    'url' => (string) $t['url'],
                ];
                if (count($out) >= $limit) {
                    break;
                }
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param list<string> $keywords
     * @param list<string> $triggerKeywords
     * @return array{score:float,overlap:int,trigger_overlap:int}
     */
    private static function scoreSimilarity(array $keywords, array $triggerKeywords, string $candidateText, string $currentTitle): array
    {
        $candidate = mb_strtolower(strip_tags($candidateText), 'UTF-8');
        $titleLower = mb_strtolower(trim($currentTitle), 'UTF-8');
        if ($candidate === '') {
            return ['score' => 0.0, 'overlap' => 0, 'trigger_overlap' => 0];
        }

        $overlap = 0;
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($candidate, $kw) !== false) {
                $overlap++;
            }
        }
        $triggerOverlap = 0;
        foreach ($triggerKeywords as $kw) {
            if ($kw !== '' && mb_strpos($candidate, $kw) !== false) {
                $triggerOverlap++;
            }
        }

        $keywordScore = $overlap / max(1, count($keywords));
        $triggerScore = $triggerOverlap / max(1, count($triggerKeywords));
        $titleBonus = ($titleLower !== '' && mb_strpos($candidate, $titleLower) !== false) ? 0.15 : 0.0;
        $score = min(1.0, ($keywordScore * 0.55) + ($triggerScore * 0.45) + $titleBonus);

        return ['score' => $score, 'overlap' => $overlap, 'trigger_overlap' => $triggerOverlap];
    }

    /**
     * @return list<string>
     */
    private static function extractKeywords(string $text, int $max = 6): array
    {
        $text = mb_strtolower(strip_tags($text), 'UTF-8');
        $parts = preg_split('/[^a-z0-9ğüşöçıİĞÜŞÖÇ]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = ['ve', 'ile', 'ama', 'gibi', 'için', 'icin', 'daha', 'çok', 'cok', 'mi', 'mı', 'mu', 'mü', 'bu', 'şu', 'bir', 'the', 'and', 'for', 'from'];
        $filtered = [];
        foreach ($parts as $p) {
            if (mb_strlen($p, 'UTF-8') < 3) {
                continue;
            }
            if (in_array($p, $stop, true)) {
                continue;
            }
            $filtered[$p] = true;
            if (count($filtered) >= $max) {
                break;
            }
        }
        return array_keys($filtered);
    }

    /**
     * @param list<array{id:int,title:string,url:string}> $similarTopics
     */
    private static function formatSimilarTopicsForPrompt(array $similarTopics): string
    {
        $lines = [];
        foreach ($similarTopics as $i => $s) {
            $lines[] = ($i + 1) . ') ' . $s['title'] . ' - ' . $s['url'];
        }
        return implode("\n", $lines);
    }

    /**
     * @param list<array{id:int,title:string,url:string}> $similarTopics
     */
    private static function appendSimilarTopicsToReply(string $reply, array $similarTopics): string
    {
        if (trim($reply) === '') {
            return $reply;
        }
        $lines = [];
        foreach ($similarTopics as $s) {
            $lines[] = '- ' . $s['title'] . ': ' . $s['url'];
        }
        if (empty($lines)) {
            return $reply;
        }
        return rtrim($reply) . "\n\nİlgili konular:\n" . implode("\n", $lines);
    }

    private static function finishAsDone(int $jobId): void
    {
        DB::table('ai_auto_reply_jobs')->where('id', $jobId)->update([
            'status' => self::STATUS_DONE,
            'last_error' => null,
            'processed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function finishAsFailed(int $jobId, string $message): void
    {
        DB::table('ai_auto_reply_jobs')->where('id', $jobId)->update([
            'status' => self::STATUS_FAILED,
            'last_error' => mb_substr($message, 0, 1000),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function isEnabled(): bool
    {
        return self::setting('ai_auto_reply_enabled', '0') === '1';
    }

    private static function hasApiKey(): bool
    {
        return trim((string) self::setting('ai_auto_reply_openai_api_key', '')) !== '';
    }

    private static function underGlobalQuota(): bool
    {
        $maxPerMinute = max(1, (int) self::setting('ai_auto_reply_max_per_minute', '3'));
        $dailyQuota = max(1, (int) self::setting('ai_auto_reply_daily_quota', '100'));

        $minuteCount = DB::table('ai_auto_reply_jobs')
            ->where('status', self::STATUS_DONE)
            ->where('processed_at', '>=', date('Y-m-d H:i:s', time() - 60))
            ->count();

        $dayCount = DB::table('ai_auto_reply_jobs')
            ->where('status', self::STATUS_DONE)
            ->where('processed_at', '>=', date('Y-m-d 00:00:00'))
            ->count();

        return $minuteCount < $maxPerMinute && $dayCount < $dailyQuota;
    }

    private static function maxTopicAgeMinutes(): int
    {
        return max(2, min(30, (int) self::setting('ai_auto_reply_topic_max_age_minutes', '10')));
    }

    private static function isTopicFresh(Topic $topic): bool
    {
        $createdRaw = $topic->created_at ?? null;
        if ($createdRaw === null) {
            return true;
        }
        $createdTs = strtotime((string) $createdRaw);
        if ($createdTs === false) {
            return true;
        }
        return (time() - $createdTs) <= (self::maxTopicAgeMinutes() * 60);
    }

    private static function isPostFresh(Post $post): bool
    {
        $createdRaw = $post->created_at ?? null;
        if ($createdRaw === null) {
            return true;
        }
        $createdTs = strtotime((string) $createdRaw);
        if ($createdTs === false) {
            return true;
        }
        return (time() - $createdTs) <= (self::maxTopicAgeMinutes() * 60);
    }

    private static function topicReplyLimitReached(int $topicId, string $triggerType): bool
    {
        if ($topicId <= 0) {
            return false;
        }
        $maxReplies = max(1, min(50, (int) self::setting('ai_auto_reply_max_replies_per_topic', '3')));
        $maxMentionReplies = max($maxReplies, min(100, (int) self::setting('ai_auto_reply_max_mention_replies_per_topic', '8')));
        $activeLimit = ($triggerType === 'mention' || $triggerType === 'quote') ? $maxMentionReplies : $maxReplies;
        $doneCount = (int) DB::table('ai_auto_reply_jobs')
            ->where('topic_id', $topicId)
            ->where('status', self::STATUS_DONE)
            ->count();

        return $doneCount >= $activeLimit;
    }

    private static function detectPostTriggerType(Post $post, int $botUserId): ?string
    {
        $body = (string) ($post->body ?? '');
        $bodyHtml = (string) ($post->body_html ?? '');
        $botUsername = trim((string) User::query()->where('id', $botUserId)->value('username'));

        if (function_exists('core_extract_mentioned_user_ids')) {
            try {
                $mentionedIds = core_extract_mentioned_user_ids($body);
                if (is_array($mentionedIds) && in_array($botUserId, array_map('intval', $mentionedIds), true)) {
                    return 'mention';
                }
            } catch (\Throwable $e) {
            }
        }
        // Fallback 1: HTML mention linklerinden bot username yakala.
        if ($botUsername !== '' && preg_match_all('/data-mention-username="([^"]+)"/iu', $bodyHtml, $htmlMentionMatches)) {
            foreach (($htmlMentionMatches[1] ?? []) as $mentionedUsername) {
                if (mb_strtolower(trim((string) $mentionedUsername), 'UTF-8') === mb_strtolower($botUsername, 'UTF-8')) {
                    return 'mention';
                }
            }
        }
        // Fallback 2: body/body_html içinden düz @username yakala (case-insensitive).
        if ($botUsername !== '') {
            $plainText = $body . "\n" . strip_tags($bodyHtml);
            if (preg_match_all('/(^|[>\s])@([a-zA-Z0-9_\x80-\xFF]+)/u', $plainText, $plainMentions, PREG_SET_ORDER)) {
                foreach ($plainMentions as $match) {
                    $u = trim((string) ($match[2] ?? ''));
                    if ($u !== '' && mb_strtolower($u, 'UTF-8') === mb_strtolower($botUsername, 'UTF-8')) {
                        return 'mention';
                    }
                }
            }
            // Son fallback: düz substring kontrolü.
            if (stripos($plainText, '@' . $botUsername) !== false) {
                return 'mention';
            }
        }

        $quotedPostIds = [];
        if (preg_match_all('/\[quote[^\]]*\bpost=(?:"(\d+)"|\'(\d+)\'|(\d+))/i', $body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $id = (int) ($match[1] !== '' ? $match[1] : ($match[2] !== '' ? $match[2] : $match[3]));
                if ($id > 0) {
                    $quotedPostIds[$id] = true;
                }
            }
        }
        if (!empty($quotedPostIds)) {
            $quotedByBot = Post::query()
                ->whereIn('id', array_keys($quotedPostIds))
                ->where('user_id', $botUserId)
                ->exists();
            if ($quotedByBot) {
                return 'quote';
            }
        }

        return null;
    }

    public static function selectedForumIds(): array
    {
        $raw = self::setting('ai_auto_reply_forum_ids', '[]');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($decoded, static fn ($v) => (int) $v > 0))));
    }

    private static function isForumAllowed(int $forumId): bool
    {
        $selected = self::selectedForumIds();
        if (empty($selected)) {
            return true;
        }
        return in_array($forumId, $selected, true);
    }

    private static function setting(string $key, string $default): string
    {
        try {
            return (string) Setting::getValue($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private static function workerToken(): string
    {
        $token = trim((string) self::setting('ai_auto_reply_worker_token', ''));
        if ($token !== '') {
            return $token;
        }
        $token = bin2hex(random_bytes(16));
        Setting::setValue('ai_auto_reply_worker_token', $token, 'forum');
        return $token;
    }

    private static function workerUrl(string $token): string
    {
        $path = 'ai-auto-reply/worker/' . rawurlencode($token);
        if (function_exists('full_site_url')) {
            return (string) full_site_url($path);
        }
        $base = rtrim((string) core_config('app.url', ''), '/');
        if ($base === '') {
            return '';
        }
        return $base . '/' . $path;
    }

    private static function ensureQueueSchema(): void
    {
        if (self::$queueSchemaChecked) {
            return;
        }
        self::$queueSchemaChecked = true;

        try {
            if (!DB::schema()->hasTable('ai_auto_reply_jobs')) {
                return;
            }
            if (!DB::schema()->hasColumn('ai_auto_reply_jobs', 'trigger_post_id')) {
                DB::schema()->table('ai_auto_reply_jobs', function ($table): void {
                    $table->unsignedBigInteger('trigger_post_id')->nullable()->after('forum_id');
                    $table->index('trigger_post_id', 'idx_ai_reply_trigger_post');
                });
            }
            if (!DB::schema()->hasColumn('ai_auto_reply_jobs', 'trigger_type')) {
                DB::schema()->table('ai_auto_reply_jobs', function ($table): void {
                    $table->string('trigger_type', 20)->default('topic')->after('trigger_post_id');
                });
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param array<string, mixed> $ctx
     */
    private static function debug(string $event, array $ctx = []): void
    {
        try {
            $logPath = rtrim((string) (app()?->getBasePath() ?? ''), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            if ($logPath === '' || (!is_dir($logPath) && !@mkdir($logPath, 0755, true))) {
                return;
            }
            $line = date('c') . ' ' . $event . ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            @file_put_contents($logPath . DIRECTORY_SEPARATOR . 'ai_auto_reply.log', $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
        }
    }
}
