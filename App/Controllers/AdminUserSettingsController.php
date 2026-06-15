<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserFieldDefinition;

/**
 * Admin: Kullanıcı ayarları — kayıt formu, kayıt kuralları, profil yorumları.
 * Tüm kayıt ve kullanıcıyla ilgili ayarlar tek sayfada.
 */
class AdminUserSettingsController extends AdminController
{
    private const CSRF_TOKEN = 'admin_user_settings';
    private const POSTBIT_ALLOWED_FIELDS = [
        'custom_title',
        'role_badge',
        'post_count',
        'like_count',
        'reputation',
        'reward_points',
        'warning_points',
        'joined_date',
        'location',
        'custom_fields',
    ];
    private const TOP_LAYOUT_BLOCKS = ['profile', 'stats', 'meta'];

    public function index(): string
    {
        $settings = [
            'registration_require_email_verification' => $this->getSetting('registration_require_email_verification', '0') === '1',
            'registration_requires_approval' => $this->getSetting('registration_requires_approval', '0') === '1',
            'login_require_email_code' => $this->getSetting('login_require_email_code', '0') === '1',
            'registration_requires_invite' => $this->getSetting('registration_requires_invite', '0') === '1',
            'registration_show_first_name' => $this->getSetting('registration_show_first_name', '1'),
            'registration_show_last_name' => $this->getSetting('registration_show_last_name', '1'),
            'profile_comments_enabled' => $this->getSetting('profile_comments_enabled', '1') === '1',
            'postbit_simple_accent_color' => (string) $this->getSetting('postbit_simple_accent_color', '#1a252f'),
            'postbit_simple_enabled_fields' => $this->normalizePostbitFields((string) $this->getSetting('postbit_simple_enabled_fields', '[]')),
            'postbit_simple_custom_field_keys' => $this->normalizeCustomFieldKeys((string) $this->getSetting('postbit_simple_custom_field_keys', '[]')),
            'postbit_simple_custom_css' => (string) $this->getSetting('postbit_simple_custom_css', ''),
            'postbit_simple_layout' => $this->normalizeLayout((string) $this->getSetting('postbit_simple_layout', 'left')),
            'postbit_top_stats_position' => $this->normalizeTopStatsPosition((string) $this->getSetting('postbit_top_stats_position', 'right')),
            'postbit_top_left_blocks' => $this->normalizeTopBlocks((string) $this->getSetting('postbit_top_left_blocks', '["profile"]'), ['profile']),
            'postbit_top_right_blocks' => $this->normalizeTopBlocks((string) $this->getSetting('postbit_top_right_blocks', '["stats","meta"]'), ['stats', 'meta']),
            'postbit_top_left_items' => $this->normalizeTopItems((string) $this->getSetting('postbit_top_left_items', '["profile"]')),
            'postbit_top_right_items' => $this->normalizeTopItems((string) $this->getSetting('postbit_top_right_items', '["post_count","like_count","reputation","reward_points","warning_points","joined_date","location"]')),
            'postbit_advanced_enabled' => $this->getSetting('postbit_advanced_enabled', '0') === '1',
            'postbit_advanced_template' => (string) $this->getSetting('postbit_advanced_template', ''),
        ];
        $customFieldDefs = UserFieldDefinition::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['field_key', 'name'])
            ->map(static fn ($r) => ['field_key' => (string) $r->field_key, 'name' => (string) $r->name])
            ->all();
        $topLayoutAvailableItems = [
            ['key' => 'profile', 'label' => 'Profil (avatar + kullanıcı adı)'],
            ['key' => 'custom_title', 'label' => 'Özel unvan'],
            ['key' => 'role_badge', 'label' => 'Rol rozeti'],
            ['key' => 'post_count', 'label' => 'Mesaj sayısı'],
            ['key' => 'like_count', 'label' => 'Beğeni sayısı'],
            ['key' => 'reputation', 'label' => 'İtibar (rep)'],
            ['key' => 'reward_points', 'label' => 'Ödül puanı'],
            ['key' => 'warning_points', 'label' => 'Uyarı puanı'],
            ['key' => 'joined_date', 'label' => 'Katılım tarihi'],
            ['key' => 'location', 'label' => 'Konum'],
        ];
        foreach ($customFieldDefs as $cf) {
            $topLayoutAvailableItems[] = [
                'key' => 'custom:' . $cf['field_key'],
                'label' => 'Özel alan: ' . $cf['name'],
            ];
        }

