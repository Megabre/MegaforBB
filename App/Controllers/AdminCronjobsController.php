<?php

declare(strict_types=1);

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Admin: Cronjobs — list scheduled tasks and allow manual "Run full cron".
 */
class AdminCronjobsController extends AdminController
{
    private const CSRF_TOKEN = 'admin_cronjobs';

    /** Task key => [ lang_key, schedule, status: implemented|partial|missing ] */
    private const TASKS = [
        'scheduled_topics'     => ['task_scheduled_topics', '*/5 * * * *', 'implemented'],
        'forum_stats'          => ['task_forum_stats', '0 1 * * *', 'implemented'],
        'notification_cleanup' => ['task_notification_cleanup', '0 1 * * *', 'implemented'],
        'zombie_topics'        => ['task_zombie_topics', '0 1 * * *', 'implemented'],
        'post_edit_trim'       => ['task_post_edit_trim', '0 1 * * *', 'implemented'],
        'zombie_users'         => ['task_zombie_users', '0 1 * * *', 'implemented'],
        'commerce_trust'       => ['task_commerce_trust', '0 1 * * *', 'implemented'],
        'sessions'             => ['task_sessions', '*/30 * * * *', 'missing'],
        'orphan_attachments'   => ['task_orphan_attachments', '0 2 * * *', 'missing'],
        'trash_empty'          => ['task_trash_empty', '30 * * * *', 'partial'],
        'moderation_cleanup'   => ['task_moderation_cleanup', '0 1 * * *', 'missing'],
        'unban_expired'        => ['task_unban_expired', '0 1 * * *', 'missing'],
        'sitemap'              => ['task_sitemap', '0 3 * * *', 'partial'],
        // RSS cadence is per-source (frequency_minutes); e.g. 1440 = at most once/day even if cron runs every 5 min.
        'rss_feed_import'      => ['task_rss_feed_import', '*/5 * * * *', 'implemented'],
    ];

    public function index(): string
    {
        $tasks = [];
        foreach (self::TASKS as $key => $row) {
            [$langKey, $schedule, $status] = $row;
            $tasks[] = [
                'key'      => $key,
                'name'     => lang('admin.cronjobs.' . $langKey),
                'schedule' => $schedule,
                'status'   => $status,
            ];
        }

        $cronToken = (string) (DB::table('settings')->where('key', 'cron_token')->value('value') ?? '');
        $cronUrl = $this->getCronUrl($cronToken);

        return $this->view('cronjobs/index', [
            'pageTitle' => lang('admin.cronjobs.title'),
            'tasks'     => $tasks,
            'cronUrl'   => $cronUrl,
            'csrfToken' => core_csrf_token(self::CSRF_TOKEN),
        ]);
    }

    /**
     * POST: Run full cron via internal request and return output as JSON.
     * This avoids exposing the cron token to the browser.
     */
    public function runFull(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            echo json_encode(['success' => false, 'output' => '', 'message' => lang('admin.rebuild.csrf_invalid')]);
            return;
        }

        $cronToken = (string) (DB::table('settings')->where('key', 'cron_token')->value('value') ?? '');
        $output = $this->executeCron($cronToken);

        $success = strpos($output, 'CRON BASARIYLA TAMAMLANDI') !== false;
        if (!$success && strpos($output, 'Cron Token') !== false) {
            $output .= "\n\n(Cron token may be invalid or missing. Check System Settings > Cron Token.)";
        }

