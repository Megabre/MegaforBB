<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use Carbon\Carbon;
use Forecor\Core\Application;
use Forecor\Core\SessionManager;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Kayıt, giriş, çıkış, mevcut kullanıcı. E-posta doğrulama ve admin onay akışı ile entegre.
 */
class AuthService
{
    public const SESSION_USER_ID = 'user_id';
    public const ROLE_MEMBER = 3;

    /** Kullanıcı adı sadece İngilizce harf, rakam ve alt çizgi içerebilir (URL ve profil linki için). Boşluk, Türkçe karakter ve özel karakter yasak. */
    public static function validateUsernameFormat(string $username): ?string
    {
        if ($username === '') {
            return lang('auth.username_min');
        }
        if (strlen($username) > 50) {
            return lang('auth.username_max');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return lang('auth.username_format_invalid');
        }
        return null;
    }

    public function __construct(
        protected Application $app
    ) {
    }

    public function user(): ?User
    {
        $session = SessionManager::get();
        $id = $session->get(self::SESSION_USER_ID);
        if ($id === null) {
            return null;
        }

        /** @var User|null $user */
        $user = User::find($id);

        if ($user && $user->is_banned) {
            return null;
        }
        if ($user && $user->closed_at !== null) {
            return null;
        }

        return $user;
    }

    public function login(string $login, string $password)
    {
        $login = trim($login);

        /** @var User|null $user */
        $user = User::where('username', $login)
            ->orWhere('email', $login)
            ->first();

        if (!$user) {
            return false;
        }

        if ($user->is_banned) {
            return false;
        }
        if ($user->closed_at !== null) {
            return 'closed';
        }
        if (!empty($user->is_suspended)) {
            return 'suspended';
        }

        if (!password_verify($password, $user->password_hash)) {
            return false;
        }

        if (class_exists(RtbhIpListService::class)) {
            $rtbh = new RtbhIpListService($this->app);
            $clientIp = SecurityService::clientIp();
            if ($rtbh->isEnabled() && $rtbh->isIpListed($clientIp)) {
                $act = $rtbh->getAction();
                if ($act === RtbhIpListService::ACTION_LOG_ONLY || $act === RtbhIpListService::ACTION_REDIRECT_URL) {
                    SecurityLogger::log('rtbh_match', [
                        'phase' => 'login',
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'client_ip' => $clientIp,
                    ]);
                } elseif ($act === RtbhIpListService::ACTION_BLOCK_REGISTER_AND_LOGIN) {
                    SecurityLogger::log('rtbh_login_blocked', [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'client_ip' => $clientIp,
                    ]);

                    return 'rtbh_blocked';
                }
            }
        }

        $requireEmailVerification = $this->app->getSetting('registration_require_email_verification', '0') === '1';
        if ($requireEmailVerification && (isset($user->email_verified_at) && $user->email_verified_at === null)) {
            return 'email_not_verified';
        }

        if (isset($user->approved_at) && $user->approved_at === null) {
            return 'pending_approval';
        }

        if ($this->app->getSetting('login_require_email_code', '0') === '1') {
            return 'require_email_code';
        }

        SessionManager::get()->set(self::SESSION_USER_ID, (int) $user->id);
        $this->updateLastActivity($user->id);
        return true;
    }

    /** Giriş 2FA: e-postaya gönderilen doğrulama kodu. */
    public function sendLoginCodeEmail(string $email, string $username, string $code): bool
    {
        $siteName = (string) core_config('app.name', 'MegaforBB');
        $replace = [
            'name' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            'username' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            'code' => htmlspecialchars($code, ENT_QUOTES, 'UTF-8'),
            'site_name' => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
        ];
        $templateService = new \App\Services\MailTemplateService($this->app);
        $subject = $templateService->getSubject('login_code', $replace);
        $body = $templateService->getBodyHtml('login_code', $replace);
        if ($subject === null) {
            $subject = $siteName . ' — ' . lang('auth.login_code_subject');
        }
        if ($body === null) {
            $body = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif">';
            $body .= '<p>' . lang('auth.login_code_hello', ['name' => $replace['name']]) . '</p>';
            $body .= '<p>' . lang('auth.login_code_body') . '</p>';
            $body .= '<p style="font-size:24px;letter-spacing:4px;font-weight:bold">' . $replace['code'] . '</p>';
            $body .= '<p>' . lang('auth.login_code_expiry') . '</p>';
            $body .= '<p>— ' . $replace['site_name'] . '</p></body></html>';
        }
        return $this->app->mail()->send($email, $subject, $body);
    }

