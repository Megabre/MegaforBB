<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

class AdminUserPolicyController extends AdminController
{
    public function bans(): string
    {
        // Get users that are banned
        $users = User::where('is_banned', 1)->orderBy('id', 'desc')->get();
        return $this->view('policies/bans', [
            'pageTitle' => lang('admin.user_policy.banned_title'),
            'users' => $users,
            'user' => $this->app->auth()->user()
        ]);
    }

    public function warnings(): string
    {
        // Get users that have warning points
        $users = User::where('warning_points', '>', 0)->orderBy('warning_points', 'desc')->get();
        return $this->view('policies/warnings', [
            'pageTitle' => lang('admin.user_policy.warnings_title'),
            'users' => $users,
            'user' => $this->app->auth()->user()
        ]);
    }
}
