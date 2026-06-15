<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Category;
use App\Models\Prefix;
use App\Services\TopicPrefixScopeService;

class AdminPrefixController extends AdminController
{
    /** @return array<string, string> css_class => label (özet liste) */
    private function getPrefixStyleLabels(): array
    {
        $m = $this->stylePresetMap();

        return [
            $m['blue'] => admin__('prefixes.style_blue'),
            $m['amber'] => admin__('prefixes.style_amber'),
            $m['green'] => admin__('prefixes.style_green'),
            $m['red'] => admin__('prefixes.style_red'),
            $m['gray'] => admin__('prefixes.style_gray'),
        ];
    }

    /** @return array<string, string> key => tam Tailwind sınıf dizisi */
    private function stylePresetMap(): array
    {
        return [
            'blue' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20',
            'amber' => 'bg-amber-50 text-amber-700',
            'green' => 'bg-green-600 text-white',
            'red' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
            'gray' => 'bg-gray-100 text-gray-700',
        ];
    }

    private function detectPresetKey(string $cssClass): ?string
    {
        foreach ($this->stylePresetMap() as $key => $classes) {
            if ($classes === $cssClass) {
                return $key;
            }
        }

        return null;
    }

    private function normalizeHex(?string $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $v = strtoupper(trim($v));
        if (preg_match('/^#[0-9A-F]{6}$/', $v) === 1) {
            return $v;
        }

        return null;
    }

    private function normalizeIconClass(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $clean = preg_replace('/[^a-zA-Z0-9_\\-\\s\\.]/', '', $raw);

        return is_string($clean) ? substr($clean, 0, 64) : '';
    }

    private function resolveCssClassFromPost(): string
    {
        $map = $this->stylePresetMap();
        $default = $map['blue'];
        if (!empty($_POST['use_custom_css'])) {
            $c = trim((string) ($_POST['css_class_custom'] ?? ''));

            return $c !== '' ? $c : $default;
        }
        $key = (string) ($_POST['style_preset_key'] ?? 'blue');

        return $map[$key] ?? $default;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function normalizeBadgeColorsFromPost(): array
    {
        if (empty($_POST['use_hex_badge'])) {
            return [null, null];
        }
        $bg = $this->normalizeHex((string) ($_POST['badge_bg'] ?? ''));
        $tx = $this->normalizeHex((string) ($_POST['badge_text'] ?? ''));
        if ($bg === null || $tx === null) {
            return [null, null];
        }

        return [$bg, $tx];
    }

    public function index(): string
    {
        $prefixes = Prefix::with('category')->orderBy('sort_order')->get();
        $labels = $this->getPrefixStyleLabels();

        return $this->view('prefixes/index', [
            'pageTitle' => lang('admin.prefixes.page_title'),
            'prefixes' => $prefixes,
            'styleLabels' => $labels,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function categoriesWithForumTree()
    {
        return Category::with([
            'forumsTopLevel' => static fn ($q) => $q->orderBy('sort_order')->orderBy('id')->with([
                'subforums' => static fn ($s) => $s->orderBy('sort_order')->orderBy('id'),
            ]),
        ])->orderBy('sort_order')->get();
    }

    /**
     * @return array<string, list<array{id: int, name: string, subs: list<array{id: int, name: string}>}>>
     */
    private function forumsByCategoryForJson(): array
    {
        $out = [];
        foreach ($this->categoriesWithForumTree() as $cat) {
            $key = (string) $cat->id;
            $out[$key] = [];
            foreach ($cat->forumsTopLevel ?? [] as $f) {
                $subs = [];
                foreach ($f->subforums ?? [] as $s) {
                    $subs[] = ['id' => (int) $s->id, 'name' => (string) $s->name];
                }
                $out[$key][] = [
                    'id' => (int) $f->id,
                    'name' => (string) $f->name,
                    'subs' => $subs,
                ];
            }
        }

        return $out;
    }

    public function create(): string
    {
        $categories = $this->categoriesWithForumTree();

        return $this->view('prefixes/form', [
            'pageTitle' => lang('admin.prefixes.add_title'),
            'prefix' => new Prefix(),
            'categories' => $categories,
            'stylePresetMap' => $this->stylePresetMap(),
            'detectedPresetKey' => 'blue',
            'prefixForumIds' => [],
            'forums_by_category_json' => json_encode($this->forumsByCategoryForJson(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS),
        ]);
    }

    public function store(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = $name !== '' ? \Forecor\Core\Str::slug($name) . '-' . time() : '';
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $css = $this->resolveCssClassFromPost();
        [$bg, $tx] = $this->normalizeBadgeColorsFromPost();
        $prefix = Prefix::create([
            'name' => $name,
            'slug' => $slug,
            'css_class' => $css,
            'icon_class' => $this->normalizeIconClass((string) ($_POST['icon_class'] ?? '')) ?: null,
            'badge_bg' => $bg,
            'badge_text' => $tx,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'category_id' => $categoryId > 0 ? $categoryId : null,
        ]);
        $forumIds = isset($_POST['prefix_forum_ids']) && is_array($_POST['prefix_forum_ids'])
            ? array_map('intval', $_POST['prefix_forum_ids']) : [];
        if ($categoryId > 0) {
            TopicPrefixScopeService::syncPrefixForumAssignmentsInCategory((int) $prefix->id, $categoryId, $forumIds);
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/prefixes'));
    }

    public function edit(string $id): string
    {
        $prefix = Prefix::findOrFail((int) $id);
        $categories = $this->categoriesWithForumTree();
        $key = $this->detectPresetKey((string) ($prefix->css_class ?? ''));
        $detectedPresetKey = $key ?? 'blue';
        $isCustom = $key === null && trim((string) ($prefix->css_class ?? '')) !== '';

        return $this->view('prefixes/form', [
            'pageTitle' => lang('admin.prefixes.edit_title'),
            'prefix' => $prefix,
            'categories' => $categories,
            'stylePresetMap' => $this->stylePresetMap(),
            'detectedPresetKey' => $detectedPresetKey,
            'isCustomCss' => $isCustom,
            'prefixForumIds' => TopicPrefixScopeService::forumIdsHavingPrefixExplicit((int) $prefix->id),
            'forums_by_category_json' => json_encode($this->forumsByCategoryForJson(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS),
        ]);
    }

    public function update(string $id): void
    {
        $prefix = Prefix::findOrFail((int) $id);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $prefix->name = (string) ($_POST['name'] ?? '');
        $prefix->css_class = $this->resolveCssClassFromPost();
        [$bg, $tx] = $this->normalizeBadgeColorsFromPost();
        $prefix->badge_bg = $bg;
        $prefix->badge_text = $tx;
        $prefix->icon_class = $this->normalizeIconClass((string) ($_POST['icon_class'] ?? '')) ?: null;
        $prefix->sort_order = (int) ($_POST['sort_order'] ?? 0);
        $prefix->category_id = $categoryId > 0 ? $categoryId : null;
        $prefix->save();
        $forumIds = isset($_POST['prefix_forum_ids']) && is_array($_POST['prefix_forum_ids'])
            ? array_map('intval', $_POST['prefix_forum_ids']) : [];
        if ($categoryId > 0) {
            TopicPrefixScopeService::syncPrefixForumAssignmentsInCategory((int) $prefix->id, $categoryId, $forumIds);
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/prefixes'));
    }

    public function delete(string $id): void
    {
        $prefix = Prefix::findOrFail((int) $id);
        TopicPrefixScopeService::deletePrefixFromScopeTables((int) $prefix->id);
        $prefix->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/prefixes'));
    }
}
