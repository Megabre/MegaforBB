<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserFieldDefinition;

/**
 * Admin: Kullanıcı özel alanı tanımları (CRUD).
 */
class AdminCustomFieldController extends AdminController
{
    public function index(): string
    {
        $list = [];
        try {
            $list = UserFieldDefinition::orderBy('sort_order')->orderBy('id')
                ->get(['id', 'name', 'field_key', 'field_type', 'is_required', 'show_on_registration', 'show_on_profile', 'show_in_postbit', 'sort_order'])
                ->all();
        } catch (\Throwable $e) {
        }
        return $this->view('custom_fields/index', [
            'pageTitle' => lang('admin.custom_fields.title'),
            'list' => $list,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function create(): string
    {
        return $this->view('custom_fields/form', [
            'pageTitle' => lang('admin.custom_fields.add_title'),
            'def' => null,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function store(): void
    {
        if (!core_csrf_valid('admin_custom_field', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/custom-fields'));
            return;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $fieldKey = $this->slugify(trim((string)($_POST['field_key'] ?? '')) ?: $this->slugify($name));
        if ($fieldKey === '') {
            $fieldKey = 'field_' . time();
        }
        UserFieldDefinition::create([
            'name' => $name,
            'field_key' => $fieldKey,
            'field_type' => in_array($_POST['field_type'] ?? '', ['text', 'number', 'date', 'textarea', 'select'], true) ? $_POST['field_type'] : 'text',
            'field_options' => trim((string)($_POST['field_options'] ?? '')) ?: null,
            'is_required' => isset($_POST['is_required']) && $_POST['is_required'] === '1' ? 1 : 0,
            'show_on_registration' => isset($_POST['show_on_registration']) && $_POST['show_on_registration'] === '1' ? 1 : 0,
            'show_on_profile' => isset($_POST['show_on_profile']) && $_POST['show_on_profile'] === '1' ? 1 : 0,
            'show_in_postbit' => isset($_POST['show_in_postbit']) && $_POST['show_in_postbit'] === '1' ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/custom-fields'));
    }

    public function edit(string $id): string
    {
        $def = UserFieldDefinition::find($id);
        if (!$def) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/custom-fields'));
            return '';
        }
        return $this->view('custom_fields/form', [
            'pageTitle' => lang('admin.custom_fields.edit_title'),
            'def' => $def,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function update(string $id): void
    {
        if (!core_csrf_valid('admin_custom_field', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/custom-fields'));
            return;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $fieldKey = trim((string)($_POST['field_key'] ?? ''));
        $fieldOptions = trim((string)($_POST['field_options'] ?? ''));
        UserFieldDefinition::where('id', $id)->update([
            'name' => $name,
            'field_key' => $fieldKey,
            'field_type' => in_array($_POST['field_type'] ?? '', ['text', 'number', 'date', 'textarea', 'select'], true) ? $_POST['field_type'] : 'text',
            'field_options' => $fieldOptions ?: null,
            'is_required' => isset($_POST['is_required']) && $_POST['is_required'] === '1' ? 1 : 0,
            'show_on_registration' => isset($_POST['show_on_registration']) && $_POST['show_on_registration'] === '1' ? 1 : 0,
            'show_on_profile' => isset($_POST['show_on_profile']) && $_POST['show_on_profile'] === '1' ? 1 : 0,
            'show_in_postbit' => isset($_POST['show_in_postbit']) && $_POST['show_in_postbit'] === '1' ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/custom-fields'));
    }

    public function delete(string $id): void
    {
        if (!core_csrf_valid('admin_custom_field_delete', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/custom-fields'));
            return;
        }
        UserFieldDefinition::where('id', $id)->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/custom-fields'));
    }

    private function slugify(string $s): string
    {
        $s = preg_replace('/[^a-z0-9_-]/i', '_', $s);
        return strtolower(preg_replace('/_+/', '_', trim($s, '_')));
    }
}
