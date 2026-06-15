<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\GroupPermission;
use App\Models\PermissionDefinition;
use App\Models\Role;

class AdminGroupPermissionController extends AdminController
{
    /** İzin anahtarı => açıklama (dil dosyası yüklenmezse veya DB boşsa kullanılır) */
    private const PERMISSION_DESCRIPTIONS = [
        'forum.view' => 'Forumu ve konuları görüntüleyebilir.',
        'forum.create_thread' => 'Yeni konu açabilir.',
        'forum.create_post' => 'Konulara yanıt yazabilir.',
        'forum.edit_own_post' => 'Kendi mesajlarını düzenleyebilir.',
        'forum.delete_own_post' => 'Kendi mesajlarını silebilir.',
        'mod.edit_post' => 'Başkalarının mesajlarını düzenleyebilir.',
        'mod.delete_post' => 'Başkalarının mesajlarını silebilir.',
        'mod.delete_thread' => 'Konuları silebilir.',
        'mod.lock_thread' => 'Konuları kilitleyebilir / açabilir.',
        'mod.move_thread' => 'Konuları başka foruma taşıyabilir.',
        'mod.stick_thread' => 'Konuları sabitleyebilir.',
        'admin.manage_users' => 'Kullanıcıları yönetebilir, yasaklayabilir.',
        'admin.manage_roles' => 'Grup ve rolleri yönetebilir.',
        'admin.manage_forums' => 'Kategori ve forumları yönetebilir.',
        'admin.manage_settings' => 'Genel sistem ayarlarını değiştirebilir.',
    ];

    public function index(): string
    {
        $roles = Role::orderBy('sort_order')->get();
        return $this->view('group_permissions/index', [
            'pageTitle' => lang('admin.group_permissions.page_title'),
            'roles' => $roles,
            'user' => $this->app->auth()->user()
        ]);
    }

    public function edit(string $id): string
    {
        $role = Role::findOrFail((int)$id);

        $definitions = PermissionDefinition::orderBy('group')->orderBy('key')->get();
        $currentPermissions = GroupPermission::where('role_id', $role->id)
            ->get()
            ->keyBy('permission_id');

        $groupedDefinitions = [];
        $permissionDescriptions = [];
        foreach ($definitions as $def) {
            $groupedDefinitions[$def->group][] = $def;
            $key = (string) $def->key;
            $desc = lang('permissions.' . $key);
            $langKey = 'permissions.' . $key;
            if ($desc !== $langKey && $desc !== '') {
                $permissionDescriptions[$def->id] = $desc;
            } elseif (trim((string)($def->description ?? '')) !== '') {
                $permissionDescriptions[$def->id] = trim((string) $def->description);
            } else {
                $permissionDescriptions[$def->id] = self::PERMISSION_DESCRIPTIONS[$key] ?? $key;
            }
        }

        return $this->view('group_permissions/form', [
            'pageTitle' => lang('admin.group_permissions.edit_title', ['name' => $role->name]),
            'role' => $role,
            'definitions' => $definitions,
            'groupedDefinitions' => $groupedDefinitions,
            'currentPermissions' => $currentPermissions,
            'permissionDescriptions' => $permissionDescriptions,
        ]);
    }

    public function update(string $id): void
    {
        $role = Role::findOrFail((int)$id);

        $permissionsInput = $_POST['permissions'] ?? [];

        // Delete all existing overrides for this role first
        GroupPermission::where('role_id', $role->id)->delete();

        $insertData = [];
        foreach ($permissionsInput as $permissionId => $value) {
            $insertData[] = [
                'role_id' => $role->id,
                'permission_id' => (int)$permissionId,
                'value' => (int)$value,
            ];
        }

        if (!empty($insertData)) {
            GroupPermission::insert($insertData);
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/group-permissions'));
    }
}
