<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PermissionDefinition;

class AdminPermissionController extends AdminController
{
    public function index(): string
    {
        $permissions = PermissionDefinition::orderBy('group')->orderBy('key')->get();
        return $this->view('permissions/index', [
            'pageTitle' => lang('admin.permissions.title'),
            'permissions' => $permissions,
            'user' => $this->app->auth()->user()
        ]);
    }

    public function create(): string
    {
        return $this->view('permissions/form', [
            'pageTitle' => lang('admin.permissions.add_title'),
            'permission' => new PermissionDefinition(),
            'user' => $this->app->auth()->user()
        ]);
    }

    public function store(): void
    {
        PermissionDefinition::create([
            'key' => $_POST['key'] ?? '',
            'group' => $_POST['group'] ?? 'general',
            'description' => $_POST['description'] ?? '',
            'default_value' => isset($_POST['default_value']) ? 1 : 0,
        ]);

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/permissions'));
    }

    public function edit(string $id): string
    {
        $permission = PermissionDefinition::findOrFail((int)$id);
        return $this->view('permissions/form', [
            'pageTitle' => lang('admin.permissions.edit_title'),
            'permission' => $permission,
            'user' => $this->app->auth()->user()
        ]);
    }

    public function update(string $id): void
    {
        $permission = PermissionDefinition::findOrFail((int)$id);

        $permission->key = $_POST['key'] ?? $permission->key;
        $permission->group = $_POST['group'] ?? $permission->group;
        $permission->description = $_POST['description'] ?? $permission->description;
        $permission->default_value = isset($_POST['default_value']) ? 1 : 0;

        $permission->save();

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/permissions'));
    }

    public function delete(string $id): void
    {
        $permission = PermissionDefinition::findOrFail((int)$id);
        $permission->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/permissions'));
    }
}
