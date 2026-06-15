<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\VersionCheckService;
use App\Version;
use App\Services\MeilisearchService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Admin: Rebuild — XenForo/WoltLab-style counter recalculation and data repair.
 */
class AdminRebuildController extends AdminController
{
    private const CSRF_TOKEN = 'admin_rebuild';

    private const STEPS = [
        'version_integrity' => 'Run version and file integrity check',
        'forums'       => 'Rebuild forum counters',
        'topics'       => 'Rebuild topic data',
        'likes'        => 'Rebuild post like counts',
        'post_votes'   => 'Rebuild post vote scores (net_votes)',
        'reputations'  => 'Rebuild reaction scores',
        'polls'        => 'Rebuild poll data and option vote counts',
        'pages'        => 'Rebuild pages cache',
        'gravatars'    => 'Fetch Gravatars for users without avatar',
        'permissions'  => 'Clean unused permission combinations',
        'stats'        => 'Rebuild daily statistics',
        'user_stats'   => 'Refresh user stats (topic/post counts, clear cache)',
        'search_index' => 'Rebuild search index (Meilisearch topics)',
        'sitemap'      => 'Rebuild sitemap (invalidate cache)',
        'routes'       => 'Clear route cache (recompiled on next request)',
        'frontend_build' => 'Build frontend Tailwind CSS assets',
        'cache'        => 'Clear file and OPcache',
    ];

    public function index(): string
    {
        return $this->view('rebuild/index', [
            'pageTitle' => lang('admin.rebuild.title'),
            'steps'     => array_combine(array_keys(self::STEPS), array_map(fn ($k) => lang('admin.rebuild.step_' . $k), array_keys(self::STEPS))),
            'csrfToken' => core_csrf_token(self::CSRF_TOKEN),
        ]);
    }

