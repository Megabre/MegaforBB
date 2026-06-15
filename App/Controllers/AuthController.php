<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\UserCustomField;
use App\Models\UserFieldDefinition;

/**
 * Login, register, logout. CSRF, validasyon, session.
 */
class AuthController extends BaseController
{
    public function loginForm(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        $this->disableBrowserCacheForHtml();
        $bag = $this->app->session()->getFlashBag();
        $error = $bag->get('auth_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $success = $bag->get('register_success');
        $success = is_array($success) ? ($success[0] ?? '') : ($success ?? '');
        $captcha = $this->app->captcha();
        $captchaConfig = $captcha->getWidgetConfig();
        return $this->layout('login', [
            'pageTitle' => core__('auth.login_title'),
            'error'     => $error,
            'success'   => $success,
            'captcha_show' => $captcha->enabledOnLogin(),
            'captcha_provider' => $captchaConfig['provider'],
            'captcha_site_key' => $captchaConfig['site_key'],
            'recaptcha_version' => $captchaConfig['recaptcha_version'],
        ], false);
    }

    public function login(): string
    {
        $ip = \App\Services\SecurityService::clientIp();
        $captcha = $this->app->captcha();
        if ($captcha->enabledOnLogin()) {
            $token = trim((string) ($_POST[$captcha->getResponseFieldName()] ?? ''));
            if (!$captcha->verify($token, $ip)) {
                $this->flashError('auth_error', lang('auth.captcha_failed'));
                $this->redirect(core_url('login'));
                return '';
            }
        }
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_LOGIN, null, $ip);
        if (!$r['allowed']) {
            $this->flashError('auth_error', $r['message']);
            $this->redirect(core_url('login'));
            return '';
        }
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        if (!$this->validateCsrf('login', (string) ($_POST['_token'] ?? ''))) {
            $this->flashError('auth_error', lang('auth.invalid_csrf'));
            $this->redirect(core_url('login'));
            return '';
        }
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($login === '' || $password === '') {
            $this->flashError('auth_error', lang('auth.credentials_required'));
            $this->redirect(core_url('login'));
            return '';
        }
        $loginResult = $this->app->auth()->login($login, $password);
        if ($loginResult === 'pending_approval') {
            $this->flashError('auth_error', lang('auth.not_approved'));
            $this->redirect(core_url('login'));
            return '';
        }
        if ($loginResult === 'closed') {
            $this->flashError('auth_error', lang('auth.account_closed'));
            $this->redirect(core_url('login'));
            return '';
        }
        if ($loginResult === 'suspended') {
            $this->flashError('auth_error', lang('auth.account_suspended'));
            $this->app->session()->getFlashBag()->add('auth_show_reactivate', '1');
            $this->redirect(core_url('login'));
            return '';
        }
        if ($loginResult === 'email_not_verified') {
            $this->flashError('auth_error', lang('auth.verify_email'));
            $this->redirect(core_url('login'));
            return '';
        }
        if ($loginResult === 'rtbh_blocked') {
            $this->flashError('auth_error', lang('auth.rtbh_login_blocked'));
            $this->redirect(core_url('login'));
            return '';
        }
        if ($loginResult === 'require_email_code') {
            $user = User::where('username', $login)->orWhere('email', $login)->first(['id', 'email', 'username']);
            if (!$user) {
                $this->flashError('auth_error', lang('auth.wrong_credentials'));
                $this->redirect(core_url('login'));
                return '';
            }
            $code = (string) random_int(100000, 999999);
            $expiresAt = time() + 600; // 10 dakika
            $session = $this->app->session();
            $session->set('login_2fa_user_id', (int) $user->id);
            $session->set('login_2fa_code', $code);
            $session->set('login_2fa_expires_at', $expiresAt);
            $this->app->auth()->sendLoginCodeEmail($user->email, $user->username, $code);
            $this->app->session()->getFlashBag()->add('login_code_sent', lang('auth.login_code_sent'));
            $this->redirect(core_url('login/verify'));
            return '';
        }
        if ($loginResult !== true) {
            \App\Services\SecurityLogger::log('login_failed', ['ip' => $ip, 'login' => $login]);
            $this->flashError('auth_error', lang('auth.wrong_credentials'));
            $this->redirect(core_url('login'));
            return '';
        }
        $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_LOGIN, null, $ip);
        $user = User::where('username', $login)->orWhere('email', $login)->first(['id', 'email', 'username']);
        if ($user && class_exists(\App\Services\SecurityNotificationService::class)) {
            try {
                $svc = new \App\Services\SecurityNotificationService($this->app);
                $svc->sendLoginNotification($user, $ip);
            } catch (\Throwable $e) {
            }
        }
        $session = $this->app->session();
        if (method_exists($session, 'migrate')) {
            $session->migrate(true);
        }
        $this->redirect(core_url(''));
        return '';
    }

    /** Giriş 2FA: e-postaya giden kodu girme sayfası. */
    public function loginVerifyForm(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        $session = $this->app->session();
        $userId = $session->get('login_2fa_user_id');
        $expiresAt = (int) $session->get('login_2fa_expires_at', 0);
        if (!$userId || $expiresAt < time()) {
            $this->flashError('auth_error', lang('auth.login_code_invalid'));
            $this->redirect(core_url('login'));
            return '';
        }
        $success = $this->app->session()->getFlashBag()->get('login_code_sent');
        $success = is_array($success) ? ($success[0] ?? '') : (string) ($success ?? '');
        $error = $this->app->session()->getFlashBag()->get('auth_error');
        $error = is_array($error) ? ($error[0] ?? '') : (string) ($error ?? '');
        return $this->layout('login_verify', [
            'pageTitle' => lang('auth.login_verify_title'),
            'success' => $success,
            'error' => $error,
        ], false);
    }

    /** Giriş 2FA: kod doğrula, session set et, giriş tamamla. */
    public function loginVerify(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        if (!$this->validateCsrf('login_verify', (string) ($_POST['_token'] ?? ''))) {
            $this->flashError('auth_error', lang('auth.invalid_csrf'));
            $this->redirect(core_url('login/verify'));
            return '';
        }
        $session = $this->app->session();
        $userId = (int) $session->get('login_2fa_user_id', 0);
        $storedCode = (string) $session->get('login_2fa_code', '');
        $expiresAt = (int) $session->get('login_2fa_expires_at', 0);
        $code = trim((string) ($_POST['code'] ?? ''));
        if (!$userId || $expiresAt < time() || $code === '' || $storedCode === '' || !hash_equals($storedCode, $code)) {
            $this->flashError('auth_error', lang('auth.login_code_invalid'));
            $this->redirect(core_url('login/verify'));
            return '';
        }
        $session->remove('login_2fa_user_id');
        $session->remove('login_2fa_code');
        $session->remove('login_2fa_expires_at');
        if (method_exists($session, 'migrate')) {
            $session->migrate(true);
        }
        $this->app->auth()->completeLogin($userId);
        $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_LOGIN, null, \App\Services\SecurityService::clientIp());
        $user = $this->app->auth()->user();
        if ($user) {
            if (class_exists(\App\Services\SecurityNotificationService::class)) {
                try {
                    $svc = new \App\Services\SecurityNotificationService($this->app);
                    $svc->sendLoginNotification($user, \App\Services\SecurityService::clientIp());
                } catch (\Throwable $e) {
                }
            }
            try {
                $this->app->event()->dispatch(new \App\Events\UserLogin($user), \App\Events\UserLogin::NAME);
            } catch (\Throwable $e) {
            }
            if (!empty($user->locale) && in_array($user->locale, ['tr', 'en'], true)) {
                $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
                setcookie('locale', $user->locale, time() + (365 * 24 * 3600), '/', '', $secure, true);
            }
            if (class_exists(\App\Services\AnalyticsLogger::class) && \App\Services\AnalyticsLogger::isEnabled()) {
                \App\Services\AnalyticsLogger::log('login', ['ip' => \App\Services\SecurityService::clientIp(), 'user_id' => $user->id, 'username' => $user->username]);
            }
        }
        $this->redirect(core_url(''));
        return '';
    }

    public function registerForm(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        $this->disableBrowserCacheForHtml();
        $captcha = $this->app->captcha();
        $captchaConfig = $captcha->getWidgetConfig();
        $error = $this->app->session()->getFlashBag()->get('register_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        $customDefinitions = $this->getRegistrationCustomFields();
        $requireInvite = $this->getSetting('registration_requires_invite', '0') === '1';
        $showFirstName = $this->getSetting('registration_show_first_name', '1');
        $showLastName = $this->getSetting('registration_show_last_name', '1');
        return $this->layout('register', [
            'pageTitle' => core__('auth.register_title'),
            'error'     => $error,
            'requireInvite' => $requireInvite,
            'registration_show_first_name' => $showFirstName,
            'registration_show_last_name' => $showLastName,
            'customDefinitions' => $customDefinitions,
            'captcha_show' => $captcha->enabledOnRegister(),
            'captcha_provider' => $captchaConfig['provider'],
            'captcha_site_key' => $captchaConfig['site_key'],
            'recaptcha_version' => $captchaConfig['recaptcha_version'],
        ], false);
    }

    public function register(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        if (!$this->validateCsrf('register', (string) ($_POST['_token'] ?? ''))) {
            $this->flashError('register_error', lang('auth.register_csrf_invalid'));
            $this->redirect(core_url('register'));
            return '';
        }
        $ip = \App\Services\SecurityService::clientIp();
        $captcha = $this->app->captcha();
        if ($captcha->enabledOnRegister()) {
            $token = trim((string) ($_POST[$captcha->getResponseFieldName()] ?? ''));
            if (!$captcha->verify($token, $ip)) {
                $this->flashError('register_error', lang('auth.register_captcha_failed'));
                $this->redirect(core_url('register'));
                return '';
            }
        }
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_REGISTER, null, $ip);
        if (!$r['allowed']) {
            $this->flashError('register_error', $r['message']);
            $this->redirect(core_url('register'));
            return '';
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
        $locale = trim((string) ($_POST['locale'] ?? 'tr'));
        $inviteCode = trim((string) ($_POST['invite_code'] ?? ''));
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        if (!in_array($locale, ['tr', 'en'], true)) {
            $locale = 'tr';
        }
        $requireInvite = $this->getSetting('registration_requires_invite', '0') === '1';
        if ($requireInvite && $inviteCode === '') {
            $this->flashError('register_error', lang('auth.invite_code_required'));
            $this->redirect(core_url('register'));
            return '';
        }
        if ($password !== $confirm) {
            $this->flashError('register_error', lang('auth.password_mismatch'));
            $this->redirect(core_url('register'));
            return '';
        }
        $err = $this->app->auth()->register($username, $email, $password, $locale, $requireInvite ? $inviteCode : null, $firstName !== '' ? $firstName : null, $lastName !== '' ? $lastName : null);
        if ($err !== null) {
            $this->flashError('register_error', $err);
            $this->redirect(core_url('register'));
            return '';
        }
        $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_REGISTER, null, $ip);
        $this->saveRegistrationCustomFields($username);
        $registeredUser = User::where('username', $username)->first();
        if ($registeredUser) {
            try {
                $this->app->event()->dispatch(new \App\Events\UserRegistered($registeredUser), \App\Events\UserRegistered::NAME);
            } catch (\Throwable $e) {
                // Eklenti hatası kayıt akışını bozmasın
            }
        }
        if (class_exists(\App\Services\AnalyticsLogger::class) && \App\Services\AnalyticsLogger::isEnabled()) {
            \App\Services\AnalyticsLogger::log('register', ['ip' => $ip, 'username' => $username]);
        }
        $requiresApproval = $this->getSetting('registration_requires_approval') === '1';
        $requireEmailVerification = $this->getSetting('registration_require_email_verification') === '1';
        $pendingApproval = $registeredUser && ($registeredUser->approved_at === null);
        if ($requireEmailVerification || $requiresApproval || $pendingApproval) {
            $this->redirect(core_url('register/pending'));
            return '';
        }
        $this->app->session()->getFlashBag()->add('register_success', lang('auth.register_success'));
        $this->redirect(core_url('login'));
        return '';
    }

    private function getRegistrationCustomFields(): array
    {
        try {
            return UserFieldDefinition::where('show_on_registration', 1)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'field_key', 'field_type', 'field_options', 'is_required'])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function saveRegistrationCustomFields(string $username): void
    {
        try {
            $user = User::where('username', $username)->first(['id']);
            if (!$user) {
                return;
            }
            $defs = UserFieldDefinition::where('show_on_registration', 1)->get(['id', 'field_key']);
            foreach ($defs as $def) {
                $value = trim((string)($_POST['custom_' . $def->field_key] ?? ''));
                UserCustomField::updateOrInsert(
                    ['user_id' => $user->id, 'field_key' => $def->field_key],
                    ['field_value' => $value ?: null]
                );
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** Bekleme salonu: e-posta doğrulama ve/veya yönetici onayı bekleyen kullanıcıya gösterilir. */
    public function registerPending(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        $requireEmail = $this->getSetting('registration_require_email_verification') === '1';
        $requireApproval = $this->getSetting('registration_requires_approval') === '1';
        return $this->layout('register_pending', [
            'pageTitle' => lang('auth.pending_title'),
            'require_email_verification' => $requireEmail,
            'require_approval' => $requireApproval,
        ], false);
    }

    /** E-posta doğrulama linki (token ile). */
    public function verifyEmail(): string
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            $this->app->session()->getFlashBag()->add('auth_error', lang('auth.invalid_verify_link'));
            $this->redirect(core_url('login'));
            return '';
        }
        $user = User::where('email_verification_token', $token)->first(['id', 'email', 'username']);
        if (!$user) {
            $this->app->session()->getFlashBag()->add('auth_error', lang('auth.expired_verify_link'));
            $this->redirect(core_url('login'));
            return '';
        }
        User::where('id', $user->id)->update(['email_verified_at' => \now(), 'email_verification_token' => null]);
        $this->app->session()->getFlashBag()->add('register_success', lang('auth.email_verified'));
        $this->redirect(core_url('login'));
        return '';
    }

    /** Askıya alınmış hesabı tekrar açma formu */
    public function reactivateForm(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        $error = $this->app->session()->getFlashBag()->get('reactivate_error');
        $error = is_array($error) ? ($error[0] ?? '') : $error;
        return $this->layout('reactivate-account', [
            'pageTitle' => lang('auth.reactivate_title'),
            'error' => $error,
        ], false);
    }

    /** Askıya alınmış hesabı tekrar aç: login + şifre ile doğrula, askıyı kaldır, giriş yap */
    public function reactivate(): string
    {
        if ($this->app->auth()->user()) {
            $this->redirect(core_url(''));
            return '';
        }
        if (!$this->validateCsrf('reactivate', (string) ($_POST['_token'] ?? ''))) {
            $this->flashError('reactivate_error', lang('auth.invalid_csrf'));
            $this->redirect(core_url('reactivate-account'));
            return '';
        }
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($login === '' || $password === '') {
            $this->flashError('reactivate_error', lang('auth.credentials_required'));
            $this->redirect(core_url('reactivate-account'));
            return '';
        }
        $result = $this->app->auth()->reactivateAccount($login, $password);
        if ($result === 'closed') {
            $this->flashError('reactivate_error', lang('auth.account_closed'));
            $this->redirect(core_url('reactivate-account'));
            return '';
        }
        if ($result === 'not_suspended') {
            $this->flashError('reactivate_error', lang('auth.reactivate_not_suspended'));
            $this->redirect(core_url('reactivate-account'));
            return '';
        }
        if ($result !== true) {
            $this->flashError('reactivate_error', lang('auth.wrong_credentials'));
            $this->redirect(core_url('reactivate-account'));
            return '';
        }
        $this->app->session()->getFlashBag()->add('auth_success', lang('auth.reactivate_success'));
        $this->redirect(core_url(''));
        return '';
    }

    public function logout(): string
    {
        $user = $this->app->auth()->user();
        $this->app->auth()->logout();
        if (class_exists(\App\Services\AnalyticsLogger::class) && \App\Services\AnalyticsLogger::isEnabled() && $user) {
            \App\Services\AnalyticsLogger::log('logout', ['user_id' => $user->id, 'username' => $user->username]);
        }
        $this->redirect(core_url(''));
        return '';
    }

    protected function validateCsrf(string $tokenId, string $value): bool
    {
        return core_csrf_valid($tokenId, $value);
    }

    protected function flashError(string $key, string $message): void
    {
        $this->app->session()->getFlashBag()->add($key, $message);
    }
}
