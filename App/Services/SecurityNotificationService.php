<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Forecor\Core\Application;

/**
 * Giriş ve şifre sıfırlama talebi sonrası kullanıcıya güvenlik bildirim e-postası gönderir.
 * IP, konum ve ISP bilgisi (ip-api.com) ile zenginleştirilir.
 */
class SecurityNotificationService
{
    private Application $app;
    private const IP_API_TIMEOUT = 3;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isEnabled(): bool
    {
        return $this->app->getSetting('security_notification_email_enabled', '0') === '1';
    }

    /**
     * IP için konum ve ISP bilgisini döndürür (ip-api.com, timeout ile).
     * @return array{country: string, isp: string}
     */
    public function getIpInfo(string $ip): array
    {
        $result = ['country' => '—', 'isp' => '—'];
        if ($ip === '' || $ip === '127.0.0.1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return $result;
        }
        try {
            $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=country,isp&lang=en';
            $ctx = stream_context_create([
                'http' => ['timeout' => self::IP_API_TIMEOUT],
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if ($json !== false) {
                $data = json_decode($json, true);
                if (is_array($data)) {
                    $result['country'] = (string) ($data['country'] ?? '—');
                    $result['isp'] = (string) ($data['isp'] ?? '—');
                }
            }
        } catch (\Throwable $e) {
        }
        return $result;
    }

    private function getForumTitle(): string
    {
        return (string) ($this->app->getSetting('seo_site_name', '') ?: core_config('app.name', 'MegaforBB'));
    }

    private function getSiteUrl(): string
    {
        return rtrim((string) core_config('app.url', ''), '/') ?: ('https://' . ($_SERVER['HTTP_HOST'] ?? ''));
    }

    /**
     * Giriş yapıldığında kullanıcıya güvenlik bildirimi gönderir.
     */
    public function sendLoginNotification(User $user, string $ip): bool
    {
        if (!$this->isEnabled() || $user->email === '') {
            return false;
        }
        $forumTitle = $this->getForumTitle();
        $siteUrl = $this->getSiteUrl();
        $info = $this->getIpInfo($ip);
        $username = $user->username ?? '';

        $subject = $forumTitle . ' — ' . lang('auth.security_notification.login_subject');
        $body = $this->buildLoginBody($username, $forumTitle, $siteUrl, $ip, $info);
        $mail = new MailService($this->app);
        return $mail->send($user->email, $subject, $body, strip_tags(str_replace(['<br>', '<br/>'], "\n", $body)));
    }

    /**
     * Şifre sıfırlama talebi gönderildiğinde kullanıcıya güvenlik bildirimi gönderir.
     */
    public function sendPasswordResetRequestNotification(User $user, string $ip): bool
    {
        if (!$this->isEnabled() || $user->email === '') {
            return false;
        }
        $forumTitle = $this->getForumTitle();
        $siteUrl = $this->getSiteUrl();
        $info = $this->getIpInfo($ip);
        $username = $user->username ?? '';

        $subject = $forumTitle . ' — ' . lang('auth.security_notification.reset_subject');
        $body = $this->buildPasswordResetBody($username, $forumTitle, $siteUrl, $ip, $info);
        $mail = new MailService($this->app);
        return $mail->send($user->email, $subject, $body, strip_tags(str_replace(['<br>', '<br/>'], "\n", $body)));
    }

    private function buildLoginBody(string $username, string $forumTitle, string $siteUrl, string $ip, array $info): string
    {
        $hello = lang('auth.security_notification.hello', ['name' => $username]);
        $messageTr = lang('auth.security_notification.login_message_tr', ['forum' => $forumTitle]);
        $messageEn = lang('auth.security_notification.login_message_en');
        $location = lang('auth.security_notification.location') . ': ' . $info['country'];
        $isp = 'ISP: ' . $info['isp'];
        $ipLine = 'IP address: ' . $ip;

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif;max-width:560px">'
            . '<p>' . htmlspecialchars($hello, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars($messageTr, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars($messageEn, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><a href="' . htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p style="margin-top:1em;font-size:0.9em;color:#555">'
            . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '<br>'
            . htmlspecialchars($isp, ENT_QUOTES, 'UTF-8') . '<br>'
            . htmlspecialchars($ipLine, ENT_QUOTES, 'UTF-8')
            . '</p></body></html>';
    }

    private function buildPasswordResetBody(string $username, string $forumTitle, string $siteUrl, string $ip, array $info): string
    {
        $hello = lang('auth.security_notification.hello', ['name' => $username]);
        $messageTr = lang('auth.security_notification.reset_message_tr', ['forum' => $forumTitle]);
        $messageEn = lang('auth.security_notification.reset_message_en');
        $location = lang('auth.security_notification.location') . ': ' . $info['country'];
        $isp = 'ISP: ' . $info['isp'];
        $ipLine = 'IP address: ' . $ip;

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="font-family:sans-serif;max-width:560px">'
            . '<p>' . htmlspecialchars($hello, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars($messageTr, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>' . htmlspecialchars($messageEn, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><a href="' . htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p style="margin-top:1em;font-size:0.9em;color:#555">'
            . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '<br>'
            . htmlspecialchars($isp, ENT_QUOTES, 'UTF-8') . '<br>'
            . htmlspecialchars($ipLine, ENT_QUOTES, 'UTF-8')
            . '</p></body></html>';
    }
}
