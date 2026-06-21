<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Language;
use App\Models\LanguageLine;
use Forecor\Core\Application;
use Illuminate\Database\Capsule\Manager as DB;

class AdminLanguageController extends AdminController
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /** Dil listesi — DB + lang/ klasöründeki dosyaları birleştirerek gösterir */
    public function index(): string
    {
        // 1) DB'den oku (tablo yoksa boş array)
        $dbLanguages = [];
        $dbHasTable = false;
        try {
            $dbLanguages = Language::orderBy('name')->get()->keyBy('code')->toArray();
            $dbHasTable = true;
        } catch (\Throwable $e) {
        }

        // 2) lang/ klasöründeki .php dosyalarını tara
        $langDir = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang';
        $fileLocales = [];
        if (is_dir($langDir)) {
            foreach (scandir($langDir) as $file) {
                if ($file === '.' || $file === '..' || !str_ends_with($file, '.php') || is_dir($langDir . DIRECTORY_SEPARATOR . $file)) {
                    continue;
                }
                $code = pathinfo($file, PATHINFO_FILENAME);
                if (preg_match('/^[a-z]{2,5}$/', $code)) {
                    $fileLocales[$code] = true;
                }
            }
        }

        // Known language names (fallback when not in DB)
        $knownNames = [
            'tr' => ['name' => 'Turkish', 'native' => lang('admin.languages.native_tr')],
            'en' => ['name' => 'English', 'native' => 'English'],
            'de' => ['name' => 'German', 'native' => 'Deutsch'],
            'fr' => ['name' => 'French', 'native' => 'Français'],
            'es' => ['name' => 'Spanish', 'native' => 'Español'],
            'ar' => ['name' => 'Arabic', 'native' => 'العربية'],
            'ru' => ['name' => 'Russian', 'native' => 'Русский'],
            'ja' => ['name' => 'Japanese', 'native' => '日本語'],
            'zh' => ['name' => 'Chinese', 'native' => '中文'],
            'ko' => ['name' => 'Korean', 'native' => '한국어'],
            'pt' => ['name' => 'Portuguese', 'native' => 'Português'],
            'it' => ['name' => 'Italian', 'native' => 'Italiano'],
            'nl' => ['name' => 'Dutch', 'native' => 'Nederlands'],
            'pl' => ['name' => 'Polish', 'native' => 'Polski'],
            'sv' => ['name' => 'Swedish', 'native' => 'Svenska'],
        ];

        // 3) Merge: use DB record if present, else derive from file
        $languages = [];
        $allCodes = array_unique(array_merge(array_keys($dbLanguages), array_keys($fileLocales)));
        sort($allCodes);

        $defaultLocale = (string) $this->app->getSetting('default_locale', core_config('app.locale', 'tr'));
        if ($defaultLocale === '' || !preg_match('/^[a-z]{2,5}$/', $defaultLocale)) {
            $defaultLocale = 'tr';
        }

        foreach ($allCodes as $code) {
            if (isset($dbLanguages[$code])) {
                $row = $dbLanguages[$code];
                $row['is_default'] = ($code === $defaultLocale);
                $languages[] = $row;
            } else {
                // Dosyadan keşfedildi, DB'de yok
                $known = $knownNames[$code] ?? null;
                $languages[] = [
                    'id'          => 0,
                    'code'        => $code,
                    'name'        => $known['name'] ?? strtoupper($code),
                    'native_name' => $known['native'] ?? strtoupper($code),
                    'is_active'   => true,
                    'is_default'  => ($code === $defaultLocale),
                    'direction'   => ($code === 'ar' || $code === 'he' || $code === 'fa') ? 'rtl' : 'ltr',
                    'source'      => 'file',
                ];
            }
        }

        // Count keys per language
        $translator = $this->app->translator();
        foreach ($languages as &$lang) {
            $all = $translator->all($lang['code']);
            $lang['key_count'] = count($all);
        }
        unset($lang);

        return $this->view('languages/index', [
            'pageTitle'  => lang('admin.languages.title'),
            'languages'  => $languages,
            'dbReady'    => $dbHasTable,
        ]);
    }

    /** New language form */
    public function create(): string
    {
        $existingLanguages = [];
        try {
            $existingLanguages = Language::orderBy('name')->get(['code', 'name'])->toArray();
        } catch (\Throwable $e) {
        }

        return $this->view('languages/create', [
            'pageTitle'          => lang('admin.languages.add_new'),
            'existingLanguages'  => $existingLanguages,
        ]);
    }

    /** Store new language */
    public function store(): string
    {
        if (!core_csrf_valid('csrf', $_POST['_token'] ?? '')) {
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        $code       = trim($_POST['code'] ?? '');
        $name       = trim($_POST['name'] ?? '');
        $nativeName = trim($_POST['native_name'] ?? '');
        $direction  = ($_POST['direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';
        $copyFrom   = trim($_POST['copy_from'] ?? '');

        if ($code === '' || $name === '') {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.common.error'));
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages/create'));
        }

        try {
            DB::beginTransaction();

            $lang = Language::create([
                'code'        => $code,
                'name'        => $name,
                'native_name' => $nativeName ?: $name,
                'direction'   => $direction,
                'is_active'   => true,
                'is_default'  => false,
            ]);

            // Copy translations from an existing language
            if ($copyFrom !== '' && $copyFrom !== $code) {
                $sourceLines = LanguageLine::where('locale', $copyFrom)->get();
                foreach ($sourceLines as $line) {
                    LanguageLine::create([
                        'locale' => $code,
                        'group'  => $line->group,
                        'key'    => $line->key,
                        'value'  => $line->value,
                    ]);
                }
            }

            // Import keys from language file into DB if present
            $filePath = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $code . '.php';
            if (is_file($filePath)) {
                $fileLines = require $filePath;
                if (is_array($fileLines)) {
                    foreach ($fileLines as $key => $value) {
                        $parts = explode('.', $key, 2);
                        $group = $parts[0] ?? 'general';
                        $lineKey = $parts[1] ?? $key;

                        LanguageLine::updateOrCreate(
                            ['locale' => $code, 'group' => $group, 'key' => $lineKey],
                            ['value' => $value]
                        );
                    }
                }
            }

            DB::commit();

            \Forecor\Core\SessionManager::get()->getFlashBag()->set('success', lang('admin.languages.created'));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.common.error'));
        }

        return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
    }

    /** Translation edit page */
    public function edit(string $code): string
    {
        $language = null;
        try {
            $language = Language::where('code', $code)->first();
        } catch (\Throwable $e) {
        }

        // If not in DB but file exists, create virtual Language object
        if (!$language) {
            $filePath = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $code . '.php';
            if (!is_file($filePath)) {
                return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
            }
            $language = (object) [
                'code'        => $code,
                'name'        => strtoupper($code),
                'native_name' => strtoupper($code),
                'direction'   => 'ltr',
                'is_active'   => true,
                'is_default'  => ($code === core_config('app.locale', 'tr')),
            ];
        }

        $defaultLocale = (string) $this->app->getSetting('default_locale', core_config('app.locale', 'tr'));
        if ($defaultLocale === '' || !preg_match('/^[a-z]{2,5}$/', $defaultLocale)) {
            $defaultLocale = core_config('app.locale', 'tr');
        }

        $translator = $this->app->translator();
        $defaultTranslations = $translator->all($defaultLocale);
        $currentTranslations = $translator->all($code);

        // Tüm bilinen anahtarlar
        $allKeys = array_unique(array_merge(array_keys($defaultTranslations), array_keys($currentTranslations)));
        sort($allKeys);

        $translationRows = [];
        foreach ($allKeys as $key) {
            $translationRows[] = [
                'key'     => $key,
                'default' => $defaultTranslations[$key] ?? '',
                'value'   => $currentTranslations[$key] ?? '',
            ];
        }

        return $this->view('languages/edit', [
            'pageTitle'        => lang('admin.languages.edit_translations') . ' — ' . ($language->native_name ?? $code),
            'language'         => $language,
            'translationRows'  => $translationRows,
        ]);
    }

    /** Çevirileri kaydet */
    public function update(string $code): string
    {
        if (!core_csrf_valid('csrf', $_POST['_token'] ?? '')) {
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        $language = null;
        try {
            $language = Language::where('code', $code)->first();
        } catch (\Throwable $e) {
        }

        $translations = $_POST['translations'] ?? [];
        if (!is_array($translations)) {
            $translations = [];
        }

        $adminPath = env('ADMIN_PATH', 'admin');
        $redirectUrl = core_url($adminPath . '/languages/edit/' . $code);
        $flashBag = \Forecor\Core\SessionManager::get()->getFlashBag();

        // Dil DB'de yoksa (sadece dosya varsa) sadece dosyaya yaz
        if (!$language) {
            $filePath = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $code . '.php';
            if (!is_file($filePath)) {
                $flashBag->set('error', lang('admin.languages.no_language_for_code', ['code' => $code]));
                return $this->redirect(core_url($adminPath . '/languages'));
            }
            try {
                $this->saveTranslationsToFile($code, $translations);
                $flashBag->set('success', lang('admin.languages.saved'));
            } catch (\Throwable $e) {
                $flashBag->set('error', lang('admin.common.error'));
            }
            return $this->redirect($redirectUrl);
        }

        try {
            DB::beginTransaction();

            foreach ($translations as $fullKey => $value) {
                $value = (string) $value;
                $parts = explode('.', $fullKey, 2);
                $group = $parts[0] ?? 'general';
                $lineKey = $parts[1] ?? $fullKey;

                LanguageLine::updateOrCreate(
                    ['locale' => $code, 'group' => $group, 'key' => $lineKey],
                    ['value' => $value]
                );
            }

            DB::commit();

            // Dil dosyasını da güncelle
            $this->exportToFile($code);

            $flashBag->set('success', lang('admin.languages.saved'));
        } catch (\Throwable $e) {
            DB::rollBack();
            $flashBag->set('error', lang('admin.common.error'));
        }

        return $this->redirect($redirectUrl);
    }

    /** Dil sil */
    public function destroy(string $code): string
    {
        if (!core_csrf_valid('csrf', $_POST['_token'] ?? '')) {
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        try {
            $language = Language::where('code', $code)->first();
            if (!$language) {
                return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
            }

            $defaultLocale = (string) $this->app->getSetting('default_locale', core_config('app.locale', 'tr'));
            if ($code === $defaultLocale || $language->is_default) {
                \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.languages.cannot_delete_default'));
                return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
            }

            DB::beginTransaction();
            LanguageLine::where('locale', $code)->delete();
            $language->delete();
            DB::commit();

            \Forecor\Core\SessionManager::get()->getFlashBag()->set('success', lang('admin.languages.deleted'));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.common.error'));
        }

        return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
    }

    /** Varsayılan dil yap — sadece veritabanı (settings) tablosuna yazar. */
    public function setDefault(string $code): string
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $redirectUrl = core_url($adminPath . '/languages');
        $flashBag = \Forecor\Core\SessionManager::get()->getFlashBag();

        if (!core_csrf_valid('csrf', (string) ($_POST['_token'] ?? ''))) {
            $flashBag->set('error', lang('admin.sys_settings.invalid_request'));
            return $this->redirect($redirectUrl);
        }

        $code = trim($code);
        if ($code === '' || !preg_match('/^[a-z]{2,5}$/', $code)) {
            $flashBag->set('error', lang('admin.common.error') . ' (invalid code)');
            return $this->redirect($redirectUrl);
        }

        $langFile = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $code . '.php';
        if (!is_file($langFile)) {
            $flashBag->set('error', lang('admin.languages.no_language_for_code', ['code' => $code]));
            return $this->redirect($redirectUrl);
        }

        try {
            \App\Models\Setting::setValue('default_locale', $code, 'system');
            \Forecor\Core\Application::clearSettingCache('default_locale');

            try {
                Language::query()->update(['is_default' => false]);
                Language::where('code', $code)->update(['is_default' => true]);
            } catch (\Throwable $e) {
            }

            $flashBag->set('success', lang('admin.languages.default_changed'));
        } catch (\Throwable $e) {
            $detail = $e->getMessage();
            if ($detail === '') {
                $detail = get_class($e) . ' @ ' . $e->getFile() . ':' . $e->getLine();
            } else {
                $detail .= ' (' . $e->getFile() . ':' . $e->getLine() . ')';
            }
            $flashBag->set('error', lang('admin.common.error') . ' — ' . $detail);
        }

        return $this->redirect($redirectUrl);
    }

    /** Dışa aktar (PHP dosyası indir) */
    public function export(string $code): string
    {
        $translator = $this->app->translator();
        $lines = $translator->all($code);
        ksort($lines);

        $content = "<?php\n\nreturn [\n";
        foreach ($lines as $key => $value) {
            $escapedValue = str_replace("'", "\\'", $value);
            $content .= "    '{$key}' => '{$escapedValue}',\n";
        }
        $content .= "];\n";

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $code . '.php"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    /** İçe aktar (PHP dosyası yükle) */
    public function import(): string
    {
        if (!core_csrf_valid('csrf', $_POST['_token'] ?? '')) {
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        $targetCode = trim($_POST['target_locale'] ?? '');
        if ($targetCode === '') {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.common.error'));
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        if (!isset($_FILES['lang_file']) || $_FILES['lang_file']['error'] !== UPLOAD_ERR_OK) {
            $msg = (isset($_FILES['lang_file']['error']) && (int) $_FILES['lang_file']['error'] === UPLOAD_ERR_NO_FILE)
                ? lang('admin.languages.no_file_selected')
                : lang('admin.common.error');
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', $msg);
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        $tmpPath = $_FILES['lang_file']['tmp_name'];
        $lines = [];
        try {
            $lines = require $tmpPath;
        } catch (\Throwable $e) {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.common.error'));
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        if (!is_array($lines)) {
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.common.error'));
            return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
        }

        try {
            DB::beginTransaction();

            foreach ($lines as $fullKey => $value) {
                $parts = explode('.', (string) $fullKey, 2);
                $group = $parts[0] ?? 'general';
                $lineKey = $parts[1] ?? (string) $fullKey;

                LanguageLine::updateOrCreate(
                    ['locale' => $targetCode, 'group' => $group, 'key' => $lineKey],
                    ['value' => (string) $value]
                );
            }

            DB::commit();

            $this->exportToFile($targetCode);

            \Forecor\Core\SessionManager::get()->getFlashBag()->set('success', lang('admin.languages.imported'));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Forecor\Core\SessionManager::get()->getFlashBag()->set('error', lang('admin.common.error'));
        }

        return $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/languages'));
    }

    /** DB çevirilerini lang/{code}.php dosyasına yaz */
    protected function exportToFile(string $code): void
    {
        try {
            $dbLines = LanguageLine::where('locale', $code)
                ->get(['group', 'key', 'value'])
                ->mapWithKeys(function ($line) {
                    return [$line->group . '.' . $line->key => $line->value];
                })
                ->toArray();

            if (empty($dbLines)) {
                return;
            }

            $this->saveTranslationsToFile($code, $dbLines);
        } catch (\Throwable $e) {
            // Dosya yazılamadıysa sessizce devam
        }
    }

    /**
     * Verilen çevirileri mevcut lang dosyasıyla birleştirip dosyaya yazar.
     * &amp; gibi HTML entity'leri tek karaktere çevirir (dosyada & saklanır).
     */
    protected function saveTranslationsToFile(string $code, array $translations): void
    {
        $filePath = $this->app->getBasePath() . DIRECTORY_SEPARATOR . 'Inc' . DIRECTORY_SEPARATOR . 'Lang' . DIRECTORY_SEPARATOR . $code . '.php';
        $existing = is_file($filePath) ? (array) require $filePath : [];
        $merged = array_merge($existing, $translations);
        ksort($merged);

        $content = "<?php\n\nreturn [\n";
        foreach ($merged as $key => $value) {
            $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $escapedValue = str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
            $content .= "    '{$key}' => '{$escapedValue}',\n";
        }
        $content .= "];\n";

        file_put_contents($filePath, $content);
    }
}
