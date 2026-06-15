<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Import\MegaforBBImporter;
use App\Services\Import\MyBBImporter;
use App\Services\Import\XenForoImporter;
use Illuminate\Database\Capsule\Manager as DB;

class AdminImportController extends AdminController
{
    private const SESSION_KEY = 'megaforbb_import_source_config';
    private const SOURCE_XENFORO = 'xenforo';
    private const SOURCE_MYBB = 'mybb';
    private const SOURCE_MEGAFORBB = 'megaforbb';

    public function index(): string
    {
        $targetPdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        $sourceConfig = $this->app->session()->get(self::SESSION_KEY);
        $sourceType = is_array($sourceConfig) ? ($sourceConfig['source_type'] ?? self::SOURCE_XENFORO) : self::SOURCE_XENFORO;
        $dataLoaded = false;
        $sourceLabel = null;

        $sourceDataCounts = null;
        if (is_array($sourceConfig)) {
            try {
                $sourcePdo = $this->createSourcePdo($sourceConfig);
                $this->validateSourceTable($sourcePdo, $sourceType);
                $dataLoaded = true;
                $sourceLabel = ($sourceConfig['host'] ?? '') . ' / ' . ($sourceConfig['dbname'] ?? '');
                $sourceDataCounts = $this->getSourceDataCounts($sourcePdo, $sourceType);
            } catch (\Throwable $e) {
            }
        }

        $importer = $this->createImporter($targetPdo, null, $sourceType);
        $sourceForProgress = $sourceType === self::SOURCE_MYBB ? 'mybb' : ($sourceType === self::SOURCE_MEGAFORBB ? 'megaforbb' : 'xenforo');
        $progress = [];
        try {
            $rows = DB::table('import_progress')
                ->where('source', $sourceForProgress)
                ->orderBy('id')
                ->get(['step', 'status', 'total_rows', 'processed_rows', 'error_count', 'started_at', 'completed_at']);
            foreach ($rows as $row) {
                $progress[$row->step] = (array) $row;
            }
        } catch (\Throwable $e) {
        }

        $steps = [];
        foreach ($importer->getSteps() as $step) {
            $key = $step->key();
            $steps[] = [
                'key'    => $key,
                'name'   => $step->name(),
                'order'  => $step->order(),
                'status' => $progress[$key]['status'] ?? 'pending',
                'total'  => (int) ($progress[$key]['total_rows'] ?? 0),
                'processed' => (int) ($progress[$key]['processed_rows'] ?? 0),
                'errors' => (int) ($progress[$key]['error_count'] ?? 0),
                'started'   => $progress[$key]['started_at'] ?? null,
                'completed' => $progress[$key]['completed_at'] ?? null,
            ];
        }

        return $this->view('import/index', [
            'pageTitle'       => lang('admin.import.page_title'),
            'xfDataLoaded'    => $dataLoaded,
            'sourceLabel'     => $sourceLabel,
            'sourceConfig'    => $sourceConfig,
            'sourceType'      => $sourceType,
            'sourceDataCounts' => $sourceDataCounts,
            'steps'           => $steps,
            'progress'        => $progress,
        ]);
    }

