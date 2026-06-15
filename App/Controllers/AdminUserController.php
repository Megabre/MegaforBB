<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Role;
use App\Models\User;

class AdminUserController extends AdminController
{
    public function index(): string
    {
        $users = User::with('role')->orderBy('id', 'desc')->get();
        return $this->view('users/index', [
            'pageTitle' => admin__('menu.users'),
            'users' => $users,
            'user' => $this->app->auth()->user()
        ]);
    }

    public function create(): string
    {
        $roles = Role::orderBy('sort_order')->get();
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        $hasInviteColumns = $schema->hasColumn('users', 'available_invites');
        $userError = $this->app->session()->getFlashBag()->get('user_error');
        $userError = is_array($userError) ? ($userError[0] ?? '') : $userError;
        return $this->view('users/form', [
            'pageTitle' => admin__('common.add') . ' ' . admin__('menu.users'),
            'editUser' => new User(),
            'roles' => $roles,
            'user' => $this->app->auth()->user(),
            'hasInviteColumns' => $hasInviteColumns,
            'user_error' => $userError,
            'hasProfileFields' => $this->hasProfileColumns($schema),
        ]);
    }

    private const CSRF_TOKEN = 'csrf';

    public function store(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
            return;
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role_id = (int)($_POST['role_id'] ?? 2);
        $role_id = $this->allowedRoleId($role_id);

        $formatErr = \App\Services\AuthService::validateUsernameFormat($username);
        if ($formatErr !== null) {
            $this->app->session()->getFlashBag()->add('user_error', $formatErr);
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users/create'));
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->app->session()->getFlashBag()->add('user_error', lang('auth.valid_email'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users/create'));
            return;
        }
        if (User::where('email', $email)->exists()) {
            $this->app->session()->getFlashBag()->add('user_error', lang('admin.users.email_taken'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users/create'));
            return;
        }

        $data = [
            'username' => $username,
            'email' => $email,
            'role_id' => $role_id,
            'is_banned' => isset($_POST['is_banned']) ? 1 : 0,
            'is_verified' => isset($_POST['is_verified']) ? 1 : 0,
            'locale' => $_POST['locale'] ?? 'tr',
            'location' => $_POST['location'] ?? null,
            'website' => $_POST['website'] ?? null,
            'bio' => $_POST['bio'] ?? null,
            'custom_title' => $_POST['custom_title'] ?? null,
            'warning_points' => (int)($_POST['warning_points'] ?? 0),
            'reward_points' => (int)($_POST['reward_points'] ?? 0),
        ];
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        if ($schema->hasColumn('users', 'available_invites')) {
            $data['available_invites'] = max(0, (int)($_POST['available_invites'] ?? 0));
        }
        if ($schema->hasColumn('users', 'first_name')) {
            $data['first_name'] = trim((string)($_POST['first_name'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'last_name')) {
            $data['last_name'] = trim((string)($_POST['last_name'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'show_name')) {
            $data['show_name'] = isset($_POST['show_name']) && $_POST['show_name'] === '1' ? 1 : 0;
        }
        if ($schema->hasColumn('users', 'birthday')) {
            $b = trim((string)($_POST['birthday'] ?? ''));
            $data['birthday'] = ($b !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $b)) ? $b : null;
        }
        if ($schema->hasColumn('users', 'avatar_path')) {
            $data['avatar_path'] = trim((string)($_POST['avatar_path'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'cover_photo_path')) {
            $data['cover_photo_path'] = trim((string)($_POST['cover_photo_path'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'signature')) {
            $sig = trim((string)($_POST['signature'] ?? ''));
            $data['signature'] = $sig !== '' ? \core_sanitize_signature($sig) : null;
        }

        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        User::create($data);

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
    }

    public function edit(string $id): string
    {
        $editUser = User::with('usedInvitation.inviter')->findOrFail((int)$id);
        $roles = Role::orderBy('sort_order')->get();
        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        $hasInviteColumns = $schema->hasColumn('users', 'available_invites');
        $userError = $this->app->session()->getFlashBag()->get('user_error');
        $userError = is_array($userError) ? ($userError[0] ?? '') : $userError;
        return $this->view('users/form', [
            'pageTitle' => admin__('common.edit') . ' ' . admin__('menu.users'),
            'editUser' => $editUser,
            'roles' => $roles,
            'user' => $this->app->auth()->user(),
            'hasInviteColumns' => $hasInviteColumns,
            'user_error' => $userError,
            'hasProfileFields' => $this->hasProfileColumns($schema),
        ]);
    }

    private function hasProfileColumns(\Illuminate\Database\Schema\Builder $schema): bool
    {
        return $schema->hasColumn('users', 'first_name')
            || $schema->hasColumn('users', 'last_name')
            || $schema->hasColumn('users', 'avatar_path')
            || $schema->hasColumn('users', 'cover_photo_path')
            || $schema->hasColumn('users', 'signature')
            || $schema->hasColumn('users', 'show_name')
            || $schema->hasColumn('users', 'birthday');
    }

    public function update(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
            return;
        }
        $editUser = User::findOrFail((int)$id);
        $wasBanned = (int)($editUser->is_banned ?? 0);
        $requestedRoleId = (int)($_POST['role_id'] ?? $editUser->role_id);
        $newUsername = trim((string) ($_POST['username'] ?? $editUser->username));
        $formatErr = \App\Services\AuthService::validateUsernameFormat($newUsername);
        if ($formatErr !== null) {
            $this->app->session()->getFlashBag()->add('user_error', $formatErr);
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users/edit/' . $id));
            return;
        }
        $editUser->username = $newUsername;
        $newEmail = trim((string) ($_POST['email'] ?? $editUser->email));
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->app->session()->getFlashBag()->add('user_error', lang('auth.valid_email'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users/edit/' . $id));
            return;
        }
        $emailTaken = User::where('email', $newEmail)->where('id', '!=', $editUser->id)->exists();
        if ($emailTaken) {
            $this->app->session()->getFlashBag()->add('user_error', lang('admin.users.email_taken'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users/edit/' . $id));
            return;
        }
        $editUser->email = $newEmail;
        $editUser->role_id = $this->allowedRoleId($requestedRoleId);
        $editUser->is_banned = isset($_POST['is_banned']) ? 1 : 0;
        $editUser->is_verified = isset($_POST['is_verified']) ? 1 : 0;

        $editUser->locale = $_POST['locale'] ?? 'tr';
        $editUser->location = $_POST['location'] ?? null;
        $editUser->website = $_POST['website'] ?? null;
        $editUser->bio = $_POST['bio'] ?? null;

        $editUser->custom_title = $_POST['custom_title'] ?? null;
        $editUser->warning_points = (int)($_POST['warning_points'] ?? 0);
        $editUser->reward_points = (int)($_POST['reward_points'] ?? 0);

        $schema = \Illuminate\Database\Capsule\Manager::connection()->getSchemaBuilder();
        if ($schema->hasColumn('users', 'available_invites')) {
            $editUser->available_invites = max(0, (int)($_POST['available_invites'] ?? $editUser->available_invites ?? 0));
        }
        if ($schema->hasColumn('users', 'first_name')) {
            $editUser->first_name = trim((string)($_POST['first_name'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'last_name')) {
            $editUser->last_name = trim((string)($_POST['last_name'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'show_name')) {
            $editUser->show_name = isset($_POST['show_name']) && $_POST['show_name'] === '1' ? 1 : 0;
        }
        if ($schema->hasColumn('users', 'birthday')) {
            $b = trim((string)($_POST['birthday'] ?? ''));
            $editUser->birthday = ($b !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $b)) ? $b : null;
        }
        if ($schema->hasColumn('users', 'avatar_path')) {
            $editUser->avatar_path = trim((string)($_POST['avatar_path'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'cover_photo_path')) {
            $editUser->cover_photo_path = trim((string)($_POST['cover_photo_path'] ?? '')) ?: null;
        }
        if ($schema->hasColumn('users', 'signature')) {
            $sig = trim((string)($_POST['signature'] ?? ''));
            $editUser->signature = $sig !== '' ? \core_sanitize_signature($sig) : null;
        }

        $password = $_POST['password'] ?? '';
        if (!empty($password)) {
            $editUser->password_hash = password_hash($password, PASSWORD_DEFAULT);
        }

        $editUser->save();

        if ((int)$editUser->is_banned === 1 && $wasBanned === 0) {
            $adminUsername = $this->app->auth()->user()->username ?? 'Admin';
            \App\Services\WebhookService::notifyUserBanned([$editUser->id], $adminUsername);
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
    }

    /** Sadece kendi yetkisi dahilindeki role_id atanabilir (admin=1 kendisi değilse role_id=1 atayamaz). */
    private function allowedRoleId(int $requested): int
    {
        $current = (int) ($this->app->auth()->user()->role_id ?? 0);
        if ($current === 1) {
            return $requested;
        }
        if ($requested === 1) {
            return 2;
        }
        return $requested;
    }

    public function delete(string $id): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
            return;
        }
        $editUser = User::findOrFail((int)$id);

        // Prevent deleting oneself
        if ($editUser->id === $this->app->auth()->user()->id) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users')); // Maybe add error flash later
            return;
        }

        $editUser->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
    }

    public function bulk(): void
    {
        if (!core_csrf_valid(self::CSRF_TOKEN, (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
            return;
        }
        $action = (string) ($_POST['action'] ?? '');
        $userIds = $_POST['user_ids'] ?? [];

        if (empty($userIds) || !is_array($userIds)) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
            return;
        }

        // Sanitize IDs
        $userIds = array_map('intval', $userIds);
        $currentUserId = $this->app->auth()->user()->id;

        // Prevent self-action on bulk delete or ban
        $filteredIds = array_filter($userIds, fn ($id) => $id !== $currentUserId);

        switch ($action) {
            case 'delete':
                if (!empty($filteredIds)) {
                    User::whereIn('id', $filteredIds)->delete();
                }
                break;
            case 'ban':
                if (!empty($filteredIds)) {
                    User::whereIn('id', $filteredIds)->update(['is_banned' => 1]);
                    $adminUsername = $this->app->auth()->user()->username ?? 'Admin';
                    \App\Services\WebhookService::notifyUserBanned($filteredIds, $adminUsername);
                }
                break;
            case 'unban':
                User::whereIn('id', $userIds)->update(['is_banned' => 0]);
                break;
            case 'export':
                $users = User::whereIn('id', $userIds)->get();
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=users_export_' . date('Y-m-d') . '.csv');
                $output = fopen('php://output', 'w');
                // CSV Header
                fputcsv($output, ['ID', 'Username', 'Email', 'Role ID', 'Is Verified', 'Is Banned', 'Warning Points', 'Reward Points', 'Registered At']);
                foreach ($users as $u) {
                    fputcsv($output, [
                        $u->id,
                        $u->username,
                        $u->email,
                        $u->role_id,
                        $u->is_verified,
                        $u->is_banned,
                        $u->warning_points,
                        $u->reward_points,
                        $u->created_at ? $u->created_at->format('Y-m-d H:i:s') : ''
                    ]);
                }
                fclose($output);
                exit;
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/users'));
    }
}
