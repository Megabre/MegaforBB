<?php

declare(strict_types=1);

namespace App\Services;

use Forecor\Core\Application;

/**
 * Captcha doğrulama: Google reCAPTCHA (v2/v3) ve Cloudflare Turnstile.
 * Ayarlar: captcha_provider (none|recaptcha|turnstile), captcha_on_login, captcha_on_register,
 * recaptcha_site_key, recaptcha_secret_key, recaptcha_version (v2|v3), recaptcha_score_threshold,
 * turnstile_site_key, turnstile_secret_key.
 */
class CaptchaService
{
    private const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    private function getSetting(string $key, string $default = ''): string
    {
        return (string) $this->app->getSetting($key, $default);
    }

    public function getProvider(): string
    {
        $p = $this->getSetting('captcha_provider', 'none');
        return $p === 'recaptcha' || $p === 'turnstile' ? $p : 'none';
    }

    /** Giriş formunda captcha gösterilsin mi? */
    public function enabledOnLogin(): bool
    {
        return $this->getSetting('captcha_on_login', '0') === '1' && $this->getProvider() !== 'none';
    }

    /** Kayıt formunda captcha gösterilsin mi? */
    public function enabledOnRegister(): bool
    {
        return $this->getSetting('captcha_on_register', '1') === '1' && $this->getProvider() !== 'none';
    }

    /** İletişim formunda captcha gösterilsin mi? (varsayılan kapalı) */
    public function enabledOnContact(): bool
    {
        return $this->getSetting('captcha_on_contact', '0') === '1' && $this->getProvider() !== 'none';
    }

    /** Formda gönderilen token alan adı (g-recaptcha-response veya cf-turnstile-response). */
    public function getResponseFieldName(): string
    {
        return $this->getProvider() === 'turnstile' ? 'cf-turnstile-response' : 'g-recaptcha-response';
    }

    /**
     * Token'ı doğrula. Başarılı ise true, değilse false.
     */
    public function verify(string $token, string $remoteIp = ''): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }
        $provider = $this->getProvider();
        if ($provider === 'recaptcha') {
            return $this->verifyRecaptcha($token, $remoteIp);
        }
        if ($provider === 'turnstile') {
            return $this->verifyTurnstile($token, $remoteIp);
        }
        return true;
    }

    private function verifyRecaptcha(string $token, string $remoteIp): bool
    {
        $secret = $this->getSetting('recaptcha_secret_key', '');
        if ($secret === '') {
            return false;
        }
        $post = [
            'secret'   => $secret,
            'response' => $token,
        ];
        if ($remoteIp !== '') {
            $post['remoteip'] = $remoteIp;
        }
        $json = $this->httpPost(self::RECAPTCHA_VERIFY_URL, $post);
        if ($json === null) {
            return false;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['success'])) {
            return false;
        }
        $version = $this->getSetting('recaptcha_version', 'v2');
        if ($version === 'v3') {
            $score = (float) ($data['score'] ?? 0);
            $threshold = (float) $this->getSetting('recaptcha_score_threshold', '0.5');
            return $score >= $threshold;
        }
        return true;
    }

    private function verifyTurnstile(string $token, string $remoteIp): bool
    {
        $secret = $this->getSetting('turnstile_secret_key', '');
        if ($secret === '') {
            return false;
        }
        $post = [
            'secret'   => $secret,
            'response' => $token,
        ];
        if ($remoteIp !== '') {
            $post['remoteip'] = $remoteIp;
        }
        $json = $this->httpPost(self::TURNSTILE_VERIFY_URL, $post);
        if ($json === null) {
            return false;
        }
        $data = json_decode($json, true);
        return is_array($data) && !empty($data['success']);
    }

    private function httpPost(string $url, array $data): ?string
    {
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 10,
            ],
        ];
        $ctx = stream_context_create($opts);
        $result = @file_get_contents($url, false, $ctx);
        return is_string($result) ? $result : null;
    }

    /** Widget için gerekli ayarlar (view'a geçilecek). */
    public function getWidgetConfig(): array
    {
        $provider = $this->getProvider();
        $config = [
            'provider'       => $provider,
            'site_key'       => '',
            'recaptcha_version' => 'v2',
        ];
        if ($provider === 'recaptcha') {
            $config['site_key'] = $this->getSetting('recaptcha_site_key', '');
            $config['recaptcha_version'] = $this->getSetting('recaptcha_version', 'v2');
        } elseif ($provider === 'turnstile') {
            $config['site_key'] = $this->getSetting('turnstile_site_key', '');
        }
        return $config;
    }
}
