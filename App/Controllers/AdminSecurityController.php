<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\RtbhIpListService;
use App\Services\SecurityLogUserLookup;

/**
 * Admin: Security settings — central anti-bump (cooldown), violation counter, temporary ban.
 */
class AdminSecurityController extends AdminController
{
    private const CSRF_TOKEN = 'admin_security';
    private const GROUP = 'security';

    public function index(): string
    {
        // Formda her zaman veritabanındaki değerler gösterilir (saldırı modu açıkken sitede preset uygulanır ama ayarlar saklanır)
        $raw = fn (string $k, $d = '') => $this->app->getSettingRaw($k, $d);
        $rtbhUpdatedTs = (int) $raw('rtbh_list_updated_at', '0');
        $s = [
            'security_attack_mode' => $raw('security_attack_mode', '0') === '1',
            'security_enabled' => $raw('security_enabled', '1') === '1',
            'security_tracking_enabled' => $raw('security_tracking_enabled', '1') === '1',
            'analytics_visitor_log_enabled' => $raw('analytics_visitor_log_enabled', '0') === '1',
            'analytics_log_retention_minutes' => (int) $raw('analytics_log_retention_minutes', '20'),
            'security_global_rate_enabled' => $raw('security_global_rate_enabled', '0') === '1',
            'security_global_rate_per_minute' => (int) $raw('security_global_rate_per_minute', '120'),
            'security_global_rate_block_minutes' => (int) $raw('security_global_rate_block_minutes', '5'),
            'security_headers_enabled' => $raw('security_headers_enabled', '1') !== '0',
            'security_hsts_enabled' => $raw('security_hsts_enabled', '0') === '1',
            'security_suspicious_blocks_threshold' => (int) $raw('security_suspicious_blocks_threshold', '3'),
            'security_suspicious_block_minutes' => (int) $raw('security_suspicious_block_minutes', '1440'),
            'security_cooldown_reply' => (int) $raw('security_cooldown_reply', '60'),
            'security_cooldown_new_topic' => (int) $raw('security_cooldown_new_topic', '30'),
            'security_cooldown_edit_post' => (int) $raw('security_cooldown_edit_post', '10'),
            'security_cooldown_edit_topic' => (int) $raw('security_cooldown_edit_topic', '10'),
            'security_cooldown_login' => (int) $raw('security_cooldown_login', '30'),
            'security_cooldown_register' => (int) $raw('security_cooldown_register', '60'),
            'security_cooldown_send_pm' => (int) $raw('security_cooldown_send_pm', '30'),
            'security_cooldown_report' => (int) $raw('security_cooldown_report', '60'),
            'security_cooldown_like' => (int) $raw('security_cooldown_like', '5'),
            'security_violations_before_block' => (int) $raw('security_violations_before_block', '5'),
            'security_violation_window_minutes' => (int) $raw('security_violation_window_minutes', '5'),
            'security_block_duration_minutes' => (int) $raw('security_block_duration_minutes', '15'),
            'security_block_message' => $raw('security_block_message', lang('admin.security.block_message_default')),
            'captcha_provider' => $raw('captcha_provider', 'none'),
            'recaptcha_site_key' => $raw('recaptcha_site_key', ''),
            'recaptcha_secret_key' => $raw('recaptcha_secret_key', ''),
            'recaptcha_version' => $raw('recaptcha_version', 'v2'),
            'recaptcha_score_threshold' => $raw('recaptcha_score_threshold', '0.5'),
            'turnstile_site_key' => $raw('turnstile_site_key', ''),
            'turnstile_secret_key' => $raw('turnstile_secret_key', ''),
            'captcha_on_login' => $raw('captcha_on_login', '0') === '1',
            'captcha_on_register' => $raw('captcha_on_register', '1') === '1',
            'captcha_on_contact' => $raw('captcha_on_contact', '0') === '1',
            'security_log_retention_days' => (int) $raw('security_log_retention_days', '7'),
            'security_notification_email_enabled' => $raw('security_notification_email_enabled', '0') === '1',
            'rtbh_enabled' => $raw('rtbh_enabled', '0') === '1',
            'rtbh_action' => $raw('rtbh_action', RtbhIpListService::ACTION_LOG_ONLY),
            'rtbh_list_updated_at' => $rtbhUpdatedTs,
            'rtbh_list_updated_formatted' => $rtbhUpdatedTs > 0 ? date('Y-m-d H:i', $rtbhUpdatedTs) : '',
            'rtbh_list_count' => (int) $raw('rtbh_list_count', '0'),
            'rtbh_list_last_error' => $raw('rtbh_list_last_error', ''),
            'rtbh_storage_exists' => is_file(RtbhIpListService::storagePath($this->app->getBasePath())),
            'rtbh_attack_redirect_url' => $raw('rtbh_attack_redirect_url', ''),
        ];
        return $this->view('security/index', [
            'pageTitle' => lang('admin.security.page_title'),
            'settings' => $s,
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security'));
            return;
        }
        $attackMode = isset($_POST['security_attack_mode']) && $_POST['security_attack_mode'] === '1';
        $this->setSetting('security_attack_mode', $attackMode ? '1' : '0', self::GROUP);

        $rtbhAttackUrl = $this->normalizeRtbhAttackRedirectUrl((string) ($_POST['rtbh_attack_redirect_url'] ?? ''));
        $this->setSetting('rtbh_attack_redirect_url', $rtbhAttackUrl, self::GROUP);
        \Forecor\Core\Application::clearSettingCache('rtbh_attack_redirect_url');

        // When attack mode is on, do not write other settings: only override is applied, current values kept. When turned off, previous settings apply.
        if ($attackMode) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security'));
            return;
        }

        $intKeys = [
            'security_global_rate_per_minute', 'security_global_rate_block_minutes',
            'security_suspicious_blocks_threshold', 'security_suspicious_block_minutes',
            'security_cooldown_reply', 'security_cooldown_new_topic', 'security_cooldown_edit_post',
            'security_cooldown_edit_topic', 'security_cooldown_login', 'security_cooldown_register',
            'security_cooldown_send_pm', 'security_cooldown_report', 'security_cooldown_like',
            'security_violations_before_block', 'security_violation_window_minutes', 'security_block_duration_minutes',
        ];
        foreach ($intKeys as $key) {
            $v = isset($_POST[$key]) ? (string) (int) $_POST[$key] : '0';
            $this->setSetting($key, $v, self::GROUP);
        }
        $this->setSetting('security_enabled', isset($_POST['security_enabled']) && $_POST['security_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('security_notification_email_enabled', isset($_POST['security_notification_email_enabled']) && $_POST['security_notification_email_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('security_tracking_enabled', isset($_POST['security_tracking_enabled']) && $_POST['security_tracking_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('analytics_visitor_log_enabled', isset($_POST['analytics_visitor_log_enabled']) && $_POST['analytics_visitor_log_enabled'] === '1' ? '1' : '0', self::GROUP);
        $retentionMin = (int) ($_POST['analytics_log_retention_minutes'] ?? 20);
        $retentionMin = in_array($retentionMin, [10, 20, 30, 60], true) ? $retentionMin : 20;
        $this->setSetting('analytics_log_retention_minutes', (string) $retentionMin, self::GROUP);
        $this->setSetting('security_global_rate_enabled', isset($_POST['security_global_rate_enabled']) && $_POST['security_global_rate_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('security_headers_enabled', isset($_POST['security_headers_enabled']) && $_POST['security_headers_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('security_hsts_enabled', isset($_POST['security_hsts_enabled']) && $_POST['security_hsts_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('security_block_message', trim((string) ($_POST['security_block_message'] ?? '')), self::GROUP);

        $this->setSetting('captcha_provider', in_array($_POST['captcha_provider'] ?? '', ['recaptcha', 'turnstile'], true) ? $_POST['captcha_provider'] : 'none', self::GROUP);
        $this->setSetting('recaptcha_site_key', trim((string) ($_POST['recaptcha_site_key'] ?? '')), self::GROUP);
        $this->setSetting('recaptcha_secret_key', trim((string) ($_POST['recaptcha_secret_key'] ?? '')), self::GROUP);
        $this->setSetting('recaptcha_version', ($_POST['recaptcha_version'] ?? '') === 'v3' ? 'v3' : 'v2', self::GROUP);
        $this->setSetting('recaptcha_score_threshold', (string) max(0, min(1, (float) ($_POST['recaptcha_score_threshold'] ?? '0.5'))), self::GROUP);
        $this->setSetting('turnstile_site_key', trim((string) ($_POST['turnstile_site_key'] ?? '')), self::GROUP);
        $this->setSetting('turnstile_secret_key', trim((string) ($_POST['turnstile_secret_key'] ?? '')), self::GROUP);
        $this->setSetting('captcha_on_login', isset($_POST['captcha_on_login']) && $_POST['captcha_on_login'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('captcha_on_register', isset($_POST['captcha_on_register']) && $_POST['captcha_on_register'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('captcha_on_contact', isset($_POST['captcha_on_contact']) && $_POST['captcha_on_contact'] === '1' ? '1' : '0', self::GROUP);

        $retention = (int) ($_POST['security_log_retention_days'] ?? 7);
        $retention = in_array($retention, [1, 2, 3, 7, 14, 30], true) ? $retention : 7;
        $this->setSetting('security_log_retention_days', (string) $retention, self::GROUP);

        $this->setSetting('rtbh_enabled', isset($_POST['rtbh_enabled']) && $_POST['rtbh_enabled'] === '1' ? '1' : '0', self::GROUP);
        $rtbhAction = (string) ($_POST['rtbh_action'] ?? RtbhIpListService::ACTION_LOG_ONLY);
        if (!in_array($rtbhAction, [
            RtbhIpListService::ACTION_LOG_ONLY,
            RtbhIpListService::ACTION_PENDING_APPROVAL,
            RtbhIpListService::ACTION_BLOCK_REGISTER,
            RtbhIpListService::ACTION_BLOCK_REGISTER_AND_LOGIN,
            RtbhIpListService::ACTION_REDIRECT_URL,
        ], true)) {
            $rtbhAction = RtbhIpListService::ACTION_LOG_ONLY;
        }
        $this->setSetting('rtbh_action', $rtbhAction, self::GROUP);
        foreach (['rtbh_enabled', 'rtbh_action'] as $k) {
            \Forecor\Core\Application::clearSettingCache($k);
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security'));
    }

    /** Yalnızca http(s) tam URL; boş = yönlendirme kapalı. */
    private function normalizeRtbhAttackRedirectUrl(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (strlen($raw) > 2048) {
            return '';
        }
        if (!preg_match('#^https?://#i', $raw)) {
            return '';
        }

        return $raw;
    }

    /** POST: list.rtbh.com.tr output.txt indir ve yerel JSON güncelle. */
    public function rtbhRefresh(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('security_rtbh_error', lang('admin.security_rtbh.refresh_csrf'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security'));
            return;
        }
        $svc = new RtbhIpListService($this->app);
        $result = $svc->refreshFromRemote();
        if (!empty($result['success'])) {
            $count = (int) ($result['count'] ?? 0);
            $this->app->session()->getFlashBag()->add(
                'security_rtbh_ok',
                lang('admin.security_rtbh.refreshed_ok', ['count' => $count])
            );
        } else {
            $code = (string) ($result['error'] ?? 'unknown');
            $svc->recordRefreshError($code);
            $this->app->session()->getFlashBag()->add(
                'security_rtbh_error',
                lang('admin.security_rtbh.refreshed_fail', ['code' => $code])
            );
        }
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security'));
    }

    /** POST: Toggle attack mode (quick access from header). */
    public function toggleAttackMode(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin')));
            return;
        }
        $current = $this->app->getSettingRaw('security_attack_mode', '0') === '1';
        $this->setSetting('security_attack_mode', $current ? '0' : '1', self::GROUP);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin')));
    }

    /** Security event log (audit log) — recent events, filtered by retention. */
    public function log(): string
    {
        $retentionDays = (int) $this->app->getSetting('security_log_retention_days', '7');
        $retentionDays = $retentionDays > 0 ? $retentionDays : 7;
        $entries = \App\Services\SecurityLogger::read(1000, $retentionDays);
        $ipLabels = SecurityLogUserLookup::labelsForIps(SecurityLogUserLookup::collectIpsFromAuditEntries($entries));
        foreach ($entries as $i => $e) {
            $entries[$i]['event_label'] = \App\Services\SecurityLogger::eventLabel($e['event'] ?? '');
            $entries[$i]['event_summary'] = \App\Services\SecurityLogger::eventSummary($e);
            $entries[$i]['date_formatted'] = date('d.m.Y H:i:s', (int) ($e['ts'] ?? 0));
            $ip = trim((string) ($e['ip'] ?? $e['client_ip'] ?? ''));
            $hasUser = isset($e['username']) && (string) $e['username'] !== '';
            $resolved = '';
            if (!$hasUser && $ip !== '') {
                $resolved = $ipLabels[$ip] ?? $ipLabels[SecurityLogUserLookup::normalizeIp($ip)] ?? '';
            }
            $entries[$i]['resolved_ip_users'] = $resolved;
        }
        $entriesJsonFlags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $entriesJson = json_encode($entries, $entriesJsonFlags);
        if ($entriesJson === false) {
            $entriesJson = '[]';
        }

        return $this->view('security/log', [
            'pageTitle' => lang('admin.security_log.page_title'),
            'entries' => $entries,
            'entries_json' => $entriesJson,
            'retentionDays' => $retentionDays,
        ]);
    }

    /** Purges old log files and old lines in current file according to retention. */
    public function logPurge(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security/log'));
            return;
        }
        $retentionDays = (int) $this->app->getSetting('security_log_retention_days', '7');
        $retentionDays = $retentionDays > 0 ? $retentionDays : 7;
        \App\Services\SecurityLogger::purge($retentionDays);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security/log'));
    }

    /** Deletes all log files. Files are recreated automatically on new events. */
    public function logDeleteAll(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security/log'));
            return;
        }
        \App\Services\SecurityLogger::deleteAll();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/security/log'));
    }

    /** Live traffic / system log page (visitor, bot, login/logout, security single stream). */
    public function analytics(): string
    {
        $retentionMinutes = (int) $this->app->getSetting('analytics_log_retention_minutes', '20');
        $retentionMinutes = in_array($retentionMinutes, [10, 20, 30, 60], true) ? $retentionMinutes : 20;
        \App\Services\AnalyticsLogger::purge($retentionMinutes);
        $entries = \App\Services\AnalyticsLogger::readLastMinutes(300, $retentionMinutes);
        $visitorsInWindow = \App\Services\AnalyticsLogger::uniqueVisitorsCount($retentionMinutes);
        $adminPath = env('ADMIN_PATH', 'admin');
        $ipLabels = SecurityLogUserLookup::labelsForIps(SecurityLogUserLookup::collectIpsFromAnalyticsEntries($entries));
        $formatted = array_map(
            static fn (array $row): string => \App\Services\AnalyticsLogger::formatMessage($row, $ipLabels),
            $entries
        );
        $lastTs = 0;
        foreach ($entries as $e) {
            $t = (int) ($e['ts'] ?? 0);
            if ($t > $lastTs) {
                $lastTs = $t;
            }
        }
        return $this->view('security/analytics', [
            'pageTitle' => lang('admin.analytics.page_title'),
            'entries' => $entries,
            'formatted' => $formatted,
            'lastTs' => $lastTs,
            'retentionMinutes' => $retentionMinutes,
            'visitorsInWindow' => $visitorsInWindow,
            'adminPath' => $adminPath,
        ]);
    }

    /** AJAX: returns recent events as JSON (incremental via since). Expired records are deleted first. */
    public function analyticsFeed(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $retentionMinutes = (int) $this->app->getSetting('analytics_log_retention_minutes', '20');
        $retentionMinutes = in_array($retentionMinutes, [10, 20, 30, 60], true) ? $retentionMinutes : 20;
        \App\Services\AnalyticsLogger::purge($retentionMinutes);
        $since = isset($_GET['since']) ? (int) $_GET['since'] : 0;
        $entries = \App\Services\AnalyticsLogger::readSince($since, 100);
        $lastTs = 0;
        foreach ($entries as $e) {
            $t = (int) ($e['ts'] ?? 0);
            if ($t > $lastTs) {
                $lastTs = $t;
            }
        }
        $visitorsInWindow = \App\Services\AnalyticsLogger::uniqueVisitorsCount($retentionMinutes);
        $ipLabels = SecurityLogUserLookup::labelsForIps(SecurityLogUserLookup::collectIpsFromAnalyticsEntries($entries));
        echo json_encode([
            'entries' => array_map(
                static fn (array $row): string => \App\Services\AnalyticsLogger::formatMessage($row, $ipLabels),
                $entries
            ),
            'raw' => $entries,
            'since' => $lastTs,
            'visitors_in_window' => $visitorsInWindow,
            'retention_minutes' => $retentionMinutes,
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Canlı günlük: saklama süresi dışındaki kayıtları temizler. */
    public function analyticsPurge(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/analytics'));
            return;
        }
        $retentionMinutes = (int) $this->app->getSetting('analytics_log_retention_minutes', '20');
        $retentionMinutes = in_array($retentionMinutes, [10, 20, 30, 60], true) ? $retentionMinutes : 20;
        \App\Services\AnalyticsLogger::purge($retentionMinutes);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/analytics'));
    }

    /** Canlı günlük: tüm log dosyalarını ve önbelleği siler. */
    public function analyticsDeleteAll(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/analytics'));
            return;
        }
        \App\Services\AnalyticsLogger::deleteAll();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/analytics'));
    }
}
