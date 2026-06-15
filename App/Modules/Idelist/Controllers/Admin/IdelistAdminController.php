<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers\Admin;

use App\Controllers\AdminController;

abstract class IdelistAdminController extends AdminController
{
    protected function adminPath(): string
    {
        return env('ADMIN_PATH', 'admin');
    }

    protected function adminUrl(string $suffix = ''): string
    {
        $path = $this->adminPath();
        $suffix = trim($suffix, '/');
        if ($suffix !== '') {
            $path .= '/' . $suffix;
        }

        return core_url($path);
    }

    protected function redirectAdmin(string $suffix = ''): void
    {
        $this->redirect($this->adminUrl($suffix));
    }

    protected function requireCsrfOrRedirect(string $tokenKey, string $token, string $redirectSuffix, ?string $error = null): void
    {
        if (core_csrf_valid($tokenKey, $token)) {
            return;
        }
        if ($error !== null && $error !== '') {
            $this->app->session()->getFlashBag()->add('error', $error);
        }
        $this->redirectAdmin($redirectSuffix);
    }
}