        echo json_encode([
            'success' => $success,
            'output'  => $output,
        ]);
    }

    /** Build cron URL (path-only for display, or full for internal request). */
    private function getCronUrl(string $cronToken, bool $fullUrl = false): string
    {
        $query = $cronToken !== '' ? '?token=' . rawurlencode($cronToken) : '';
        if (!$fullUrl) {
            $path = core_url('cron.php');
            return $path . $query;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = function_exists('app_url_base_path') ? app_url_base_path() : '';
        return $scheme . '://' . $host . $basePath . '/cron.php' . $query;
    }

    /** Run cron via CLI first; fall back to HTTP if exec is disabled. */
    private function executeCron(string $cronToken): string
    {
        $basePath = $this->app->getBasePath();
        $cronScript = $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'cron.php';
        if (!is_file($cronScript)) {
            return 'Error: public/cron.php not found.';
        }

        try {
            $cliOutput = $this->runCronViaCli($cronScript, $cronToken);
            if ($cliOutput !== null && trim($cliOutput) !== '') {
                return $cliOutput;
            }
        } catch (\Throwable $e) {
            // HTTP fallback below
        }

        return $this->runCronViaHttp($cronToken);
    }

    private function runCronViaCli(string $cronScript, string $cronToken): ?string
    {
        if (!$this->isExecAllowed()) {
            return null;
        }

        $phpBin = $this->resolvePhpBinary();
        $basePath = $this->app->getBasePath();
        $parts = [escapeshellarg($phpBin), escapeshellarg($cronScript)];
        if ($cronToken !== '') {
            $parts[] = escapeshellarg('token=' . $cronToken);
        }
        $command = implode(' ', $parts);
        if (DIRECTORY_SEPARATOR === '\\') {
            $command = 'cmd /C ' . $command . ' 2>&1';
        } else {
            $command .= ' 2>&1';
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = @proc_open($command, $descriptorSpec, $pipes, $basePath);
        if (!is_resource($proc)) {
            return null;
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

        proc_close($proc);
        $output = trim($stdout . ($stderr !== '' ? "\n" . $stderr : ''));

        if ($output === '' || $this->looksLikePhpFpmHelp($output)) {
            return null;
        }

        return $output;
    }

    private function looksLikePhpFpmHelp(string $output): bool
    {
        return stripos($output, 'Usage: php-fpm') !== false
            || (stripos($output, 'FastCGI process manager') !== false && stripos($output, 'Usage:') !== false);
    }

    private function runCronViaHttp(string $cronToken): string
    {
        if (!ini_get('allow_url_fopen')) {
            return 'Cron could not be started: allow_url_fopen is disabled and CLI exec is unavailable. Run from terminal: php public/cron.php';
        }

        $url = $this->getCronUrl($cronToken, true);

        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 120,
                    'ignore_errors' => true,
                ],
            ]);
            $result = @file_get_contents($url, false, $ctx);
            if ($result !== false) {
                return $result;
            }
        } catch (\Throwable $e) {
            return 'Error: ' . $e->getMessage();
        }

        return 'Request failed (timeout or connection error). URL: ' . $url . "\nRun from terminal: php public/cron.php";
    }

    private function isExecAllowed(): bool
    {
        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        return !in_array('proc_open', $disabled, true) && !in_array('exec', $disabled, true);
    }

    private function resolvePhpBinary(): string
    {
        $candidates = [];

        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            $binary = PHP_BINARY;

            if (DIRECTORY_SEPARATOR === '\\' && stripos($binary, 'php-cgi') !== false) {
                $cli = dirname($binary) . DIRECTORY_SEPARATOR . 'php.exe';
                if (is_file($cli)) {
                    return $cli;
                }
            }

            if ($this->isCliPhpBinary($binary)) {
                $candidates[] = $binary;
            } elseif (preg_match('/php-fpm(?:-(\d+(?:\.\d+)?))?$/i', basename($binary), $matches)) {
                $version = $matches[1] ?? '';
                $dir = dirname($binary);
                if ($version !== '') {
                    $candidates[] = $dir . DIRECTORY_SEPARATOR . 'php' . $version;
                    $candidates[] = '/usr/bin/php' . $version;
                    $candidates[] = '/usr/local/bin/php' . $version;
                }
                $candidates[] = $dir . DIRECTORY_SEPARATOR . 'php';
            }
        }

        if (defined('PHP_BINDIR') && PHP_BINDIR !== '') {
            $candidates[] = PHP_BINDIR . DIRECTORY_SEPARATOR . (DIRECTORY_SEPARATOR === '\\' ? 'php.exe' : 'php');
        }

        if (defined('PHP_MAJOR_VERSION') && defined('PHP_MINOR_VERSION')) {
            $ver = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
            $candidates[] = '/usr/bin/php' . $ver;
            $candidates[] = '/usr/local/bin/php' . $ver;
            $candidates[] = '/opt/cpanel/ea-php' . PHP_MAJOR_VERSION . PHP_MINOR_VERSION . '/root/usr/bin/php';
        }

        $candidates[] = '/usr/bin/php';
        $candidates[] = '/usr/local/bin/php';
        $candidates[] = 'php';

        foreach (array_unique($candidates) as $path) {
            if ($path === 'php') {
                return 'php';
            }
            if (is_file($path) && is_executable($path) && $this->isCliPhpBinary($path)) {
                return $path;
            }
        }

        return 'php';
    }

    private function isCliPhpBinary(string $path): bool
    {
        $base = strtolower(basename($path));

        return stripos($base, 'fpm') === false && stripos($base, 'php-cgi') === false;
    }
}
