<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers\Admin;

use App\Modules\Idelist\Models\IdeaCategory;
use App\Modules\Idelist\Requests\StoreCategoryRequest;
use Forecor\Core\Str;

class CategoryAdminController extends IdelistAdminController
{
    public function index(): string
    {
        return $this->view('idelist/categories', ['categories' => IdeaCategory::query()->orderBy('sort_order')->orderBy('name')->get(), 'pageTitle' => lang('idelist.admin_title')]);
    }

    public function store(): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_category', (string) ($_POST['_token'] ?? ''), 'idelist/categories', lang('common.invalid_csrf'));
        $req = new StoreCategoryRequest();
        if ($req->validate()) {
            $data = $req->validated();
            IdeaCategory::query()->create([
                'name' => (string) $data['name'],
                'slug' => Str::slug((string) $data['name']),
                'color' => (string) ($data['color'] ?? ''),
                'icon' => (string) ($data['icon'] ?? ''),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
        }
        $this->redirectAdmin('idelist/categories');
    }

    public function update(string $id): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_category', (string) ($_POST['_token'] ?? ''), 'idelist/categories', lang('common.invalid_csrf'));
        $req = new StoreCategoryRequest();
        if ($req->validate()) {
            $data = $req->validated();
            $cat = IdeaCategory::query()->findOrFail((int) $id);
            $cat->name = (string) $data['name'];
            $cat->slug = Str::slug((string) $data['name']);
            $cat->color = (string) ($data['color'] ?? '');
            $cat->icon = (string) ($data['icon'] ?? '');
            $cat->sort_order = (int) ($data['sort_order'] ?? 0);
            $cat->save();
        }
        $this->redirectAdmin('idelist/categories');
    }

    public function destroy(string $id): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_category', (string) ($_POST['_token'] ?? ''), 'idelist/categories', lang('common.invalid_csrf'));
        IdeaCategory::query()->findOrFail((int) $id)->delete();
        $this->redirectAdmin('idelist/categories');
    }
}
