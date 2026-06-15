<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\BackupService;

/**
 * Admin Araçlar: Yedekleme (DB + dosya). Listele, oluştur, indir, sil.
 */
class AdminBackupController extends AdminController
{
    private const CSRF_TOKEN = 'admin_backup';

    private function backupService(): BackupService
    {
        return new BackupService($this->app->getBasePath());
    }

    /**
     * Yedekleme sayfası: listele, PHP limit uyarısı, oluştur/sil butonları.
     */
    public function index(): string
    {
        $service = $this->backupService();
        $backups = $service->listBackups();
        $phpLimits = $service->getPhpLimits();
        $estimateDb = $service->estimateDbSize();
        $estimateFiles = $service->estimateFilesSize();
        $dbRisky = $service->isSizeRisky($estimateDb);
        $filesRisky = $service->isSizeRisky($estimateFiles);
        $zipAvailable = extension_loaded('zip');

        $adminPath = env('ADMIN_PATH', 'admin');

        return $this->view('backup/index', [
            'pageTitle'      => lang('admin.backup.title'),
            'backups'         => $backups,
            'phpLimits'       => $phpLimits,
            'estimateDb'      => $estimateDb,
            'estimateFiles'   => $estimateFiles,
            'dbRisky'         => $dbRisky,
            'filesRisky'      => $filesRisky,
            'zipAvailable'    => $zipAvailable,
            'csrfToken'       => core_csrf_token(self::CSRF_TOKEN),
            'adminPath'       => $adminPath,
            'urlBackup'       => core_url($adminPath . '/backup'),
        ]);
    }

    /**
     * POST: Veritabanı yedeği oluştur.
     */
    public function createDb(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('backup_error', lang('admin.rebuild.csrf_invalid'));
            $this->redirectToBackup();
        }

        $service = $this->backupService();
        $result = $service->createDbBackup();

        if ($result['success']) {
            $this->app->session()->getFlashBag()->add('backup_success', $result['message']);
            $this->app->session()->getFlashBag()->add('backup_remind_delete', '1'); // Yedekleri sil hatırlatması
        } else {
            $this->app->session()->getFlashBag()->add('backup_error', $result['message']);
        }
        $this->redirectToBackup();
    }

    /**
     * POST: Dosya yedeği oluştur.
     */
    public function createFiles(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('backup_error', lang('admin.rebuild.csrf_invalid'));
            $this->redirectToBackup();
        }

        $service = $this->backupService();
        $result = $service->createFileBackup();

        if ($result['success']) {
            $this->app->session()->getFlashBag()->add('backup_success', $result['message']);
            $this->app->session()->getFlashBag()->add('backup_remind_delete', '1');
        } else {
            $this->app->session()->getFlashBag()->add('backup_error', $result['message']);
        }
        $this->redirectToBackup();
    }

    /**
     * GET: Yedek dosyasını indir. ?file=xxx.zip (query string kullanılır; .zip path'te olursa Nginx statik dosyaya yönlendirip 404 verebilir).
     */
    public function download(): void
    {
        $filename = isset($_GET['file']) ? trim((string) $_GET['file']) : '';
        if ($filename === '') {
            http_response_code(400);
            echo '400 Bad Request';
            return;
        }
        $service = $this->backupService();
        $path = $service->getBackupPath($filename);
        if ($path === null || !is_file($path)) {
            http_response_code(404);
            echo '404 Not Found';
            return;
        }

        $mime = strpos($filename, '.zip') !== false ? 'application/zip' : 'application/sql';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    /**
     * POST: Tek yedek sil. Dosya adı POST ile gelir (path'te .zip olursa Nginx statik dosyaya yönlendirip 404 verebilir).
     */
    public function delete(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('backup_error', lang('admin.rebuild.csrf_invalid'));
            $this->redirectToBackup();
        }

        $filename = isset($_POST['file']) ? trim((string) $_POST['file']) : '';
        if ($filename === '') {
            $this->app->session()->getFlashBag()->add('backup_error', lang('admin.backup.delete_failed'));
            $this->redirectToBackup();
        }

        $service = $this->backupService();
        if ($service->deleteBackup($filename)) {
            $this->app->session()->getFlashBag()->add('backup_success', lang('admin.backup.deleted'));
        } else {
            $this->app->session()->getFlashBag()->add('backup_error', lang('admin.backup.delete_failed'));
        }
        $this->redirectToBackup();
    }

    /**
     * POST: Tüm yedekleri sil (güvenlik için yedek alındıktan sonra silme hatırlatması).
     */
    public function deleteAll(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('backup_error', lang('admin.rebuild.csrf_invalid'));
            $this->redirectToBackup();
        }

        $service = $this->backupService();
        $count = $service->deleteAllBackups();
        $this->app->session()->getFlashBag()->add('backup_success', lang('admin.backup.deleted_all', ['count' => $count]));
        $this->redirectToBackup();
    }

    private function redirectToBackup(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        header('Location: ' . core_url($adminPath . '/backup'), true, 302);
        exit;
    }
}
