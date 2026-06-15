<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Setting;
use App\Models\User;

class AdminSpamZombieController extends AdminController
{
    public function index(): string
    {
        $spamEnabled = (string) Setting::getValue('spam_control_enabled', '0') === '1';
        $spamMinLength = (int) Setting::getValue('spam_min_post_length', '15');
        $zombieEnabled = (string) Setting::getValue('zombie_control_enabled', '0') === '1';
        $zombieMonths = (int) Setting::getValue('zombie_inactive_months', '6');

        $suspended = User::where('is_suspended', 1)
            ->with('role:id,name')
            ->orderByDesc('suspended_at')
            ->get(['id', 'username', 'email', 'role_id', 'suspended_at', 'last_activity_at', 'closed_at']);

        return $this->view('spam_zombie/index', [
            'pageTitle' => lang('admin.spam_zombie.page_title'),
            'spam_enabled' => $spamEnabled,
            'spam_min_length' => $spamMinLength,
            'zombie_enabled' => $zombieEnabled,
            'zombie_months' => $zombieMonths,
            'suspended' => $suspended,
        ]);
    }

    public function save(): void
    {
        $spamEnabled = isset($_POST['spam_control_enabled']) && $_POST['spam_control_enabled'] === '1' ? '1' : '0';
        $spamMinLength = (int) ($_POST['spam_min_post_length'] ?? 15);
        $spamMinLength = max(0, min(500, $spamMinLength));
        $zombieEnabled = isset($_POST['zombie_control_enabled']) && $_POST['zombie_control_enabled'] === '1' ? '1' : '0';
        $zombieMonths = (int) ($_POST['zombie_inactive_months'] ?? 6);
        $zombieMonths = max(1, min(24, $zombieMonths));

        $this->setSetting('spam_control_enabled', $spamEnabled, 'forum');
        $this->setSetting('spam_min_post_length', (string) $spamMinLength, 'forum');
        $this->setSetting('zombie_control_enabled', $zombieEnabled, 'forum');
        $this->setSetting('zombie_inactive_months', (string) $zombieMonths, 'forum');

        $this->app->session()->getFlashBag()->add('spam_zombie_ok', lang('admin.spam_zombie.saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/spam-zombie'));
    }

    public function unsuspend(string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/spam-zombie'));
            return;
        }
        if ($user->closed_at !== null) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/spam-zombie'));
            return;
        }
        $user->is_suspended = 0;
        $user->suspended_at = null;
        $user->save();

        $this->app->session()->getFlashBag()->add('spam_zombie_ok', lang('admin.spam_zombie.unsuspended'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/spam-zombie'));
    }
}
