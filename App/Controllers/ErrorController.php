<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Hata sayfaları: 404 davranışı ayarlara göre (sayfa göster veya URL'ye yönlendir).
 */
class ErrorController extends BaseController
{
    /**
     * Route eşleşmediğinde veya 404 durumunda çağrılır.
     * Ayara göre 404 sayfası gösterir veya belirlenen URL'ye yönlendirir.
     */
    public function notFound(): string
    {
        $action = $this->getSetting('error_404_action', 'page');
        if ($action === 'redirect') {
            $url = trim((string) $this->getSetting('error_404_redirect_url', ''));
            if ($url === '') {
                $url = core_url('');
            } else {
                if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
                    $url = core_url(ltrim($url, '/'));
                }
            }
            header('Location: ' . $url, true, 302);
            exit;
        }
        http_response_code(404);
        return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
    }
}
