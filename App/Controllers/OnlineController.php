<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Çevrimiçi sayfası: üyeler, ziyaretçiler ve botlar (son 15 dakika).
 */
class OnlineController extends BaseController
{
    public function index(): string
    {
        $svc = $this->layoutService();
        $onlineData = $svc->getOnlinePageData(15);
        $stats = $svc->getStats();

        return $this->layout('online/index', [
            'pageTitle' => lang('online.page_title'),
            'online' => $onlineData,
            'stats' => $stats,
        ], false);
    }
}