    /**
     * AJAX POST: Run a single rebuild step.
     * Returns JSON: { success: bool, step: string, message: string, count: int }
     */
    public function run(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => lang('admin.rebuild.csrf_invalid')]);
            return;
        }

        $step = trim((string) ($_POST['step'] ?? ''));
        if (!array_key_exists($step, self::STEPS)) {
            echo json_encode(['success' => false, 'message' => lang('admin.rebuild.invalid_step', ['step' => $step])]);
            return;
        }

        try {
            $result = match ($step) {
                'version_integrity' => $this->rebuildVersionIntegrity(),
                'forums'      => $this->rebuildForums(),
                'topics'      => $this->rebuildTopics(),
                'likes'       => $this->rebuildLikes(),
                'post_votes'  => $this->rebuildPostVotes(),
                'reputations' => $this->rebuildReputations(),
                'polls'       => $this->rebuildPolls(),
                'pages'       => $this->rebuildPages(),
                'gravatars'   => $this->rebuildGravatars(),
                'permissions' => $this->rebuildPermissions(),
                'stats'       => $this->rebuildStats(),
                'user_stats'  => $this->rebuildUserStats(),
                'search_index' => $this->rebuildSearchIndex(),
                'sitemap'     => $this->rebuildSitemap(),
                'routes'      => $this->rebuildRoutesCache(),
                'frontend_build' => $this->rebuildFrontendBuild(),
                'cache'       => $this->rebuildCache(),
            };

            $count = is_array($result) ? (int) ($result['count'] ?? 0) : (int) $result;
            $stepSuccess = !is_array($result) || !array_key_exists('success', $result) || (bool) $result['success'];
            $level = is_array($result) ? (string) ($result['level'] ?? ($stepSuccess ? 'success' : 'danger')) : 'success';

            $message = lang('admin.rebuild.step_' . $step) . lang('admin.rebuild.step_completed');
            if (is_array($result) && isset($result['message']) && trim((string) $result['message']) !== '') {
                $message = (string) $result['message'];
            }
            if ($step === 'frontend_build' && is_array($result) && !empty($result['skipped'])) {
                $message = lang('admin.rebuild.frontend_build_skipped');
            }
            if ($step === 'search_index' && is_array($result) && !empty($result['skipped'])) {
                $message = lang('admin.rebuild.search_index_skipped');
                $detail = trim((string) ($result['error'] ?? ''));
                if ($detail !== '') {
                    $message .= ' ' . $detail;
                }
                $host = trim((string) ($result['host'] ?? ''));
                if ($host !== '') {
                    $message .= ' (MEILISEARCH_HOST=' . $host . ')';
                }
            }

            echo json_encode([
                'success' => $stepSuccess,
                'step'    => $step,
                'message' => $message,
                'count'   => $count,
                'level'   => $level,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'step'    => $step,
                'message' => lang('admin.rebuild.error_message', ['message' => $e->getMessage()]),
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /** Version + file verification check (uses GitHub version.json + manifest). */
    private function rebuildVersionIntegrity(): array
    {
        $checkUrl = core_config('app.version_check_url', Version::DEFAULT_VERSION_CHECK_URL);
        $service = new VersionCheckService($checkUrl, $this->app->getBasePath());
        $result = $service->runCheck(true);

        if (!($result['success'] ?? false)) {
            throw new \RuntimeException((string) ($result['error'] ?? lang('admin.rebuild.version_integrity_unknown_error')));
        }

        $file = is_array($result['file_verification'] ?? null) ? $result['file_verification'] : [];
        $total = (int) ($file['total'] ?? 0);
        $status = (string) ($file['status'] ?? 'unknown');

        if (($result['upgrade_available'] ?? false) === true) {
            $versionMsg = lang('admin.rebuild.version_integrity_upgrade', [
                'current' => Version::VERSION,
                'remote' => (string) ($result['remote'] ?? '?'),
            ]);
        } else {
            $versionMsg = lang('admin.rebuild.version_integrity_version_ok', [
                'version' => Version::VERSION,
            ]);
        }

        $fileMsg = match ($status) {
            'ok' => lang('admin.rebuild.version_integrity_files_ok', ['total' => $total]),
            'issues' => lang('admin.rebuild.version_integrity_files_issues', [
                'modified' => (int) ($file['modified_count'] ?? 0),
                'missing' => (int) ($file['missing_count'] ?? 0),
                'unexpected' => (int) ($file['unexpected_count'] ?? 0),
            ]),
            'skipped' => (string) ($file['message'] ?? lang('admin.rebuild.version_integrity_files_skipped')),
            default => (string) ($file['message'] ?? lang('admin.rebuild.version_integrity_files_unknown')),
        };

        $success = $status !== 'error';
        $level = match ($status) {
            'ok' => 'success',
            'issues' => 'warning',
            'skipped' => 'secondary',
            'error' => 'danger',
            default => 'warning',
        };

        return [
            'count' => $total,
            'message' => trim($versionMsg . ' ' . $fileMsg),
            'success' => $success,
            'level' => $level,
        ];
    }

    private function rebuildForums(): int
    {
        DB::statement("
            UPDATE forums f
            SET f.topic_count = (
                SELECT COUNT(*) FROM topics t WHERE t.forum_id = f.id AND t.deleted_at IS NULL
            )
        ");
        DB::statement("
            UPDATE forums f
            SET f.post_count = (
                SELECT COUNT(*) FROM posts p
                JOIN topics t ON t.id = p.topic_id
                WHERE t.forum_id = f.id AND t.deleted_at IS NULL AND p.deleted_at IS NULL
            )
        ");
        DB::statement("
            UPDATE forums f
            LEFT JOIN (
                SELECT t.forum_id,
                       MAX(p.id) AS last_post_id
                FROM posts p
                JOIN topics t ON t.id = p.topic_id
                WHERE t.deleted_at IS NULL AND p.deleted_at IS NULL
                GROUP BY t.forum_id
            ) lp ON lp.forum_id = f.id
            LEFT JOIN posts p2 ON p2.id = lp.last_post_id
            SET f.last_post_id = lp.last_post_id,
                f.last_post_user_id = p2.user_id
        ");
        try {
            $this->app->cache()->delete('home_categories');
        } catch (\Throwable $e) {
        }
        return (int) DB::table('forums')->count();
    }

    private function rebuildTopics(): int
    {
        DB::statement("
            UPDATE topics t
            SET t.reply_count = GREATEST(0, (
                SELECT COUNT(*) FROM posts p WHERE p.topic_id = t.id AND p.deleted_at IS NULL
            ) - 1)
            WHERE t.deleted_at IS NULL
        ");
        DB::statement("
            UPDATE topics t
            SET t.first_post_id = (
                SELECT MIN(p.id) FROM posts p WHERE p.topic_id = t.id AND p.is_first_post = 1 AND p.deleted_at IS NULL
            )
            WHERE t.deleted_at IS NULL
        ");
        DB::statement("
            UPDATE topics t
            LEFT JOIN (
                SELECT p.topic_id, MAX(p.id) AS last_post_id
                FROM posts p
                WHERE p.deleted_at IS NULL
                GROUP BY p.topic_id
            ) lp ON lp.topic_id = t.id
            LEFT JOIN posts p2 ON p2.id = lp.last_post_id
            SET t.last_post_id = lp.last_post_id,
                t.last_post_at = p2.created_at,
                t.last_post_user_id = p2.user_id
            WHERE t.deleted_at IS NULL
        ");
        try {
            $this->app->cache()->delete('home_categories');
        } catch (\Throwable $e) {
        }
        return (int) DB::table('topics')->whereNull('deleted_at')->count();
    }

    /** Rebuild post like_count from post_likes (WoltLab: Beğenileri yeniden oluşturur). */
    private function rebuildLikes(): int
    {
        DB::statement("
            UPDATE posts p
            SET p.like_count = (
                SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id
            )
        ");
        return (int) DB::table('posts')->count();
    }

    /** Rebuild post net_votes from post_votes (up/down votes). */
    private function rebuildPostVotes(): int
    {
        DB::statement("
            UPDATE posts p
            SET p.net_votes = COALESCE((
                SELECT SUM(pv.value) FROM post_votes pv WHERE pv.post_id = p.id
            ), 0)
        ");
        return (int) DB::table('posts')->count();
    }

    private function rebuildReputations(): int
    {
        DB::statement("
            UPDATE users u
            SET u.reputation_positive = (
                SELECT COUNT(*) FROM user_reputations r WHERE r.to_user_id = u.id AND r.value > 0
            ),
            u.reputation_negative = (
                SELECT COUNT(*) FROM user_reputations r WHERE r.to_user_id = u.id AND r.value < 0
            )
        ");
        return (int) DB::table('users')->count();
    }

    /** Rebuild poll option vote_count from poll_votes (WoltLab: Anketleri yeniden oluşturur). */
    private function rebuildPolls(): int
    {
        DB::statement("
            UPDATE poll_options po
            SET po.vote_count = (
                SELECT COUNT(*) FROM poll_votes pv WHERE pv.option_id = po.id
            )
        ");
        return (int) DB::table('poll_options')->count();
    }

    /** Invalidate pages cache so next request uses fresh data (WoltLab: Sayfaları yeniden oluşturur). */
    private function rebuildPages(): int
    {
        $cache = $this->app->cache();
        $cache->delete('pages_list');
        $cache->delete('pages_active');
        return (int) DB::table('pages')->count();
    }

    /** Invalidate sitemap cache so next sitemap.xml request is fresh (WoltLab: Site Haritasını yeniden oluşturur). */
    private function rebuildSitemap(): int
    {
        $this->app->cache()->delete('sitemap_xml');
        return 1;
    }

    private function rebuildGravatars(): int
    {
        $users = DB::table('users')->whereNull('avatar_path')->orWhere('avatar_path', '')->get(['id', 'email']);
        $count = 0;
        foreach ($users as $u) {
            $hash = md5(strtolower(trim($u->email ?? '')));
            $url = "https://www.gravatar.com/avatar/$hash?d=mp&s=200";
            DB::table('users')->where('id', $u->id)->update(['avatar_path' => $url]);
            $count++;
        }
        return $count;
    }

    private function rebuildPermissions(): int
    {
        $count1 = DB::table('group_permissions')->whereNotIn('role_id', DB::table('roles')->select('id'))->delete();
        $count2 = 0;
        try {
            DB::statement("
                DELETE FROM content_permissions
                WHERE (role_id IS NOT NULL AND role_id NOT IN (SELECT id FROM roles))
                   OR (user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users))
                   OR (content_type = 'forum' AND content_id NOT IN (SELECT id FROM forums))
            ");
            $count2 = 1; // affected rows not needed for return
        } catch (\Throwable $e) {
            // Table might not exist yet if roles feature is incomplete
        }
        return (int) ($count1 + $count2);
    }

    /** Önbelleği temizleyerek bir sonraki sayfa yüklemede konu/mesaj sayıları doğru hesaplansın. */
    private function rebuildUserStats(): int
    {
        $this->app->cache()->clear();
        return 1;
    }

    /**
     * Meilisearch konu indeksini yeniden oluşturur (XenForo xf-rebuild:search benzeri).
     * Meilisearch yoksa adım atlanır, hata fırlatılmaz.
     * @return int|array{count: int, skipped: bool}
     */
    private function rebuildSearchIndex(): int|array
    {
        $meili = new MeilisearchService();
        if (!$meili->isAvailable()) {
            return [
                'count'  => 0,
                'skipped' => true,
                'error'  => $meili->getLastError(),
                'host'   => $meili->getHost(),
            ];
        }

        $batchSize = 100;
        $total = 0;

        DB::table('topics')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk($batchSize, function ($topics) use ($meili, &$total) {
                $ids = $topics->pluck('id')->all();
                $bodies = DB::table('posts')
                    ->whereIn('topic_id', $ids)
                    ->where('is_first_post', 1)
                    ->whereNull('deleted_at')
                    ->pluck('body', 'topic_id');

                $documents = [];
                foreach ($topics as $t) {
                    $documents[] = [
                        'id' => (int) $t->id,
                        'title' => (string) $t->title,
                        'slug' => (string) $t->slug,
                        'body' => (string) ($bodies[$t->id] ?? ''),
                        'forum_id' => (int) $t->forum_id,
                        'created_at' => isset($t->created_at) ? (strtotime($t->created_at) ?: 0) : 0,
                    ];
                }
                if ($documents !== [] && $meili->indexTopicBatch($documents)) {
                    $total += count($documents);
                }
            });

        return $total;
    }

    /** Deletes the route cache file; recompiled on next request via web.php. */
    private function rebuildRoutesCache(): int
    {
        $dir = MEGAFORBB_BASE_PATH . '/Content/storage/cache';
        $n = 0;
        foreach ([ \Forecor\Core\Router::ROUTES_CACHE_FILENAME,
            \Forecor\Core\Router::ROUTES_LEGACY_CACHE_FILENAME,
        ] as $name) {
            $file = $dir . '/' . $name;
            if (is_file($file)) {
                @unlink($file);
                $n++;
            }
        }

        return $n;
    }

    private function rebuildStats(): int
    {
        $totals = $this->layoutService()->recalculateForumStatsTotals();

        return $totals['total_topics'] + $totals['total_posts'] + $totals['total_members'];
    }

    private function rebuildCache(): int
    {
        $count = 0;

        // App cache
        $this->app->cache()->clear();
        $count++;

        // File cache (route cache routes_compiled.php is also in this folder)
        $cacheDir = MEGAFORBB_BASE_PATH . '/Content/storage/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                        $count++;
                    }
                }
            }
        }

        // View cache (Twig – recursive, alt dizinler dahil)
        $viewCacheDir = MEGAFORBB_BASE_PATH . '/Content/storage/views';
        if (is_dir($viewCacheDir)) {
            $iter = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($viewCacheDir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iter as $item) {
                if ($item->isFile()) {
                    @unlink($item->getPathname());
                    $count++;
                }
            }
        }

        // OPcache
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $count++;
        }

        return $count;
    }

    /**
     * Tailwind CSS derlemesi. Node/npx yoksa ve mevcut tailwind.css varsa adım atlanır (hata yok).
     * @return int|array{count: int, skipped: bool} Başarıda 1 veya ['count' => 1, 'skipped' => true]
     */
    private function rebuildFrontendBuild(): int|array
    {
        @set_time_limit(300);

        $basePath = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2);
        $outputCss = $basePath . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Template' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'tailwind.css';

        $configFile = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'tailwind.config.js';
        if (!is_file($configFile)) {
            throw new \RuntimeException(lang('admin.rebuild.frontend_build_missing_config'));
        }

        $env = [];
        if (function_exists('getenv')) {
            $all = getenv();
            if (is_array($all)) {
                $env = $all;
            }
        }
        $npxPath = trim((string) $this->app->getSetting('npx_binary_path', ''));
        $npxResolved = false;
        if ($npxPath !== '' && is_file($npxPath) && (DIRECTORY_SEPARATOR === '\\' || @is_executable($npxPath))) {
            $env['NPX_BIN'] = $npxPath;
            $npxResolved = true;
        } elseif (DIRECTORY_SEPARATOR !== '\\') {
            $discovered = $this->discoverNpxPath($env['PATH'] ?? '');
            if ($discovered !== '') {
                $env['NPX_BIN'] = $discovered;
                $npxResolved = true;
            }
        } elseif (DIRECTORY_SEPARATOR === '\\') {
            $npxResolved = true; // Windows'ta script npx'i PATH'ten kullanır
        }

        if (!$npxResolved) {
            if (is_file($outputCss) && filesize($outputCss) > 0) {
                return ['count' => 1, 'skipped' => true];
            }
            throw new \RuntimeException(lang('admin.rebuild.frontend_build_no_node_upload'));
        }

        if (!$this->isExecAllowed()) {
            throw new \RuntimeException(lang('admin.rebuild.frontend_build_exec_disabled'));
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $script = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'tailwind.build.bat';
            if (!is_file($script)) {
                throw new \RuntimeException(lang('admin.rebuild.frontend_build_missing_script'));
            }
            $command = 'cmd /C "' . str_replace('"', '""', $script) . '"';
        } else {
            $script = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'tailwind.build.sh';
            if (!is_file($script)) {
                throw new \RuntimeException(lang('admin.rebuild.frontend_build_missing_script'));
            }
            $command = 'sh ' . escapeshellarg($script);
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = @proc_open($command, $descriptorSpec, $pipes, $basePath, $env);
        if (!is_resource($proc)) {
            throw new \RuntimeException(lang('admin.rebuild.frontend_build_exec_failed'));
        }

        if (isset($pipes[0]) && is_resource($pipes[0])) {
            fclose($pipes[0]);
        }

        $stdout = '';
        $stderr = '';
        if (isset($pipes[1]) && is_resource($pipes[1])) {
            $stdout = (string) stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }
        if (isset($pipes[2]) && is_resource($pipes[2])) {
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }

        $exitCode = proc_close($proc);
        if ($exitCode !== 0) {
            $tail = trim($stdout . "\n" . $stderr);
            if ($tail !== '') {
                $tail = preg_replace('/\s+/', ' ', $tail);
                if (strlen($tail) > 220) {
                    $tail = substr($tail, 0, 220) . '...';
                }
                throw new \RuntimeException(lang('admin.rebuild.frontend_build_failed_detail', ['message' => $tail]));
            }
            throw new \RuntimeException(lang('admin.rebuild.frontend_build_exec_failed'));
        }

        if (!is_file($outputCss) || filesize($outputCss) === 0) {
            throw new \RuntimeException(lang('admin.rebuild.frontend_build_output_missing'));
        }

        return 1;
    }

    /**
     * Linux hosting: web sunucusu PATH'inde npx olmayabilir; yaygın dizinlerde ara.
     * Sadece okuma ve which çalıştırma — kullanıcı girdisi shell'e verilmez.
     */
    private function discoverNpxPath(string $existingPath): string
    {
        $extraPaths = '/usr/local/bin:/usr/bin:/opt/node/bin';
        $path = $extraPaths . ':' . trim($existingPath);
        $path = preg_replace('/::+/', ':', $path);
        $path = trim($path, ':');
        if ($path === '') {
            $path = $extraPaths;
        }

        $cmd = 'PATH=' . escapeshellarg($path) . ' which npx 2>/dev/null';
        $out = @shell_exec($cmd);
        if ($out === null || $out === '') {
            return '';
        }
        $line = trim(explode("\n", $out)[0]);
        // Sadece mutlak yol kabul et, .. ve boşluk yok
        if ($line === '' || strlen($line) < 2 || $line[0] !== '/' || strpos($line, '..') !== false || str_contains($line, ' ')) {
            return '';
        }
        if (is_file($line) && (PHP_OS_FAMILY === 'Windows' || @is_executable($line))) {
            return $line;
        }
        return '';
    }

    /**
     * Admin panelden veritabanı migrasyonlarını çalıştır (SSH olmayan hostingler için).
     * CLI migrate.php mantığını web'e taşır. Sadece App/Database/migrations içindeki
     * dosyaları kullanır; kullanıcı girdisi SQL'e dahil edilmez.
     */
    public function runMigrations(): void
    {
        @set_time_limit(300);
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            echo json_encode(['success' => false, 'message' => lang('admin.rebuild.csrf_invalid'), 'output' => ''], JSON_UNESCAPED_UNICODE);
            return;
        }

        $action = trim((string) ($_POST['action'] ?? 'run'));
        $basePath = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2);
        $migrationsDir = $basePath . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'migrations';

        try {
            // Migrations tablosunu oluştur (yoksa)
            DB::statement("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INT UNSIGNED NOT NULL DEFAULT 1,
                    ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_migration (migration)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo = DB::connection()->getPdo();
            $ran = DB::table('migrations')->orderBy('batch')->orderBy('id')->pluck('migration')->all();

            // Status modunda sadece durum bilgisi dön
            if ($action === 'status') {
                $files = $this->getMigrationFiles($migrationsDir);
                $statusList = [];
                foreach ($files as $f) {
                    $name = pathinfo($f, PATHINFO_FILENAME);
                    $statusList[] = [
                        'name' => $name,
                        'ran'  => in_array($name, $ran, true),
                    ];
                }
                echo json_encode([
                    'success' => true,
                    'message' => lang('admin.rebuild.migration_status_info', [
                        'ran'   => count(array_filter($statusList, fn ($s) => $s['ran'])),
                        'total' => count($statusList),
                    ]),
                    'output'    => '',
                    'status'    => $statusList,
                    'ran_count' => count(array_filter($statusList, fn ($s) => $s['ran'])),
                    'total'     => count($statusList),
                    'pending'   => count(array_filter($statusList, fn ($s) => !$s['ran'])),
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Bekleyen migrasyonları çalıştır
            $files = $this->getMigrationFiles($migrationsDir);
            $pending = array_filter($files, function ($f) use ($ran) {
                return !in_array(pathinfo($f, PATHINFO_FILENAME), $ran, true);
            });

            if (empty($pending)) {
                echo json_encode([
                    'success' => true,
                    'message' => lang('admin.rebuild.migration_no_pending'),
                    'output'  => '',
                    'count'   => 0,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $maxBatch = DB::table('migrations')->max('batch');
            $batch = $maxBatch !== null ? (int) $maxBatch + 1 : 1;
            $log = [];
            $errorCount = 0;

            foreach ($pending as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $path = $migrationsDir . DIRECTORY_SEPARATOR . $file;
                $migration = require $path;

                // Format 1: return ['up' => callable|string, 'down' => ...]
                // Format 2: return new class { public function up(): void {...} }
                $up = null;
                if (is_array($migration) && !empty($migration['up'])) {
                    $up = $migration['up'];
                } elseif (is_object($migration) && method_exists($migration, 'up')) {
                    $up = [$migration, 'up'];
                }

                if ($up === null) {
                    $log[] = "⏭ {$name} (skipped — no 'up')";
                    continue;
                }

                try {
                    if (is_callable($up)) {
                        // Anonim sınıf: parametre almaz; array closure: PDO alır
                        $ref = is_array($up)
                            ? new \ReflectionMethod($up[0], $up[1])
                            : new \ReflectionFunction($up);
                        $ref->getNumberOfParameters() > 0 ? $up($pdo) : $up();
                    } else {
                        $sql = trim((string) $up);
                        if ($sql !== '') {
                            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                                if ($stmt !== '') {
                                    $pdo->exec($stmt);
                                }
                            }
                        }
                    }
                    DB::table('migrations')->insert([
                        'migration' => $name,
                        'batch'     => $batch,
                        'ran_at'    => date('Y-m-d H:i:s'),
                    ]);
                    $log[] = "✓ {$name}";
                } catch (\Throwable $e) {
                    $errorCount++;
                    $log[] = "✗ {$name}: " . $e->getMessage();
                }
            }

            $success = $errorCount === 0;
            $message = $success
                ? lang('admin.rebuild.migration_success', ['count' => count($pending) - $errorCount])
                : lang('admin.rebuild.migration_partial', [
                    'ok'     => count($pending) - $errorCount,
                    'errors' => $errorCount,
                ]);

            echo json_encode([
                'success' => $success,
                'message' => $message,
                'output'  => implode("\n", $log),
                'count'   => count($pending) - $errorCount,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => lang('admin.rebuild.migration_error', ['message' => $e->getMessage()]),
                'output'  => '',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /** Migration dosyalarını okur (sadece .php uzantılı dosyalar). */
    private function getMigrationFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $files = [];
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            if (pathinfo($f, PATHINFO_EXTENSION) === 'php') {
                $files[] = $f;
            }
        }
        sort($files);
        return $files;
    }

    private const COMPOSER_ACTIONS = ['install', 'update'];

    /**
     * Panelden sadece composer install veya update çalıştır (SSH olmayan hostingler için).
     * Sabit komutlar: ek paket adı veya kullanıcı girdisi ASLA shell'e verilmez.
     * Sadece proje composer.json/composer.lock içindeki bağımlılıklar kullanılır.
     */
    public function composerInstall(): void
    {
        @set_time_limit(300);
        ob_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => lang('admin.rebuild.csrf_invalid'), 'output' => ''], JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            return;
        }

        $action = trim((string) ($_POST['action'] ?? 'install'));
        if (!in_array($action, self::COMPOSER_ACTIONS, true)) {
            $action = 'install';
        }

        $basePath = defined('MEGAFORBB_BASE_PATH') ? MEGAFORBB_BASE_PATH : dirname(__DIR__, 2);
        if (!is_file($basePath . DIRECTORY_SEPARATOR . 'composer.json')) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => lang('admin.rebuild.composer_no_composer_json'),
                'output' => '',
            ], JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            return;
        }

        $composerPhar = $basePath . DIRECTORY_SEPARATOR . 'composer.phar';
        $phpBin = $this->resolvePhpBinary();
        $composerHome = $basePath . DIRECTORY_SEPARATOR . 'Content' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
        if (!is_dir($composerHome)) {
            @mkdir($composerHome, 0755, true);
        }

        if (!$this->isExecAllowed()) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => lang('admin.rebuild.composer_exec_disabled'),
                'output' => '',
            ], JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            return;
        }

        $output = [];
        $exitCode = -1;
        // stdin'i pipe yapıp hemen kapatıyoruz; Linux'ta alt süreç girdi beklemesin diye (takılma önlenir)
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $env = $this->buildComposerEnv($composerHome);

        if (is_file($composerPhar)) {
            // Dizi ile çalıştır: Windows’ta bypass_shell + string komut bazen başarısız oluyor
            $cmd = [
                $phpBin,
                $composerPhar,
                $action,
                '--no-dev',
                '--optimize-autoloader',
                '--no-interaction',
            ];
            $proc = @proc_open(
                $cmd,
                $descriptorSpec,
                $pipes,
                $basePath,
                $env,
                ['bypass_shell' => true]
            );
        } else {
            $runner = $this->resolveComposerRunner($basePath, $phpBin);
            if ($runner === null) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => lang('admin.rebuild.composer_not_found'),
                    'output' => lang('admin.rebuild.composer_not_found_hint'),
                ], JSON_UNESCAPED_UNICODE);
                ob_end_flush();
                return;
            }
            if ($runner['type'] === 'phar') {
                $cmd = [
                    $runner['php'],
                    $runner['phar'],
                    $action,
                    '--no-dev',
                    '--optimize-autoloader',
                    '--no-interaction',
                ];
                $proc = @proc_open($cmd, $descriptorSpec, $pipes, $basePath, $env, ['bypass_shell' => true]);
            } else {
                $cmd = $runner['cmd'];
                $proc = @proc_open($cmd, $descriptorSpec, $pipes, $basePath, $env, ['bypass_shell' => false]);
            }
        }

        if (is_resource($proc)) {
            if (isset($pipes[0]) && is_resource($pipes[0])) {
                fclose($pipes[0]);
            }
            $stdout = '';
            $stderr = '';
            $out = $pipes[1] ?? null;
            $err = $pipes[2] ?? null;
            if ($out !== null && is_resource($out)) {
                stream_set_blocking($out, false);
            }
            if ($err !== null && is_resource($err)) {
                stream_set_blocking($err, false);
            }
            $write = [];
            $except = [];
            while (true) {
                $status = proc_get_status($proc);
                $read = [];
                if ($out !== null && is_resource($out)) {
                    $read[] = $out;
                }
                if ($err !== null && is_resource($err)) {
                    $read[] = $err;
                }
                if ($read !== []) {
                    $n = @stream_select($read, $write, $except, 0, 200000);
                    if ($n > 0) {
                        foreach ($read as $r) {
                            $chunk = stream_get_contents($r);
                            if ($chunk !== '') {
                                if ($r === $out) {
                                    $stdout .= $chunk;
                                } else {
                                    $stderr .= $chunk;
                                }
                            }
                        }
                    }
                }
                if ($status === false || !$status['running']) {
                    break;
                }
                usleep(100000);
            }
            if ($out !== null && is_resource($out)) {
                stream_set_blocking($out, true);
                $stdout .= stream_get_contents($out);
                fclose($out);
            }
            if ($err !== null && is_resource($err)) {
                stream_set_blocking($err, true);
                $stderr .= stream_get_contents($err);
                fclose($err);
            }
            $exitCode = proc_close($proc);
            $output[] = trim($stdout . "\n" . $stderr);
        } else {
            $output[] = lang('admin.rebuild.composer_exec_failed');
        }

        $outputStr = implode("\n", $output);
        $message = $exitCode === 0
            ? lang('admin.rebuild.composer_success_' . $action)
            : lang('admin.rebuild.composer_error_' . $action);

        ob_clean();
        echo json_encode([
            'success' => $exitCode === 0,
            'message' => $message,
            'output' => $outputStr,
            'exit_code' => $exitCode,
        ], JSON_UNESCAPED_UNICODE);
        ob_end_flush();
    }

    private function isExecAllowed(): bool
    {
        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);
        return !in_array('proc_open', $disabled, true) && !in_array('exec', $disabled, true);
    }

    private function resolvePhpBinary(): string
    {
        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            return PHP_BINARY;
        }
        if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
            $exe = PHP_BINDIR . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php');
            return is_file($exe) ? $exe : 'php';
        }
        if (DIRECTORY_SEPARATOR === '/') {
            foreach (['/usr/bin/php', '/usr/local/bin/php'] as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }
        }
        return 'php';
    }

    /**
     * Composer çalıştırıcısını çözümler: önce proje kökünde composer.phar, sonra ayar/Laragon/yaygın yollar.
     * @return array{type: 'phar', php: string, phar: string}|array{type: 'cmd', cmd: string}|null
     */
    private function resolveComposerRunner(string $basePath, string $phpBin): ?array
    {
        $composerPhar = $basePath . DIRECTORY_SEPARATOR . 'composer.phar';
        if (is_file($composerPhar)) {
            return ['type' => 'phar', 'php' => $phpBin, 'phar' => $composerPhar];
        }

        $sep = DIRECTORY_SEPARATOR;
        $customPath = trim((string) $this->app->getSetting('composer_binary_path', ''));
        if ($customPath !== '' && is_file($customPath)) {
            $action = (isset($_POST['action']) && $_POST['action'] === 'update') ? 'update' : 'install';
            $cmd = '"' . str_replace('"', '""', $customPath) . '" ' . $action . ' --no-dev --optimize-autoloader --no-interaction 2>&1';
            return ['type' => 'cmd', 'cmd' => $cmd];
        }

        if ($sep === '\\') {
            $binDir = defined('PHP_BINDIR') ? PHP_BINDIR : '';
            if ($binDir !== '') {
                $laragonBin = dirname($binDir, 2) . $sep . 'composer';
                foreach (['composer.bat', 'composer.phar'] as $name) {
                    $path = $laragonBin . $sep . $name;
                    if (is_file($path)) {
                        if (str_ends_with($name, '.phar')) {
                            return ['type' => 'phar', 'php' => $phpBin, 'phar' => $path];
                        }
                        $action = ($_POST['action'] ?? 'install') === 'update' ? 'update' : 'install';
                        return ['type' => 'cmd', 'cmd' => '"' . str_replace('"', '""', $path) . '" ' . $action . ' --no-dev --optimize-autoloader --no-interaction 2>&1'];
                    }
                }
            }
        } else {
            foreach (['/usr/local/bin/composer', '/usr/bin/composer'] as $path) {
                if (is_file($path) && is_executable($path)) {
                    $action = ($_POST['action'] ?? 'install') === 'update' ? 'update' : 'install';
                    return ['type' => 'cmd', 'cmd' => $path . ' ' . $action . ' --no-dev --optimize-autoloader --no-interaction 2>&1'];
                }
            }
        }

        return null;
    }

    /** @return array<string, string> */
    private function buildComposerEnv(string $composerHome): array
    {
        $env = [];
        if (function_exists('getenv')) {
            $all = getenv();
            if (is_array($all)) {
                $env = $all;
            }
        }
        $env['COMPOSER_HOME'] = $composerHome;
        return $env;
    }
}
