<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Admin: Message and notification system settings.
 */
class AdminCommunicationSettingsController extends AdminController
{
    private const GROUP = 'communication';

    public function index(): string
    {
        $settings = [
            'messages_enabled' => $this->getSetting('messages_enabled', '1') === '1',
            'notifications_enabled' => $this->getSetting('notifications_enabled', '1') === '1',
            'notification_toast_enabled' => $this->getSetting('notification_toast_enabled', '1') === '1',
        ];
        return $this->view('communication_settings/index', [
            'pageTitle' => lang('admin.communication.title'),
            'settings' => $settings,
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid('admin_communication_settings', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/communication-settings'));
            return;
        }
        $keys = ['messages_enabled', 'notifications_enabled', 'notification_toast_enabled'];
        foreach ($keys as $key) {
            $v = isset($_POST[$key]) && $_POST[$key] === '1' ? '1' : '0';
            $this->setSetting($key, $v, self::GROUP);
        }
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/communication-settings'));
    }
}
