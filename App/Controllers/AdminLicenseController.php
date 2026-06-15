<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LicenseService;

/**
 * Admin: Botble lisans yönetimi.
 * Route: /{adminPath}/license
 */
class AdminLicenseController extends AdminController
{
    private const CSRF_TOKEN = 'admin_license';

    public function index(): string
    {
        $service = new LicenseService();
        $status  = $service->getStatus();

        return $this->view('license/index', [
            'pageTitle' => 'Lisans Yönetimi',
            'adminPath' => env('ADMIN_PATH', 'admin'),
            'status'    => $status,
            'flash_ok'  => $this->popFlash('license_ok'),
            'flash_err' => $this->popFlash('license_err'),
        ]);
    }

    public function activate(): void
    {
        $this->requireCsrf(self::CSRF_TOKEN);

        $result = (new LicenseService())->activate();

        if ($result['success']) {
            $this->flash('license_ok', $result['message']);
        } else {
            $this->flash('license_err', $result['message']);
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/license'));
    }

    public function deactivate(): void
    {
        $this->requireCsrf(self::CSRF_TOKEN);

        $result = (new LicenseService())->deactivate();

        if ($result['success']) {
            $this->flash('license_ok', $result['message']);
        } else {
            $this->flash('license_err', $result['message']);
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/license'));
    }

    public function recheck(): void
    {
        $this->requireCsrf(self::CSRF_TOKEN);

        $valid = (new LicenseService())->verify(forceRemote: true);

        if ($valid) {
            $this->flash('license_ok', 'Lisans doğrulandı ve güncellendi.');
        } else {
            $this->flash('license_err', 'Lisans doğrulanamadı. Lütfen lisans anahtarını ve alan adını kontrol edin.');
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/license'));
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────

    private function requireCsrf(string $key): void
    {
        $token = (string) ($_POST['_token'] ?? '');
        if (! core_csrf_valid($key, $token)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/license'));
        }
    }

    private function flash(string $key, string $message): void
    {
        $this->app->session()->getFlashBag()->add($key, $message);
    }

    private function popFlash(string $key): ?string
    {
        $messages = $this->app->session()->getFlashBag()->get($key);
        return is_array($messages) ? ($messages[0] ?? null) : ($messages ?: null);
    }
}
