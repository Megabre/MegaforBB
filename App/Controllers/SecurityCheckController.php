<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Saldırı algılandığında gösterilen güvenlik doğrulama sayfası. Captcha geçen IP whitelist'e alınır.
 */
class SecurityCheckController extends BaseController
{
    public function show(): string
    {
        $captcha = $this->app->captcha();
        $config = $captcha->getWidgetConfig();
        $captchaEnabled = $captcha->getProvider() !== 'none' && ($config['site_key'] ?? '') !== '';
        $bag = $this->app->session()->getFlashBag();
        $error = $bag->get('security_check_error');
        $error = is_array($error) ? ($error[0] ?? '') : ($error ?? '');

        return $this->app->twig('frontend')->render('security-check.html.twig', [
            'pageTitle'        => lang('security_check.title'),
            'user'            => $this->app->auth()->user(),
            'locale'          => $this->locale(),
            'captcha_show'     => $captchaEnabled,
            'captcha_provider' => $config['provider'] ?? 'none',
            'captcha_site_key' => $config['site_key'] ?? '',
            'recaptcha_version' => $config['recaptcha_version'] ?? 'v2',
            'return_url'       => isset($_GET['return']) ? core_redirect_url_safe($_GET['return']) : core_url(''),
            'error'           => $error,
        ]);
    }

    public function verify(): string
    {
        $ip = \App\Services\SecurityService::clientIp();
        $captcha = $this->app->captcha();
        $config = $captcha->getWidgetConfig();
        $captchaEnabled = $captcha->getProvider() !== 'none' && ($config['site_key'] ?? '') !== '';

        if ($captchaEnabled) {
            $token = trim((string) ($_POST[$captcha->getResponseFieldName()] ?? ''));
            if (!$captcha->verify($token, $ip)) {
                $this->app->session()->getFlashBag()->add('security_check_error', lang('security_check.captcha_failed'));
                $this->redirect(core_url('security-check'));
                return '';
            }
        }

        $this->app->security()->recordSecurityCheckPassed($ip);
        $returnUrl = isset($_POST['return_url']) && $_POST['return_url'] !== '' ? $_POST['return_url'] : core_url('');
        $returnUrl = core_redirect_url_safe($returnUrl);
        $this->redirect($returnUrl);
        return '';
    }
}