    /** 2FA doğrulandıktan sonra girişi tamamla (session set + last_activity). */
    public function completeLogin(int $userId): void
    {
        SessionManager::get()->set(self::SESSION_USER_ID, $userId);
        $this->updateLastActivity($userId);
    }

    /** Çıkış */
    public function logout(): void
    {
        SessionManager::get()->invalidate();
    }

    /**
     * Askıya alınmış hesabı tekrar açar: login + şifre doğrulanır, is_suspended=0 yapılır ve giriş yapılır.
     * @return true başarılı, false hatalı giriş, 'closed' hesap kalıcı kapatılmış, 'not_suspended' zaten askıda değil
     */
    public function reactivateAccount(string $login, string $password)
    {
        $login = trim($login);
        $user = User::where('username', $login)->orWhere('email', $login)->first();
        if (!$user) {
            return false;
        }
        if ($user->closed_at !== null) {
            return 'closed';
        }
        if (empty($user->is_suspended)) {
            return 'not_suspended';
        }
        if (!password_verify($password, $user->password_hash)) {
            return false;
        }
        $user->is_suspended = 0;
        $user->suspended_at = null;
        $user->save();
        SessionManager::get()->set(self::SESSION_USER_ID, (int) $user->id);
        $this->updateLastActivity($user->id);
        return true;
    }

    /** Kayıt: username, email, password. Opsiyonel locale, inviteCode, first_name, last_name. Hata mesajı veya null (başarılı). */
    public function register(string $username, string $email, string $password, string $locale = 'tr', ?string $inviteCode = null, ?string $firstName = null, ?string $lastName = null): ?string
    {
        $username = trim($username);
        $email = trim($email);

        if (strlen($username) < 3) {
            return lang('auth.username_min');
        }
        $formatErr = self::validateUsernameFormat($username);
        if ($formatErr !== null) {
            return $formatErr;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return lang('auth.valid_email');
        }
        if (strlen($password) < 8) {
            return lang('auth.password_min');
        }

        $censorship = $this->app->censorship();
        $uCheck = $censorship->checkUsername($username);
        if (!$uCheck['allowed']) {
            return $uCheck['message'] ?? lang('auth.username_invalid');
        }
        $eCheck = $censorship->checkEmail($email);
        if (!$eCheck['allowed']) {
            return $eCheck['message'] ?? lang('auth.email_invalid');
        }

        $locale = in_array($locale, ['tr', 'en'], true) ? $locale : 'tr';

        $firstName = $firstName !== null ? trim($firstName) : '';
        $lastName = $lastName !== null ? trim($lastName) : '';
        $showFirst = $this->app->getSetting('registration_show_first_name', '1');
        $showLast = $this->app->getSetting('registration_show_last_name', '1');
        if ($showFirst === '2' && $firstName === '') {
            return lang('auth.first_name_required');
        }
        if ($showLast === '2' && $lastName === '') {
            return lang('auth.last_name_required');
        }

        $invitation = null;
        if ($inviteCode !== null && $inviteCode !== '') {
            $invitation = Invitation::where('code', $inviteCode)->whereNull('used_at')->first();
            if (!$invitation) {
                return lang('auth.invite_code_invalid');
            }
            if ($invitation->expires_at && $invitation->expires_at->isPast()) {
                return lang('auth.invite_code_expired');
            }
            if ($invitation->email !== null && $invitation->email !== $email) {
                return lang('auth.invite_code_email_mismatch');
            }
        }

        // Check exists
        $exists = User::where('username', $username)->orWhere('email', $email)->exists();
        if ($exists) {
            return lang('auth.username_email_taken');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            return lang('auth.password_hash_failed');
        }

        $requiresApproval = $this->app->getSetting('registration_requires_approval', '0') === '1';
        $requireEmailVerification = $this->app->getSetting('registration_require_email_verification', '0') === '1';

        $sfsPending = false;
        if (class_exists(StopForumSpamService::class)) {
            $sfs = new StopForumSpamService($this->app);
            $sfsPending = $sfs->registrationShouldRequireApproval($username, $email, SecurityService::clientIp());
        }

        $regIp = SecurityService::clientIp();
        $rtbhPending = false;
        if (class_exists(RtbhIpListService::class)) {
            $rtbh = new RtbhIpListService($this->app);
            if ($rtbh->isEnabled() && $rtbh->isIpListed($regIp)) {
                $act = $rtbh->getAction();
                if ($act === RtbhIpListService::ACTION_LOG_ONLY || $act === RtbhIpListService::ACTION_REDIRECT_URL) {
                    SecurityLogger::log('rtbh_match', [
                        'phase' => 'register',
                        'username' => $username,
                        'client_ip' => $regIp,
                    ]);
                } elseif ($act === RtbhIpListService::ACTION_BLOCK_REGISTER
                    || $act === RtbhIpListService::ACTION_BLOCK_REGISTER_AND_LOGIN) {
                    SecurityLogger::log('rtbh_register_blocked', [
                        'username' => $username,
                        'client_ip' => $regIp,
                    ]);

                    return lang('auth.rtbh_register_blocked');
                } elseif ($act === RtbhIpListService::ACTION_PENDING_APPROVAL) {
                    $rtbhPending = true;
                }
            }
        }

        try {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->password_hash = $hash;
            $user->role_id = self::ROLE_MEMBER;
            $user->locale = $locale;
            if (\Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder()->hasColumn('users', 'first_name')) {
                $user->first_name = $firstName !== '' ? $firstName : null;
                $user->last_name = $lastName !== '' ? $lastName : null;
            }
            if (!$requiresApproval && !$sfsPending && !$rtbhPending) {
                $user->approved_at = Carbon::now();
            }
            if ($requireEmailVerification) {
                $user->email_verified_at = null;
                $user->email_verification_token = bin2hex(random_bytes(32));
            } else {
                $user->email_verified_at = Carbon::now();
                $user->email_verification_token = null;
            }
            $user->save();
            if (function_exists('sef_service') && sef_service()->getMode() === 'random') {
                $user->url_key = sef_service()->generateUniqueUrlKeyForTable('users', 'url_key');
                $user->save();
            }
        } catch (\Exception $e) {
            return lang('auth.register_error', ['message' => $e->getMessage()]);
        }

        if ($invitation) {
            $invitation->update(['used_at' => Carbon::now(), 'used_by' => $user->id]);
        }

        $this->updateForumStatsMembers($user->id, $user->username);

        if ($requireEmailVerification && isset($user->email_verification_token)) {
            $this->sendVerificationEmail($user->email, $user->username, $user->email_verification_token);
        }

        return null;
    }

