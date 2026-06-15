<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Admin: Hero / giriş kartı ve portal vitrin düzenleme.
 */
class AdminHeroController extends AdminController
{
    private const CSRF_TOKEN = 'admin_hero';

    /** @var list<string> */
    private const PORTAL_TEXT_KEYS = [
        'portal_hero_badge',
        'portal_hero_title',
        'portal_hero_description',
        'portal_hero_btn_primary_label',
        'portal_hero_btn_primary_url',
        'portal_hero_btn_secondary_label',
        'portal_hero_btn_secondary_url',
        'portal_features_intro',
        'portal_cta_title',
        'portal_cta_text',
        'portal_cta_btn_label',
        'portal_cta_btn_url',
        'portal_card_view_all_label',
        'portal_block_empty_text',
    ];

    public function index(): string
    {
        $s = [
            'hero_title' => $this->getSetting('hero_title', '') ?: core__('stats.forums'),
            'hero_description' => $this->getSetting('hero_description', '') ?: core__('stats.forums_desc'),
            'hero_visible' => $this->getSetting('hero_visible', '1') !== '0',

            'hero_f1_icon' => $this->getSetting('hero_f1_icon', 'fa-solid fa-gem'),
            'hero_f1_title' => $this->getSetting('hero_f1_title', 'Pırlanta Kalite'),
            'hero_f1_desc' => $this->getSetting('hero_f1_desc', 'Modern mimari, güvenli altyapı ve sınırsız özelleştirme ile forum yazılımının zirvesi.'),

            'hero_f2_icon' => $this->getSetting('hero_f2_icon', 'fa-solid fa-bolt'),
            'hero_f2_title' => $this->getSetting('hero_f2_title', 'Hızlı & Akıcı'),
            'hero_f2_desc' => $this->getSetting('hero_f2_desc', 'Laravel ve Symfony gücüyle optimize edilmiş, her ölçekte kusursuz performans.'),

            'hero_f3_icon' => $this->getSetting('hero_f3_icon', 'fa-solid fa-palette'),
            'hero_f3_title' => $this->getSetting('hero_f3_title', 'Özelleştirilebilir'),
            'hero_f3_desc' => $this->getSetting('hero_f3_desc', 'Tema, eklenti ve modül desteği ile hayalinizdeki topluluğu kurun.'),

            'hero_f4_icon' => $this->getSetting('hero_f4_icon', 'fa-solid fa-shield-halved'),
            'hero_f4_title' => $this->getSetting('hero_f4_title', 'Güvenli & Kararlı'),
            'hero_f4_desc' => $this->getSetting('hero_f4_desc', 'Güncel güvenlik standartları ve düzenli güncellemelerle güvende kalın.'),
        ];

        foreach (self::PORTAL_TEXT_KEYS as $key) {
            $s[$key] = (string) $this->getSetting($key, '');
        }

        return $this->view('hero/index', [
            'pageTitle' => lang('admin.hero.title'),
            'settings' => $s,
            'updated' => isset($_GET['updated']) && (string) $_GET['updated'] === '1',
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/hero'));
            return;
        }
        $this->setSetting('hero_title', trim((string) ($_POST['hero_title'] ?? '')), 'forum');
        $this->setSetting('hero_description', trim((string) ($_POST['hero_description'] ?? '')), 'forum');
        $this->setSetting('hero_visible', isset($_POST['hero_visible']) && $_POST['hero_visible'] === '1' ? '1' : '0', 'forum');

        foreach (self::PORTAL_TEXT_KEYS as $key) {
            $this->setSetting($key, trim((string) ($_POST[$key] ?? '')), 'forum');
        }

        for ($i = 1; $i <= 4; $i++) {
            $this->setSetting("hero_f{$i}_icon", trim((string) ($_POST["hero_f{$i}_icon"] ?? '')), 'forum');
            $this->setSetting("hero_f{$i}_title", trim((string) ($_POST["hero_f{$i}_title"] ?? '')), 'forum');
            $this->setSetting("hero_f{$i}_desc", trim((string) ($_POST["hero_f{$i}_desc"] ?? '')), 'forum');
        }

        $this->app->cache()->clear();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/hero?updated=1'));
    }
}
