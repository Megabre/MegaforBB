<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockedEmailDomain;
use App\Models\BlockedUsername;
use App\Models\BlockedWord;
use App\Models\Setting;

/**
 * Sansür koruma: engellenecek kelimeler, engellenecek kullanıcı adları, geçici e-posta (temp mail) koruması.
 */
class CensorshipService
{
    private const CACHE_KEY_WORDS = 'censorship_blocked_words';
    private const CACHE_KEY_USERNAMES = 'censorship_blocked_usernames';
    private const CACHE_KEY_DOMAINS = 'censorship_blocked_email_domains';
    private const CACHE_TTL = 300;

    public function __construct(
        private \Forecor\Core\Application $app
    ) {
    }

    public function isCensorshipEnabled(): bool
    {
        return (string) Setting::getCached('censorship_enabled', '0', self::CACHE_TTL) === '1';
    }

    public function isTempMailBlockEnabled(): bool
    {
        return (string) Setting::getCached('temp_mail_block_enabled', '1', self::CACHE_TTL) === '1';
    }

    public function isBlockedUsernamesEnabled(): bool
    {
        return (string) Setting::getCached('blocked_usernames_enabled', '1', self::CACHE_TTL) === '1';
    }

    /** Kelime filtresi mesaj/başlık için uygulanacak mı? */
    public function applyToPosts(): bool
    {
        return (string) Setting::getCached('censorship_apply_posts', '1', self::CACHE_TTL) === '1';
    }

    public function applyToTopicTitles(): bool
    {
        return (string) Setting::getCached('censorship_apply_topic_titles', '1', self::CACHE_TTL) === '1';
    }

    public function applyToSignatures(): bool
    {
        return (string) Setting::getCached('censorship_apply_signatures', '1', self::CACHE_TTL) === '1';
    }

    /** block = içerik reddedilir, replace = eşleşen kelimeler replacement ile değiştirilir */
    public function wordAction(): string
    {
        $v = (string) Setting::getCached('censorship_word_action', 'block', self::CACHE_TTL);
        return $v === 'replace' ? 'replace' : 'block';
    }

    /** @return list<object{word: string, replacement: ?string, is_regex: bool}> */
    private function getBlockedWords(): array
    {
        try {
            return \Illuminate\Support\Facades\Cache::remember(self::CACHE_KEY_WORDS, self::CACHE_TTL, function () {
                return BlockedWord::orderBy('id')->get(['word', 'replacement', 'is_regex'])->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return list<object{pattern: string, is_regex: bool}> */
    private function getBlockedUsernames(): array
    {
        try {
            return \Illuminate\Support\Facades\Cache::remember(self::CACHE_KEY_USERNAMES, self::CACHE_TTL, function () {
                return BlockedUsername::orderBy('id')->get(['pattern', 'is_regex'])->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return list<string> lowercase domain list */
    private function getBlockedEmailDomains(): array
    {
        try {
            return \Illuminate\Support\Facades\Cache::remember(self::CACHE_KEY_DOMAINS, self::CACHE_TTL, function () {
                return BlockedEmailDomain::orderBy('id')->pluck('domain')->map(fn ($d) => strtolower(trim((string) $d)))->filter()->values()->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function clearCache(): void
    {
        try {
            \Illuminate\Support\Facades\Cache::forget(self::CACHE_KEY_WORDS);
            \Illuminate\Support\Facades\Cache::forget(self::CACHE_KEY_USERNAMES);
            \Illuminate\Support\Facades\Cache::forget(self::CACHE_KEY_DOMAINS);
        } catch (\Throwable $e) {
        }
    }

    /**
     * Metin içinde yasaklı kelime var mı kontrol et; replace modunda değiştirilmiş metni döndür.
     * @return array{allowed: bool, filtered_text: string, matched: ?string}
     */
    public function checkContent(string $text): array
    {
        $filtered = $text;
        $matched = null;
        $words = $this->getBlockedWords();
        $action = $this->wordAction();

        foreach ($words as $row) {
            $word = $row->word ?? '';
            $replacement = isset($row->replacement) ? (string) $row->replacement : '***';
            $isRegex = !empty($row->is_regex);

            if ($word === '') {
                continue;
            }

            if ($isRegex) {
                $pattern = $word;
                if (@preg_match($pattern, '') === false) {
                    continue;
                }
                if ($action === 'block') {
                    if (preg_match($pattern, $text)) {
                        return ['allowed' => false, 'filtered_text' => $text, 'matched' => $word];
                    }
                    continue;
                }
                $filtered = preg_replace($pattern, $replacement, $filtered);
                if (preg_match($pattern, $text)) {
                    $matched = $word;
                }
                continue;
            }

            $quoted = preg_quote($word, '/');
            $pattern = '/\b' . $quoted . '\b/iu';
            if ($action === 'block') {
                if (preg_match($pattern, $text)) {
                    return ['allowed' => false, 'filtered_text' => $text, 'matched' => $word];
                }
                continue;
            }
            $filtered = preg_replace($pattern, $replacement, $filtered);
            if (preg_match($pattern, $text)) {
                $matched = $word;
            }
        }

        return ['allowed' => true, 'filtered_text' => $filtered, 'matched' => $matched];
    }

    /**
     * Kullanıcı adı engelli listede mi?
     * @return array{allowed: bool, message: ?string}
     */
    public function checkUsername(string $username): array
    {
        if (!$this->isBlockedUsernamesEnabled()) {
            return ['allowed' => true, 'message' => null];
        }
        $list = $this->getBlockedUsernames();
        $normalized = trim($username);
        if ($normalized === '') {
            return ['allowed' => false, 'message' => lang('censorship.username_required')];
        }
        foreach ($list as $row) {
            $pattern = $row->pattern ?? '';
            if ($pattern === '') {
                continue;
            }
            if (!empty($row->is_regex)) {
                if (@preg_match($pattern, $normalized)) {
                    return ['allowed' => false, 'message' => lang('censorship.username_blocked')];
                }
                continue;
            }
            if (strcasecmp($pattern, $normalized) === 0) {
                return ['allowed' => false, 'message' => lang('censorship.username_blocked')];
            }
        }
        return ['allowed' => true, 'message' => null];
    }

    /**
     * E-posta geçici mail domain listesinde mi?
     * @return array{allowed: bool, message: ?string}
     */
    public function checkEmail(string $email): array
    {
        if (!$this->isTempMailBlockEnabled()) {
            return ['allowed' => true, 'message' => null];
        }
        $email = trim($email);
        if ($email === '' || strpos($email, '@') === false) {
            return ['allowed' => true, 'message' => null];
        }
        $parts = explode('@', $email);
        $domain = strtolower(trim((string) end($parts)));
        if ($domain === '') {
            return ['allowed' => true, 'message' => null];
        }
        $blocked = $this->getBlockedEmailDomains();
        if (in_array($domain, $blocked, true)) {
            return ['allowed' => false, 'message' => lang('censorship.temp_mail_blocked')];
        }
        return ['allowed' => true, 'message' => null];
    }
}
