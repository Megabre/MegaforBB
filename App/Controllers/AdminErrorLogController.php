<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ErrorLogger;

/**
 * Admin Araçlar: Hata logu görüntüleyici.
 * Log dosyası FTP'ye gerek kalmadan panelde okunaklı gösterilir.
 */
class AdminErrorLogController extends AdminController
{
    private const MAX_ENTRIES = 200;

    public function index(): string
    {
        $logPath = '';
        $entries = [];
        if (class_exists(ErrorLogger::class)) {
            ErrorLogger::setBasePath($this->app->getBasePath());
            $logPath = ErrorLogger::getLogPath();
            $entries = ErrorLogger::getRecentEntries(self::MAX_ENTRIES);
        }
        $levelCounts = [];
        foreach ($entries as $e) {
            $l = $e['level'] ?? 'OTHER';
            $levelCounts[$l] = ($levelCounts[$l] ?? 0) + 1;
        }

        return $this->view('error_log/index', [
            'pageTitle'   => 'Hata Logu',
            'logPath'     => $logPath,
            'entries'     => $entries,
            'levelCounts' => $levelCounts,
        ]);
    }
}
