<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\FileVerificationService;
use App\Services\VersionCheckService;
use App\Version;

/**
 * Admin: Dosya doğrulama (XenForo tarzı).
 * GitHub sadece uzak version.json kaynağıdır; manifest tamamen lokal olarak tutulur.
 */
class AdminFileVerificationController extends AdminController
{
    private const CSRF_TOKEN = 'admin_file_verification';

    public function index(): string
    {
        $basePath = $this->app->getBasePath();
        $service = new FileVerificationService($basePath);
        $version = Version::VERSION;
        $manifestPath = $service->getManifestPath($version);
        $manifestExists = is_file($manifestPath);
        $remoteFileStatus = VersionCheckService::getFileVerificationStatus();
        $remoteVersionPayload = VersionCheckService::getRemoteVersionPayload();

        $result = null;
        $runCheck = isset($_GET['run']) && $_GET['run'] === '1';
        if ($runCheck && $manifestExists) {
            $result = $service->verify($version);
        } elseif ($runCheck && !$manifestExists) {
            $result = [
                'success' => false,
                'error' => 'manifest_not_found',
                'message' => 'Bu sürüm için manifest yok. Önce aşağıdaki "GitHub manifestini senkronize et" butonunu kullanın.',
                'version' => $version,
                'total' => 0,
                'ok' => [],
                'modified' => [],
                'missing' => [],
                'unexpected' => [],
            ];
        }

        $adminPath = env('ADMIN_PATH', 'admin');
        $flashOk = $this->app->session()->getFlashBag()->get('file_verification_ok');
        $flashOk = is_array($flashOk) ? ($flashOk[0] ?? null) : $flashOk;
        $flashError = $this->app->session()->getFlashBag()->get('file_verification_error');
        $flashError = is_array($flashError) ? ($flashError[0] ?? null) : $flashError;

        return $this->view('file_verification/index', [
            'pageTitle' => lang('admin.file_verification.title'),
            'adminPath' => $adminPath,
            'version' => $version,
            'manifestExists' => $manifestExists,
            'result' => $result,
            'remoteFileStatus' => $remoteFileStatus,
            'remoteVersion' => $remoteVersionPayload,
            'flashFileVerificationOk' => $flashOk,
            'flashFileVerificationError' => $flashError,
        ]);
    }

    public function syncManifest(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/file-verification'));
            return;
        }

        $service = new FileVerificationService($this->app->getBasePath());
        $generated = $service->generateManifest(Version::VERSION);

        if ($generated['success'] ?? false) {
            $this->app->session()->getFlashBag()->add(
                'file_verification_ok',
                lang('admin.file_verification.manifest_generated', [
                    'count' => (int) ($generated['file_count'] ?? 0),
                    'version' => Version::VERSION,
                ])
            );
        } else {
            $this->app->session()->getFlashBag()->add(
                'file_verification_error',
                lang('admin.file_verification.manifest_error')
            );
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/file-verification'));
    }

    /** Geriye dönük uyumluluk: eski route hâlâ bu metoda gelir. */
    public function generateManifest(): void
    {
        $this->syncManifest();
    }
}