        $flashSuccess = $this->app->session()->getFlashBag()->get('user_settings_success');
        $flashError = $this->app->session()->getFlashBag()->get('user_settings_error');
        $flashSuccess = is_array($flashSuccess) ? ($flashSuccess[0] ?? '') : (string) $flashSuccess;
        $flashError = is_array($flashError) ? ($flashError[0] ?? '') : (string) $flashError;

        return $this->view('user_settings/index', [
            'pageTitle' => lang('admin.user_settings.page_title'),
            'settings' => $settings,
            'flashSuccess' => $flashSuccess,
            'flashError' => $flashError,
            'postbitAllowedFields' => self::POSTBIT_ALLOWED_FIELDS,
            'postbitCustomFieldDefs' => $customFieldDefs,
            'postbitTopLayoutAvailableItems' => $topLayoutAvailableItems,
        ]);
    }

    public function update(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('user_settings_error', lang('admin.performance.csrf_failed'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/user-settings'));
            return;
        }

        $this->setSetting('registration_require_email_verification', isset($_POST['registration_require_email_verification']) && $_POST['registration_require_email_verification'] === '1' ? '1' : '0', 'auth');
        $this->setSetting('login_require_email_code', isset($_POST['login_require_email_code']) && $_POST['login_require_email_code'] === '1' ? '1' : '0', 'auth');
        $this->setSetting('registration_requires_approval', isset($_POST['registration_requires_approval']) && $_POST['registration_requires_approval'] === '1' ? '1' : '0', 'auth');
        $this->setSetting('registration_requires_invite', isset($_POST['registration_requires_invite']) && $_POST['registration_requires_invite'] === '1' ? '1' : '0', 'auth');

        $showFirst = (string) ($_POST['registration_show_first_name'] ?? '1');
        $showLast = (string) ($_POST['registration_show_last_name'] ?? '1');
        if (in_array($showFirst, ['0', '1', '2'], true)) {
            $this->setSetting('registration_show_first_name', $showFirst, 'auth');
        }
        if (in_array($showLast, ['0', '1', '2'], true)) {
            $this->setSetting('registration_show_last_name', $showLast, 'auth');
        }

        $this->setSetting('profile_comments_enabled', isset($_POST['profile_comments_enabled']) && $_POST['profile_comments_enabled'] === '1' ? '1' : '0', 'system');
        $accentColor = trim((string) ($_POST['postbit_simple_accent_color'] ?? '#1a252f'));
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $accentColor)) {
            $accentColor = '#1a252f';
        }
        $rawFields = $_POST['postbit_simple_enabled_fields'] ?? [];
        $selectedFields = is_array($rawFields) ? $rawFields : [$rawFields];
        $selectedFields = $this->normalizePostbitFields($selectedFields);
        $rawCustomFieldKeys = $_POST['postbit_simple_custom_field_keys'] ?? [];
        $selectedCustomFieldKeys = is_array($rawCustomFieldKeys) ? $rawCustomFieldKeys : [$rawCustomFieldKeys];
        $selectedCustomFieldKeys = $this->normalizeCustomFieldKeys($selectedCustomFieldKeys);
        $layout = $this->normalizeLayout((string) ($_POST['postbit_simple_layout'] ?? 'left'));
        $topStatsPosition = $this->normalizeTopStatsPosition((string) ($_POST['postbit_top_stats_position'] ?? 'right'));
        $topLeftBlocks = $this->normalizeTopBlocks($_POST['postbit_top_left_blocks'] ?? [], ['profile']);
        $topRightBlocks = $this->normalizeTopBlocks($_POST['postbit_top_right_blocks'] ?? [], ['stats', 'meta']);
        $topRightBlocks = array_values(array_filter($topRightBlocks, static fn ($b) => !in_array($b, $topLeftBlocks, true)));
        $topLeftItems = $this->normalizeTopItems((string) ($_POST['postbit_top_left_items'] ?? '[]'));
        $topRightItems = $this->normalizeTopItems((string) ($_POST['postbit_top_right_items'] ?? '[]'));
        $topRightItems = array_values(array_filter($topRightItems, static fn ($k) => !in_array($k, $topLeftItems, true)));
        $this->setSetting('postbit_simple_accent_color', $accentColor, 'forum');
        $this->setSetting('postbit_simple_enabled_fields', json_encode($selectedFields, JSON_UNESCAPED_UNICODE), 'forum');
        $this->setSetting('postbit_simple_custom_field_keys', json_encode($selectedCustomFieldKeys, JSON_UNESCAPED_UNICODE), 'forum');
        $this->setSetting('postbit_simple_custom_css', trim((string) ($_POST['postbit_simple_custom_css'] ?? '')), 'forum');
        $this->setSetting('postbit_simple_layout', $layout, 'forum');
        $this->setSetting('postbit_top_stats_position', $topStatsPosition, 'forum');
        $this->setSetting('postbit_top_left_blocks', json_encode($topLeftBlocks, JSON_UNESCAPED_UNICODE), 'forum');
        $this->setSetting('postbit_top_right_blocks', json_encode($topRightBlocks, JSON_UNESCAPED_UNICODE), 'forum');
        $this->setSetting('postbit_top_left_items', json_encode($topLeftItems, JSON_UNESCAPED_UNICODE), 'forum');
        $this->setSetting('postbit_top_right_items', json_encode($topRightItems, JSON_UNESCAPED_UNICODE), 'forum');
        $this->setSetting('postbit_advanced_enabled', isset($_POST['postbit_advanced_enabled']) && $_POST['postbit_advanced_enabled'] === '1' ? '1' : '0', 'forum');
        $advancedTemplate = trim((string) ($_POST['postbit_advanced_template'] ?? ''));
        $this->setSetting('postbit_advanced_template', $advancedTemplate, 'forum');

        $this->app->session()->getFlashBag()->add('user_settings_success', lang('admin.sys_settings.saved'));
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/user-settings'));
    }

    /**
     * @return array<int, string>
     */
    private function normalizePostbitFields($source): array
    {
        if (is_string($source)) {
            $decoded = json_decode($source, true);
        } elseif (is_array($source)) {
            $decoded = $source;
        } else {
            $decoded = null;
        }
        if (!is_array($decoded)) {
            return self::POSTBIT_ALLOWED_FIELDS;
        }
        $clean = [];
        foreach ($decoded as $item) {
            $item = trim((string) $item);
            if ($item !== '' && in_array($item, self::POSTBIT_ALLOWED_FIELDS, true)) {
                $clean[] = $item;
            }
        }
        $clean = array_values(array_unique($clean));
        return $clean === [] ? self::POSTBIT_ALLOWED_FIELDS : $clean;
    }

    /**
     * @param mixed $source
     * @return array<int, string>
     */
    private function normalizeCustomFieldKeys($source): array
    {
        if (is_string($source)) {
            $decoded = json_decode($source, true);
        } elseif (is_array($source)) {
            $decoded = $source;
        } else {
            $decoded = null;
        }
        if (!is_array($decoded)) {
            return [];
        }
        $allowed = UserFieldDefinition::query()->pluck('field_key')->map(static fn ($k) => (string) $k)->all();
        if ($allowed === []) {
            return [];
        }
        $clean = [];
        foreach ($decoded as $item) {
            $item = trim((string) $item);
            if ($item !== '' && in_array($item, $allowed, true)) {
                $clean[] = $item;
            }
        }
        return array_values(array_unique($clean));
    }

    private function normalizeLayout(string $layout): string
    {
        return in_array($layout, ['left', 'top'], true) ? $layout : 'left';
    }

    private function normalizeTopStatsPosition(string $position): string
    {
        return in_array($position, ['left', 'right'], true) ? $position : 'right';
    }

    /**
     * @param mixed $source
     * @param array<int,string> $default
     * @return array<int,string>
     */
    private function normalizeTopBlocks($source, array $default): array
    {
        if (is_string($source)) {
            $decoded = json_decode($source, true);
        } elseif (is_array($source)) {
            $decoded = $source;
        } else {
            $decoded = null;
        }
        if (!is_array($decoded)) {
            return $default;
        }
        $clean = [];
        foreach ($decoded as $item) {
            $item = trim((string) $item);
            if ($item !== '' && in_array($item, self::TOP_LAYOUT_BLOCKS, true)) {
                $clean[] = $item;
            }
        }
        $clean = array_values(array_unique($clean));
        return $clean === [] ? $default : $clean;
    }

    /**
     * @return array<int,string>
     */
    private function normalizeTopItems(string $encoded): array
    {
        $decoded = json_decode($encoded, true);
        if (!is_array($decoded)) {
            return [];
        }
        $allowed = [
            'profile', 'custom_title', 'role_badge', 'post_count', 'like_count',
            'reputation', 'reward_points', 'warning_points', 'joined_date', 'location',
        ];
        $customKeys = UserFieldDefinition::query()->pluck('field_key')->map(static fn ($k) => 'custom:' . (string) $k)->all();
        $allowed = array_values(array_unique(array_merge($allowed, $customKeys)));
        $clean = [];
        foreach ($decoded as $item) {
            $item = trim((string) $item);
            if ($item !== '' && in_array($item, $allowed, true)) {
                $clean[] = $item;
            }
        }
        return array_values(array_unique($clean));
    }
}