<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BlockedEmailDomain;
use App\Models\BlockedUsername;
use App\Models\BlockedWord;
use App\Models\Setting;

/**
 * Admin: Sansür koruma — yasak kelimeler, yasak kullanıcı adları, temp mail domain listesi ve ayarlar.
 */
class AdminCensorshipController extends AdminController
{
    private const CSRF_TOKEN = 'admin_censorship';
    private const GROUP = 'censorship';
    private const SECTIONS = ['words', 'usernames', 'domains', 'settings'];

    /** Eski URL: /admin/censorship → /admin/censorship/words yönlendir */
    public function index(): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        header('Location: ' . core_url($adminPath . '/censorship/words'), true, 302);
        exit;
    }

    /** Path tabanlı bölüm: /admin/censorship/{section} — tab yok */
    public function showSection(string $section): string
    {
        $section = strtolower(trim($section));
        if (!in_array($section, self::SECTIONS, true)) {
            $adminPath = env('ADMIN_PATH', 'admin');
            header('Location: ' . core_url($adminPath . '/censorship/words'), true, 302);
            exit;
        }
        return $this->renderSection($section);
    }

    private function renderSection(string $activeTab): string
    {
        $words = BlockedWord::orderBy('id')->get();
        $usernames = BlockedUsername::orderBy('id')->get();
        $domains = BlockedEmailDomain::orderBy('domain')->get();
        $raw = fn (string $k, $d = '') => Setting::getValue($k, $d);
        $settings = [
            'censorship_enabled' => $raw('censorship_enabled', '0') === '1',
            'censorship_word_action' => $raw('censorship_word_action', 'block'),
            'censorship_apply_posts' => $raw('censorship_apply_posts', '1') === '1',
            'censorship_apply_topic_titles' => $raw('censorship_apply_topic_titles', '1') === '1',
            'censorship_apply_signatures' => $raw('censorship_apply_signatures', '1') === '1',
            'temp_mail_block_enabled' => $raw('temp_mail_block_enabled', '1') === '1',
            'blocked_usernames_enabled' => $raw('blocked_usernames_enabled', '1') === '1',
        ];
        return $this->view('censorship/index', [
            'pageTitle' => lang('admin.censorship.page_title'),
            'words' => $words,
            'usernames' => $usernames,
            'domains' => $domains,
            'settings' => $settings,
            'activeTab' => $activeTab,
            'csrfToken' => core_csrf_token(self::CSRF_TOKEN),
            'adminPath' => env('ADMIN_PATH', 'admin'),
        ]);
    }

    public function updateSettings(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/settings'));
            return;
        }
        $this->setSetting('censorship_enabled', isset($_POST['censorship_enabled']) && $_POST['censorship_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('censorship_word_action', ($_POST['censorship_word_action'] ?? '') === 'replace' ? 'replace' : 'block', self::GROUP);
        $this->setSetting('censorship_apply_posts', isset($_POST['censorship_apply_posts']) && $_POST['censorship_apply_posts'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('censorship_apply_topic_titles', isset($_POST['censorship_apply_topic_titles']) && $_POST['censorship_apply_topic_titles'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('censorship_apply_signatures', isset($_POST['censorship_apply_signatures']) && $_POST['censorship_apply_signatures'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('temp_mail_block_enabled', isset($_POST['temp_mail_block_enabled']) && $_POST['temp_mail_block_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->setSetting('blocked_usernames_enabled', isset($_POST['blocked_usernames_enabled']) && $_POST['blocked_usernames_enabled'] === '1' ? '1' : '0', self::GROUP);
        $this->app->censorship()->clearCache();
        $this->app->session()->getFlashBag()->add('censorship_ok', lang('admin.censorship.settings_saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/settings'));
    }

    public function storeWord(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/words'));
            return;
        }
        $word = trim((string) ($_POST['word'] ?? ''));
        if ($word === '') {
            $this->app->session()->getFlashBag()->add('censorship_error', lang('admin.censorship.word_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/words'));
            return;
        }
        $replacement = trim((string) ($_POST['replacement'] ?? ''));
        $isRegex = isset($_POST['is_regex']) && $_POST['is_regex'] === '1';
        BlockedWord::create(['word' => $word, 'replacement' => $replacement ?: null, 'is_regex' => $isRegex]);
        $this->app->censorship()->clearCache();
        $this->app->session()->getFlashBag()->add('censorship_ok', lang('admin.censorship.word_added'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/words'));
    }

    public function deleteWord(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/words'));
            return;
        }
        BlockedWord::where('id', (int) $id)->delete();
        $this->app->censorship()->clearCache();
        $this->app->session()->getFlashBag()->add('censorship_ok', lang('admin.censorship.word_deleted'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/words'));
    }

    public function storeUsername(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/usernames'));
            return;
        }
        $pattern = trim((string) ($_POST['pattern'] ?? ''));
        if ($pattern === '') {
            $this->app->session()->getFlashBag()->add('censorship_error', lang('admin.censorship.pattern_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/usernames'));
            return;
        }
        $isRegex = isset($_POST['is_regex']) && $_POST['is_regex'] === '1';
        BlockedUsername::create(['pattern' => $pattern, 'is_regex' => $isRegex]);
        $this->app->censorship()->clearCache();
        $this->app->session()->getFlashBag()->add('censorship_ok', lang('admin.censorship.username_added'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/usernames'));
    }

    public function deleteUsername(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/usernames'));
            return;
        }
        BlockedUsername::where('id', (int) $id)->delete();
        $this->app->censorship()->clearCache();
        $this->app->session()->getFlashBag()->add('censorship_ok', lang('admin.censorship.username_deleted'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/usernames'));
    }

    public function storeDomain(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/domains'));
            return;
        }
        $domain = trim((string) ($_POST['domain'] ?? ''));
        $domain = strtolower($domain);
        if ($domain === '' || strpos($domain, '@') !== false) {
            $this->app->session()->getFlashBag()->add('censorship_error', lang('admin.censorship.domain_invalid'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/domains'));
            return;
        }
        if (BlockedEmailDomain::where('domain', $domain)->exists()) {
            $this->app->session()->getFlashBag()->add('censorship_error', lang('admin.censorship.domain_exists'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/domains'));
            return;
        }
        BlockedEmailDomain::create(['domain' => $domain]);
        $this->app->censorship()->clearCache();
        $this->app->session()->getFlashBag()->add('censorship_ok', lang('admin.censorship.domain_added'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/domains'));
    }

    public function deleteDomain(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/domains'));
            return;
        }
        BlockedEmailDomain::where('id', (int) $id)->delete();
        $this->app->censorship()->clearCache();
        $this->app->session()->getFlashBag()->add('censorship_ok', lang('admin.censorship.domain_deleted'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/censorship/domains'));
    }
}