    /** Sends email verification link (after registration). */
    public function sendVerificationEmail(string $email, string $username, string $token): bool
    {
        if (function_exists('full_site_url')) {
            $verifyUrl = full_site_url('verify-email?token=' . rawurlencode($token));
        } else {
            $base = rtrim((string) core_config('app.url', ''), '/');
            if ($base === '') {
                $scheme = \App\Services\SecurityService::isHttpsRequest() ? 'https' : 'http';
                $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            }
            $verifyUrl = $base . '/verify-email?token=' . rawurlencode($token);
        }
        $siteName = (string) core_config('app.name', 'MegaforBB');
        $replace = [
            'name' => htmlspecialchars($username, ENT_QUOTES, 'UTF-8'),
            'verify_url' => $verifyUrl,
            'site_name' => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
        ];
        $templateService = new \App\Services\MailTemplateService($this->app);
        $subject = $templateService->getSubject('email_verification', $replace);
        $body = $templateService->getBodyHtml('email_verification', $replace);
        if ($subject === null) {
            $subject = $siteName . ' — ' . lang('auth.verify_email_subject');
        }
        if ($body === null) {
            $body = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif">';
            $body .= '<p>' . lang('auth.verify_email_hello', ['name' => $replace['name']]) . '</p>';
            $body .= '<p>' . lang('auth.verify_email_body') . '</p>';
            $body .= '<p><a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '">' . lang('auth.verify_email_link_text') . '</a></p>';
            $body .= '<p>' . lang('auth.verify_email_expiry') . '</p>';
            $body .= '<p>— ' . $replace['site_name'] . '</p></body></html>';
        }
        return $this->app->mail()->send($email, $subject, $body);
    }

    protected function updateLastActivity(int $userId): void
    {
        User::where('id', $userId)->update([
            'last_activity_at' => Carbon::now(),
            'last_ip' => SecurityService::clientIp(),
        ]);
    }

    protected function updateForumStatsMembers(int $userId, string $username): void
    {
        try {
            DB::table('forum_stats')->where('id', 1)->update([
                'total_members' => DB::raw('total_members + 1'),
                'last_member_id' => $userId,
                'last_member_username' => $username,
            ]);
        } catch (\Throwable $e) {
        }
    }
}