    /**
     * Kaynak veritabanındaki tablo satır sayılarını döndürür (veri önizlemesi / "veri alınamadı" teşhisi).
     * @return array<string, int>|null
     */
    private function getSourceDataCounts(\PDO $pdo, string $sourceType): ?array
    {
        $counts = [];
        try {
            if ($sourceType === self::SOURCE_XENFORO) {
                $tables = [
                    'users'    => 'SELECT COUNT(*) FROM xf_user',
                    'categories' => 'SELECT COUNT(*) FROM xf_node n INNER JOIN xf_category c ON c.node_id = n.node_id',
                    'forums'   => 'SELECT COUNT(*) FROM xf_node n INNER JOIN xf_forum f ON f.node_id = n.node_id',
                    'topics'   => 'SELECT COUNT(*) FROM xf_thread',
                    'posts'    => 'SELECT COUNT(*) FROM xf_post',
                ];
                foreach ($tables as $key => $sql) {
                    $counts[$key] = (int) $pdo->query($sql)->fetchColumn(0);
                }
            } elseif ($sourceType === self::SOURCE_MYBB) {
                $tables = ['users' => 'mybb_users', 'topics' => 'mybb_threads', 'posts' => 'mybb_posts'];
                foreach ($tables as $key => $table) {
                    $counts[$key] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn(0);
                }
            } elseif ($sourceType === self::SOURCE_MEGAFORBB) {
                $tables = ['users' => 'users', 'categories' => 'categories', 'forums' => 'forums', 'topics' => 'topics', 'posts' => 'posts'];
                foreach ($tables as $key => $table) {
                    $counts[$key] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn(0);
                }
            }
            return $counts;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function validateSourceTable(\PDO $pdo, string $sourceType): void
    {
        if ($sourceType === self::SOURCE_MYBB) {
            $pdo->query('SELECT 1 FROM mybb_users LIMIT 1');
        } elseif ($sourceType === self::SOURCE_MEGAFORBB) {
            $pdo->query('SELECT 1 FROM users LIMIT 1');
        } else {
            $pdo->query('SELECT 1 FROM xf_user LIMIT 1');
        }
    }

    private function createImporter(\PDO $targetPdo, ?\PDO $sourcePdo, string $sourceType)
    {
        if ($sourceType === self::SOURCE_MYBB) {
            return new MyBBImporter($targetPdo, $sourcePdo);
        }
        if ($sourceType === self::SOURCE_MEGAFORBB) {
            return new MegaforBBImporter($targetPdo, $sourcePdo);
        }
        return new XenForoImporter($targetPdo, $sourcePdo);
    }

    public function testSourceConnection(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $flash = $this->app->session()->getFlashBag();

        if (!core_csrf_valid('admin_import_connection', (string)($_POST['_token'] ?? ''))) {
            $flash->add('error', lang('admin.import.csrf_invalid') ?: 'Invalid CSRF token.');
            $this->redirect(core_url($adminPath . '/import'));
            return;
        }

        $sourceType = trim((string) ($_POST['source_type'] ?? self::SOURCE_XENFORO));
        if (!in_array($sourceType, [self::SOURCE_XENFORO, self::SOURCE_MYBB, self::SOURCE_MEGAFORBB], true)) {
            $sourceType = self::SOURCE_XENFORO;
        }
        $host = trim((string) ($_POST['db_host'] ?? ''));
        $port = (int) ($_POST['db_port'] ?? 3306);
        $dbname = trim((string) ($_POST['db_name'] ?? ''));
        $username = trim((string) ($_POST['db_user'] ?? ''));
        $password = (string) ($_POST['db_password'] ?? '');
        $charset = trim((string) ($_POST['db_charset'] ?? 'utf8mb4'));
        $existing = $this->app->session()->get(self::SESSION_KEY);
        if ($password === '' && is_array($existing) && isset($existing['password'])) {
            $password = $existing['password'];
        }

        if ($host === '' || $dbname === '' || $username === '') {
            $flash->add('error', lang('admin.import.required_fields'));
            $this->redirect(core_url($adminPath . '/import'));
            return;
        }

        try {
            $pdo = $this->createSourcePdo([
                'host' => $host,
                'port' => $port,
                'dbname' => $dbname,
                'username' => $username,
                'password' => $password,
                'charset' => $charset,
            ]);
            $this->validateSourceTable($pdo, $sourceType);
        } catch (\Throwable $e) {
            $msg = $e->getMessage() ?: get_class($e);
            @error_log('[MegaforBB Import] Connection failed: ' . $msg);
            $flash->add('error', lang('admin.import.connection_failed', ['message' => $msg]));
            $this->redirect(core_url($adminPath . '/import'));
            return;
        }

        $config = [
            'source_type' => $sourceType,
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'username' => $username,
            'password' => $password,
            'charset' => $charset,
        ];
        if ($sourceType === self::SOURCE_XENFORO) {
            $config['xenforo_internal_data_path'] = trim((string) ($_POST['xenforo_internal_data_path'] ?? ''));
            $config['xenforo_data_path'] = trim((string) ($_POST['xenforo_data_path'] ?? ''));
        }
        $this->app->session()->set(self::SESSION_KEY, $config);
        $name = $sourceType === self::SOURCE_MYBB ? 'MyBB' : ($sourceType === self::SOURCE_MEGAFORBB ? 'MegaforBB' : 'XenForo');
        $flash->add('success', lang('admin.import.connection_success', ['name' => $name]));
        $this->redirect(core_url($adminPath . '/import'));
    }

    public function clearSourceConnection(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $flash = $this->app->session()->getFlashBag();

        if (!core_csrf_valid('admin_import_connection', (string)($_POST['_token'] ?? ''))) {
            $flash->add('error', lang('admin.import.csrf_invalid') ?: 'Invalid CSRF token.');
            $this->redirect(core_url($adminPath . '/import'));
            return;
        }

        $this->app->session()->remove(self::SESSION_KEY);
        $flash->add('success', lang('admin.import.connection_removed'));
        $this->redirect(core_url($adminPath . '/import'));
    }

    public function runStep(): void
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid('admin_import_connection', (string)($_POST['_token'] ?? ''))) {
            echo json_encode(['success' => false, 'error' => lang('admin.import.csrf_invalid') ?: 'Invalid CSRF token.']);
            return;
        }

        $stepKey = trim((string) ($_POST['step'] ?? ''));
        $mode = trim((string) ($_POST['mode'] ?? 'merge'));

        if ($stepKey === '') {
            echo json_encode(['success' => false, 'error' => lang('admin.import.missing_step')]);
            return;
        }

        $targetPdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        $sourceConfig = $this->app->session()->get(self::SESSION_KEY);

        if (!is_array($sourceConfig)) {
            echo json_encode(['success' => false, 'error' => lang('admin.import.connect_first')]);
            return;
        }

        $sourceType = $sourceConfig['source_type'] ?? self::SOURCE_XENFORO;
        try {
            $sourcePdo = $this->createSourcePdo($sourceConfig);
            $this->validateSourceTable($sourcePdo, $sourceType);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => lang('admin.import.connection_failed', ['message' => $e->getMessage()])]);
            return;
        }

        try {
            $importer = $this->createImporter($targetPdo, $sourcePdo, $sourceType);

            if ($mode === 'clean') {
                $firstStep = $importer->getSteps()[0] ?? null;
                if ($firstStep && $firstStep->key() === $stepKey) {
                    $this->executeSystemReset();
                    $importer->resetProgress();
                }
            }

            $runOptions = ['mode' => $mode];
            if ($sourceType === self::SOURCE_XENFORO) {
                if (!empty($sourceConfig['xenforo_internal_data_path'])) {
                    $runOptions['xenforo_internal_data_path'] = $sourceConfig['xenforo_internal_data_path'];
                }
                if (!empty($sourceConfig['xenforo_data_path'])) {
                    $runOptions['xenforo_data_path'] = $sourceConfig['xenforo_data_path'];
                }
            }
            $result = $importer->runStep($stepKey, $runOptions);

            echo json_encode([
                'success' => ($result->imported > 0 || $result->errors === 0),
                'result'  => [
                    'total'         => $result->total,
                    'imported'      => $result->imported,
                    'skipped'       => $result->skipped,
                    'errors'        => $result->errors,
                    'errorMessages' => array_slice($result->errorMessages, 0, 50),
                ],
                'step' => $stepKey,
            ]);
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage() ?: get_class($e);
            @error_log('[MegaforBB Import] Step ' . $stepKey . ' failed: ' . $errMsg);
            echo json_encode([
                'success' => false,
                'error'   => $errMsg,
                'result'  => [
                    'errors'        => 1,
                    'errorMessages' => [$errMsg],
                ],
                'step' => $stepKey,
            ]);
        }
    }

    /**
     * Sadece import ilerlemesini sıfırlar (import_progress, import_errors, import_id_map).
     * Mevcut forum verilerine (kullanıcılar, kategoriler, forumlar, konular, mesajlar vb.) DOKUNMAZ.
     * For a clean import: select "Clean Import" mode and run from the first step; data deletion is only performed there.
     */
    public function resetImport(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $flash = $this->app->session()->getFlashBag();

        if (!core_csrf_valid('admin_import_connection', (string)($_POST['_token'] ?? ''))) {
            $flash->add('error', lang('admin.import.csrf_invalid') ?: 'Invalid CSRF token.');
            $this->redirect(core_url($adminPath . '/import'));
            return;
        }

        $targetPdo = \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        $sourceConfig = $this->app->session()->get(self::SESSION_KEY);
        $sourceType = is_array($sourceConfig) ? ($sourceConfig['source_type'] ?? self::SOURCE_XENFORO) : self::SOURCE_XENFORO;
        $importer = $this->createImporter($targetPdo, null, $sourceType);
        $importer->resetProgress();

        $flash->add('success', lang('admin.import.progress_reset'));
        $this->redirect(core_url($adminPath . '/import'));
    }

    private function executeSystemReset(): void
    {
        $adminUser = $this->app->auth()->user();
        if (!$adminUser) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        $tables = [
            'user_follows', 'user_blocks', 'user_bans', 'user_warnings',
            'user_custom_fields', 'user_preferences', 'user_reputations',
            'post_likes', 'post_edits', 'post_reports',
            'poll_votes', 'poll_options', 'polls',
            'private_message_hidden', 'private_messages', 'conversation_user', 'conversations',
            'notifications', 'topic_subscriptions', 'topic_reads',
            'attachments', 'password_resets',
            'posts', 'topics', 'forums', 'categories', 'topic_prefixes',
        ];

        foreach ($tables as $table) {
            try {
                DB::statement('TRUNCATE TABLE `' . $table . '`');
            } catch (\Throwable $e) {
            }
        }

        DB::table('users')->where('id', '!=', $adminUser->id)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        try {
            DB::table('forum_stats')->where('id', 1)->update([
                'total_topics' => 0,
                'total_posts' => 0,
                'total_members' => 1,
                'last_member_id' => $adminUser->id,
                'last_member_username' => $adminUser->username,
            ]);
        } catch (\Throwable $e) {
        }

        $this->app->cache()->clear();
    }

    /**
     * @param array{host: string, port: int, dbname: string, username: string, password: string, charset: string} $config
     */
    private function createSourcePdo(array $config): \PDO
    {
        $dsn = 'mysql:host=' . $config['host'] . ';port=' . ($config['port'] ?? 3306)
            . ';dbname=' . $config['dbname']
            . ';charset=' . ($config['charset'] ?? 'utf8mb4');
        return new \PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]
        );
    }
}
