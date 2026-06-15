<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PasswordReset;
use App\Models\User;

/**
 * Password reset: request form, email sending, new password form and save.
 * Token and expiry managed via PasswordReset model.
 */
class PasswordResetController extends BaseController
{
    private const TOKEN_EXPIRY_MINUTES = 60;

    public function forgotForm(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        $bag = $this->app->session()->getFlashBag();
        $error = $bag->get('forgot_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $success = $bag->get('forgot_success');
        $success = is_array($success) ? ($success[0] ?? '') : $success;
        return $this->layout('forgot_password', [
            'pageTitle' => lang('auth.forgot.page_title'),
            'mode'      => 'forgot',
            'error'     => $error,
            'success'   => $success,
        ], false);
    }

    public function forgot(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        if (!core_csrf_valid('forgot_password', (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('forgot_error', lang('auth.forgot.invalid_csrf'));
            $this->redirect(core_url('forgot-password'));
            return '';
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $user = $email !== '' ? User::where('email', $email)->first(['id', 'email', 'username']) : null;
        if ($user) {
            $token = bin2hex(random_bytes(32));
            PasswordReset::deleteByEmail($user->email);
            PasswordReset::create([
                'email'      => $user->email,
                'token'      => $token,
                'created_at' => \now(),
            ]);
            if (function_exists('full_site_url')) {
                $resetUrl = full_site_url('reset-password?token=' . rawurlencode($token));
            } else {
                $base = rtrim((string) core_config('app.url', ''), '/');
                if ($base === '') {
                    $scheme = \App\Services\SecurityService::isHttpsRequest() ? 'https' : 'http';
                    $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                }
                $resetUrl = $base . '/reset-password?token=' . rawurlencode($token);
            }
            $siteName = $this->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB');
            $replace = ['url' => $resetUrl, 'site' => $siteName, 'site_name' => $siteName];
            $templateService = new \App\Services\MailTemplateService($this->app);
            $subject = $templateService->getSubject('password_reset', $replace);
            $bodyHtml = $templateService->getBodyHtml('password_reset', $replace);
            if ($subject === null) {
                $subject = lang('auth.forgot.email_subject', ['site' => $siteName]);
            }
            if ($bodyHtml === null) {
                $bodyHtml = lang('auth.forgot.email_body_html', ['url' => htmlspecialchars($resetUrl)]);
            }
            $bodyText = strip_tags($bodyHtml);
            $mail = new \App\Services\MailService($this->app);
            $mail->send($user->email, $subject, $bodyHtml, $bodyText);
            if (class_exists(\App\Services\SecurityNotificationService::class)) {
                try {
                    $ip = \App\Services\SecurityService::clientIp();
                    $svc = new \App\Services\SecurityNotificationService($this->app);
                    $svc->sendPasswordResetRequestNotification($user, $ip);
                } catch (\Throwable $e) {
                }
            }
        }
        $this->app->session()->getFlashBag()->add('forgot_success', lang('auth.forgot.success_sent'));
        $this->redirect(core_url('forgot-password'));
        return '';
    }

    public function resetForm(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            $this->app->session()->getFlashBag()->add('forgot_error', lang('auth.forgot.invalid_or_missing_link'));
            $this->redirect(core_url('forgot-password'));
            return '';
        }
        $reset = PasswordReset::findValidToken($token, self::TOKEN_EXPIRY_MINUTES);
        if (!$reset) {
            $this->app->session()->getFlashBag()->add('forgot_error', lang('auth.forgot.invalid_or_expired_link'));
            $this->redirect(core_url('forgot-password'));
            return '';
        }
        $bag = $this->app->session()->getFlashBag();
        $error = $bag->get('reset_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        return $this->layout('forgot_password', [
            'pageTitle' => lang('auth.reset.page_title'),
            'mode'      => 'reset',
            'token'     => $token,
            'error'     => $error,
            'success'   => '',
        ], false);
    }

    public function reset(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        if (!core_csrf_valid('reset_password', (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('reset_error', lang('auth.reset.invalid_csrf'));
            $this->redirect(core_url('forgot-password'));
            return '';
        }
        $token = trim((string) ($_POST['token'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        if ($password !== $passwordConfirm) {
            $this->app->session()->getFlashBag()->add('reset_error', lang('auth.reset.passwords_mismatch'));
            $this->redirect(core_url('reset-password?token=' . rawurlencode($token)));
            return '';
        }
        if (strlen($password) < 6) {
            $this->app->session()->getFlashBag()->add('reset_error', lang('auth.reset.password_min'));
            $this->redirect(core_url('reset-password?token=' . rawurlencode($token)));
            return '';
        }
        $reset = PasswordReset::findValidToken($token, self::TOKEN_EXPIRY_MINUTES);
        if (!$reset) {
            $this->app->session()->getFlashBag()->add('forgot_error', lang('auth.forgot.invalid_or_expired_link'));
            $this->redirect(core_url('forgot-password'));
            return '';
        }
        $user = User::where('email', $reset->email)->first();
        if (!$user) {
            $this->app->session()->getFlashBag()->add('forgot_error', lang('auth.reset.user_not_found'));
            $this->redirect(core_url('forgot-password'));
            return '';
        }
        $user->update(['password_hash' => password_hash($password, PASSWORD_BCRYPT)]);
        PasswordReset::deleteByEmail($reset->email);
        $this->app->session()->getFlashBag()->add('register_success', lang('auth.reset.password_updated_success'));
        $this->redirect(core_url('login'));
        return '';
    }
}
