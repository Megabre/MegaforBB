<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers\Admin;

use App\Modules\Idelist\Models\IdelistSetting;

class SettingsAdminController extends IdelistAdminController
{
    private const SETTING_KEYS = ['module_enabled', 'allow_anonymous_view', 'allow_downvotes', 'require_approval', 'show_vote_counts', 'votes_per_user'];

    public function index(): string
    {
        $settings = IdelistSetting::getMany(self::SETTING_KEYS, '0');
        return $this->view('idelist/settings', ['settings' => $settings, 'pageTitle' => lang('idelist.admin_title')]);
    }

    public function update(): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_settings', (string) ($_POST['_token'] ?? ''), 'idelist/settings', lang('common.invalid_csrf'));

        $payload = [];
        foreach (self::SETTING_KEYS as $key) {
            $value = $_POST[$key] ?? ($key === 'votes_per_user' ? '0' : '0');
            $payload[$key] = is_array($value) ? '0' : (string) $value;
        }
        $payload['votes_per_user'] = (string) max(0, (int) $payload['votes_per_user']);

        IdelistSetting::setMany($payload);
        $this->app->cache()->delete('idelist.enabled');
        $this->app->session()->getFlashBag()->add('success', lang('idelist.status_updated'));
        $this->redirectAdmin('idelist/settings');
    }
}
