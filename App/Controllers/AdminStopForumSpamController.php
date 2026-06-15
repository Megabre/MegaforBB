<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StopForumSpamService;
use Forecor\Core\Application;

class AdminStopForumSpamController extends AdminController
{
    private const CSRF_PAGE = 'admin_stopforumspam';

    public function index(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');

        return $this->view('stop_forum_spam/index', [
            'pageTitle' => lang('admin.sfs.title'),
            'sfs_enabled' => $this->app->getSetting('sfs_enabled', '0') === '1',
            'sfs_check_ip' => $this->app->getSetting('sfs_check_ip', '1') === '1',
            'sfs_check_email' => $this->app->getSetting('sfs_check_email', '1') === '1',
            'sfs_check_username' => $this->app->getSetting('sfs_check_username', '1') === '1',
            'sfs_min_frequency' => (int) $this->app->getSetting('sfs_min_frequency', '1'),
            'sfs_min_confidence' => (float) $this->app->getSetting('sfs_min_confidence', '0'),
            'sfs_expire_days' => (int) $this->app->getSetting('sfs_expire_days', '0'),
            'csrfToken' => core_csrf_token(self::CSRF_PAGE),
            'urlSave' => core_url($adminPath . '/stop-forum-spam/save'),
            'urlTest' => core_url($adminPath . '/stop-forum-spam/test'),
        ]);
    }

    public function save(): void
    {
        if (!core_csrf_valid(self::CSRF_PAGE, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('sfs_error', lang('admin.rebuild.csrf_invalid'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/stop-forum-spam'));
            return;
        }

        $enabled = isset($_POST['sfs_enabled']) && $_POST['sfs_enabled'] === '1' ? '1' : '0';
        $checkIp = isset($_POST['sfs_check_ip']) && $_POST['sfs_check_ip'] === '1' ? '1' : '0';
        $checkEmail = isset($_POST['sfs_check_email']) && $_POST['sfs_check_email'] === '1' ? '1' : '0';
        $checkUsername = isset($_POST['sfs_check_username']) && $_POST['sfs_check_username'] === '1' ? '1' : '0';
        $minFreq = (int) ($_POST['sfs_min_frequency'] ?? 1);
        $minFreq = max(1, min(255, $minFreq));
        $minConf = (float) ($_POST['sfs_min_confidence'] ?? 0);
        $minConf = max(0.0, min(100.0, $minConf));
        $expireDays = (int) ($_POST['sfs_expire_days'] ?? 0);
        $expireDays = max(0, min(3650, $expireDays));

        $this->setSetting('sfs_enabled', $enabled, 'forum');
        $this->setSetting('sfs_check_ip', $checkIp, 'forum');
        $this->setSetting('sfs_check_email', $checkEmail, 'forum');
        $this->setSetting('sfs_check_username', $checkUsername, 'forum');
        $this->setSetting('sfs_min_frequency', (string) $minFreq, 'forum');
        $this->setSetting('sfs_min_confidence', (string) $minConf, 'forum');
        $this->setSetting('sfs_expire_days', (string) $expireDays, 'forum');

        foreach (
            [
                'sfs_enabled',
                'sfs_check_ip',
                'sfs_check_email',
                'sfs_check_username',
                'sfs_min_frequency',
                'sfs_min_confidence',
                'sfs_expire_days',
            ] as $k
        ) {
            Application::clearSettingCache($k);
        }

        $this->app->session()->getFlashBag()->add('sfs_ok', lang('admin.sfs.saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/stop-forum-spam'));
    }

    public function test(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!core_csrf_valid(self::CSRF_PAGE, (string) ($_POST['_token'] ?? ''))) {
            echo json_encode(['success' => false, 'error' => 'csrf']);
            return;
        }

        $username = trim((string) ($_POST['test_username'] ?? ''));
        $email = trim((string) ($_POST['test_email'] ?? ''));
        $ip = trim((string) ($_POST['test_ip'] ?? ''));

        $svc = new StopForumSpamService($this->app);
        $result = $svc->lookupForTest($username, $email, $ip);

        if (!$result['success']) {
            $err = $result['error'] ?? 'unknown';
            if ($err === 'empty_query') {
                $msg = lang('admin.sfs.test_empty_fields');
            } elseif ($err === 'http') {
                $msg = lang('admin.sfs.test_http_error');
            } else {
                $msg = lang('admin.sfs.test_query_error');
            }
            echo json_encode(['success' => false, 'error' => $err, 'message' => $msg]);
            return;
        }

        echo json_encode([
            'success' => true,
            'needs_approval' => !empty($result['needs_approval']),
            'raw' => $result['raw'] ?? [],
        ]);
    }
}
