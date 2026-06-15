<?php

declare(strict_types=1);

namespace App\Controllers;

use Forecor\Core\Application;

class LocaleController extends BaseController
{
    private const ALLOWED_LOCALES = ['tr', 'en'];

    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /** GET /set-locale?locale=tr — Misafir: sadece session (tarayıcı kapanınca sıfırlanır). Giriş yapmış: session + cookie. */
    public function switchGet(): string
    {
        $locale = trim((string) ($_GET['locale'] ?? ''));
        if ($locale !== '' && in_array($locale, self::ALLOWED_LOCALES, true)) {
            try {
                $session = \Forecor\Core\SessionManager::get();
                $session->set('locale', $locale);
                $this->app->translator()->setLocale($locale);
                $cookiePath = core_config('session.path', '/');
                setcookie('locale', $locale, time() + (365 * 24 * 3600), $cookiePath !== '' ? $cookiePath : '/', '', false, true);
            } catch (\Throwable $e) {
                // Session henüz başlatılmamış olabilir
            }
        }
        $referer = isset($_SERVER['HTTP_REFERER']) ? core_redirect_url_safe($_SERVER['HTTP_REFERER']) : core_url('');
        $this->redirect($referer);
        return '';
    }

    public function switch(): string
    {
        if (!core_csrf_valid('csrf', (string) ($_POST['_token'] ?? ''))) {
            http_response_code(403);
            return '';
        }

        $locale = trim($_POST['locale'] ?? '');
        if ($locale !== '' && in_array($locale, self::ALLOWED_LOCALES, true)) {
            try {
                \Forecor\Core\SessionManager::get()->set('locale', $locale);
                $this->app->translator()->setLocale($locale);
                $cookiePath = core_config('session.path', '/');
                setcookie('locale', $locale, time() + (365 * 24 * 3600), $cookiePath !== '' ? $cookiePath : '/', '', false, true);
            } catch (\Throwable $e) {
                // Session henüz başlatılmamış olabilir
            }
        }

        $referer = isset($_SERVER['HTTP_REFERER']) ? core_redirect_url_safe($_SERVER['HTTP_REFERER']) : core_url('');
        $this->redirect($referer);
        return '';
    }
}
