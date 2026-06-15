<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Role;

class AdminRoleController extends AdminController
{
    public function index(): string
    {
        $roles = Role::orderBy('sort_order')->get();
        return $this->view('roles/index', [
            'pageTitle' => lang('admin.roles.title'),
            'roles' => $roles,
            'user' => $this->app->auth()->user()
        ]);
    }

    public function create(): string
    {
        return $this->view('roles/form', [
            'pageTitle' => lang('admin.roles.add_title'),
            'role' => new Role(),
            'user' => $this->app->auth()->user()
        ]);
    }

    public function store(): void
    {
        $name = $_POST['name'] ?? '';
        $slug = mb_strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        Role::create([
            'name' => $name,
            'slug' => $slug,
            'color' => $_POST['color'] ?? '#6c757d',
            'is_staff' => isset($_POST['is_staff']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'pm_daily_limit' => max(0, (int)($_POST['pm_daily_limit'] ?? 0)),
            'pm_inbox_limit' => max(0, (int)($_POST['pm_inbox_limit'] ?? 0)),
            'pm_daily_receive_limit' => max(0, (int)($_POST['pm_daily_receive_limit'] ?? 0)),
            'pm_lifetime_total_quota' => max(0, (int)($_POST['pm_lifetime_total_quota'] ?? 0)),
            'daily_topic_limit' => max(0, (int)($_POST['daily_topic_limit'] ?? 0)),
            'bump_per_day' => max(0, (int)($_POST['bump_per_day'] ?? 0)),
        ]);

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/roles'));
    }

    public function edit(string $id): string
    {
        $role = Role::findOrFail((int)$id);
        return $this->view('roles/form', [
            'pageTitle' => lang('admin.roles.edit_title'),
            'role' => $role,
            'user' => $this->app->auth()->user()
        ]);
    }

    public function update(string $id): void
    {
        $role = Role::findOrFail((int)$id);

        $name = $_POST['name'] ?? $role->name;
        $slug = mb_strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        $role->name = $name;
        $role->slug = $slug;
        $role->color = $_POST['color'] ?? $role->color;
        $role->is_staff = isset($_POST['is_staff']) ? 1 : 0;
        $role->sort_order = (int)($_POST['sort_order'] ?? $role->sort_order);
        $role->pm_daily_limit = max(0, (int)($_POST['pm_daily_limit'] ?? $role->pm_daily_limit ?? 0));
        $role->pm_inbox_limit = max(0, (int)($_POST['pm_inbox_limit'] ?? $role->pm_inbox_limit ?? 0));
        $role->pm_daily_receive_limit = max(0, (int)($_POST['pm_daily_receive_limit'] ?? $role->pm_daily_receive_limit ?? 0));
        $role->pm_lifetime_total_quota = max(0, (int)($_POST['pm_lifetime_total_quota'] ?? $role->pm_lifetime_total_quota ?? 0));
        $role->daily_topic_limit = max(0, (int)($_POST['daily_topic_limit'] ?? $role->daily_topic_limit ?? 0));
        $role->bump_per_day = max(0, (int)($_POST['bump_per_day'] ?? $role->bump_per_day ?? 0));

        $role->save();

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/roles'));
    }

    public function delete(string $id): void
    {
        $role = Role::findOrFail((int)$id);

        // Prevent deleting Admin (usually ID 1) or Member (usually ID 2) logic可以 be added here
        if ($role->id === 1 || $role->id === 2) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/roles')); // Prevent deleting core groups
            return;
        }

        $role->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/roles'));
    }
}
