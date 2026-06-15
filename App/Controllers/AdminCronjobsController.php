<?php

declare(strict_types=1);

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Admin: Cronjobs — list scheduled tasks (WoltLab-style comparison) and allow manual "Run full cron".
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
        $url = $this->getCronUrl($cronToken, true);

        $output = '';
        try {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 120,
                    'ignore_errors' => true,
                ],
            ]);
            $result = @file_get_contents($url, false, $ctx);
            if ($result !== false) {
                $output = $result;
            } else {
                $output = 'Request failed (timeout or connection error). Run cron from command line: php public/cron.php';
            }
        } catch (\Throwable $e) {
            $output = 'Error: ' . $e->getMessage();
        }

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
        // Full URL: base (origin + app path) + 'cron.php' only — do NOT use core_url('cron.php') here or path is duplicated
        $base = rtrim((string) core_config('app.url', ''), '/');
        if ($base === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = function_exists('app_url_base_path') ? app_url_base_path() : '';
            $base = $scheme . '://' . $host . $basePath;
        }
        return rtrim($base, '/') . '/cron.php' . $query;
    }
}
