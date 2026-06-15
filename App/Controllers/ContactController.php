<?php

declare(strict_types=1);

namespace App\Controllers;

class ContactController extends BaseController
{
    public function index(): string
    {
        $captcha = $this->app->captcha();
        $captchaConfig = $captcha->getWidgetConfig();
        return $this->layout('contact/index', [
            'pageTitle' => lang('contact.page_title'),
            'captcha_show' => $captcha->enabledOnContact(),
            'captcha_provider' => $captchaConfig['provider'],
            'captcha_site_key' => $captchaConfig['site_key'],
            'recaptcha_version' => $captchaConfig['recaptcha_version'],
        ], false);
    }

    public function submit(): string
    {
        if (!core_csrf_valid('contact', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('contact_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('iletisim'));
            return '';
        }

        // Validation
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($name === '' || $email === '' || $message === '') {
            $this->app->session()->getFlashBag()->add('contact_error', lang('contact.fill_all'));
            $this->redirect(core_url('iletisim'));
            return '';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->app->session()->getFlashBag()->add('contact_error', lang('contact.valid_email'));
            $this->redirect(core_url('iletisim'));
            return '';
        }

        // Captcha (sadece admin "İletişim formunda captcha" açtıysa)
        $captcha = $this->app->captcha();
        if ($captcha->enabledOnContact()) {
            $token = trim((string) ($_POST[$captcha->getResponseFieldName()] ?? ''));
            if (!$captcha->verify($token, \App\Services\SecurityService::clientIp())) {
                $this->app->session()->getFlashBag()->add('contact_error', lang('contact.captcha_failed'));
                $this->redirect(core_url('iletisim'));
                return '';
            }
        }

        try {
            \App\Models\ContactMessage::create([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'ip_address' => \App\Services\SecurityService::clientIp(),
                'created_at' => \now(),
            ]);

            $this->app->session()->getFlashBag()->add('contact_success', lang('contact.success'));
            $this->redirect(core_url('iletisim'));
            return '';
        } catch (\Exception $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('contact_error', lang('contact.error'));
            $this->redirect(core_url('iletisim'));
            return '';
        }
    }
}
