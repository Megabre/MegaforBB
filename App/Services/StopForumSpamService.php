<?php

declare(strict_types=1);

namespace App\Services;

use Forecor\Core\Application;

/**
 * Stop Forum Spam (https://www.stopforumspam.com/) — kayıt sırasında IP / e-posta / kullanıcı adı sorgusu.
 * API: GET https://api.stopforumspam.org/api?json=1&...
 */
class StopForumSpamService
{
    private const API_BASE = 'https://api.stopforumspam.org/api';

    public function __construct(
        protected Application $app
    ) {
    }

    /**
     * Kayıt için SFS eşleşmesi varsa true: hesap yönetici onayına düşmeli (approved_at boş).
     */
    public function registrationShouldRequireApproval(string $username, string $email, string $clientIp): bool
    {
        if ($this->app->getSetting('sfs_enabled', '0') !== '1') {
            return false;
        }

        $checkIp = $this->app->getSetting('sfs_check_ip', '1') === '1';
        $checkEmail = $this->app->getSetting('sfs_check_email', '1') === '1';
        $checkUsername = $this->app->getSetting('sfs_check_username', '1') === '1';
        if (!$checkIp && !$checkEmail && !$checkUsername) {
            return false;
        }

        $minFreq = max(1, min(255, (int) $this->app->getSetting('sfs_min_frequency', '1')));
        $minConf = max(0.0, min(100.0, (float) $this->app->getSetting('sfs_min_confidence', '0')));
        $expireDays = max(0, min(3650, (int) $this->app->getSetting('sfs_expire_days', '0')));

        $query = ['json' => '1'];
        if ($expireDays > 0) {
            $query['expire'] = (string) $expireDays;
        }
        $fields = 0;
        if ($checkIp && $clientIp !== '') {
            $query['ip'] = $clientIp;
            $fields++;
        }
        if ($checkEmail && $email !== '') {
            $query['email'] = $email;
            $fields++;
        }
        if ($checkUsername && $username !== '') {
            $query['username'] = $username;
            $fields++;
        }
        if ($fields === 0) {
            return false;
        }

        $url = self::API_BASE . '?' . $this->buildQuery($query);
        $body = $this->httpGet($url);
        if ($body === null || $body === '') {
            return false;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['success'])) {
            return false;
        }

        if ($checkIp && $clientIp !== '' && isset($data['ip']) && is_array($data['ip'])) {
            if ($this->fieldMatches($data['ip'], $minFreq, $minConf)) {
                return true;
            }
        }
        if ($checkEmail && isset($data['email']) && is_array($data['email'])) {
            if ($this->fieldMatches($data['email'], $minFreq, $minConf)) {
                return true;
            }
        }
        if ($checkUsername && isset($data['username']) && is_array($data['username'])) {
            if ($this->fieldMatches($data['username'], $minFreq, $minConf)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Yönetim paneli testi: ham JSON yanıtı ve eşleşme özeti.
     *
     * @return array{success: bool, raw?: array, needs_approval?: bool, error?: string}
     */
    public function lookupForTest(string $username, string $email, string $clientIp): array
    {
        $minFreq = max(1, min(255, (int) $this->app->getSetting('sfs_min_frequency', '1')));
        $minConf = max(0.0, min(100.0, (float) $this->app->getSetting('sfs_min_confidence', '0')));
        $expireDays = max(0, min(3650, (int) $this->app->getSetting('sfs_expire_days', '0')));

        $query = ['json' => '1'];
        if ($expireDays > 0) {
            $query['expire'] = (string) $expireDays;
        }
        $fields = 0;
        if ($clientIp !== '') {
            $query['ip'] = $clientIp;
            $fields++;
        }
        if ($email !== '') {
            $query['email'] = $email;
            $fields++;
        }
        if ($username !== '') {
            $query['username'] = $username;
            $fields++;
        }

        if ($fields === 0) {
            return ['success' => false, 'error' => 'empty_query'];
        }

        $url = self::API_BASE . '?' . $this->buildQuery($query);
        $body = $this->httpGet($url);
        if ($body === null || $body === '') {
            return ['success' => false, 'error' => 'http'];
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return ['success' => false, 'error' => 'json'];
        }

        $needs = false;
        foreach (['ip', 'email', 'username'] as $key) {
            if (isset($data[$key]) && is_array($data[$key]) && $this->fieldMatches($data[$key], $minFreq, $minConf)) {
                $needs = true;
                break;
            }
        }

        return ['success' => true, 'raw' => $data, 'needs_approval' => $needs];
    }

    private function fieldMatches(array $block, int $minFrequency, float $minConfidence): bool
    {
        $appears = isset($block['appears']) ? (int) $block['appears'] : 0;
        if ($appears !== 1) {
            return false;
        }
        $freq = isset($block['frequency']) ? (int) $block['frequency'] : 0;
        if ($freq < $minFrequency) {
            return false;
        }
        $conf = isset($block['confidence']) ? (float) $block['confidence'] : 0.0;
        if ($conf < $minConfidence) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, string> $query
     */
    private function buildQuery(array $query): string
    {
        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function httpGet(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'MegaforBB/StopForumSpam',
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code !== 200) {
                return null;
            }

            return (string) $body;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'MegaforBB/StopForumSpam',
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            return null;
        }

        return (string) $body;
    }
}
