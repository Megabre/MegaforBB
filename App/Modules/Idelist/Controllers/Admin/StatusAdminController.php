<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers\Admin;

use App\Modules\Idelist\Models\Idea;
use App\Modules\Idelist\Models\IdeaStatusDefinition;
use App\Modules\Idelist\Requests\StoreStatusDefinitionRequest;
use Forecor\Core\Str;

class StatusAdminController extends IdelistAdminController
{
    public function index(): string
    {
        return $this->view('idelist/statuses', [
            'statuses' => IdeaStatusDefinition::query()->orderBy('sort_order')->orderBy('name')->get(),
            'pageTitle' => lang('idelist.admin_title'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_status_def', (string) ($_POST['_token'] ?? ''), 'idelist/statuses', lang('common.invalid_csrf'));
        $req = new StoreStatusDefinitionRequest();
        if (!$req->validate()) {
            $this->app->session()->getFlashBag()->add('error', $req->firstError() ?? lang('idelist.status_validation_failed'));
            $this->redirectAdmin('idelist/statuses');
        }
        $data = $req->validated();
        $slug = trim((string) ($_POST['slug'] ?? ''));
        if ($slug === '') {
            $slug = Str::slug((string) $data['name'], '_');
        }
        if ($slug === '' || !preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $slug)) {
            $this->app->session()->getFlashBag()->add('error', lang('idelist.status_invalid_slug'));
            $this->redirectAdmin('idelist/statuses');
        }
        if (IdeaStatusDefinition::query()->where('slug', $slug)->exists()) {
            $this->app->session()->getFlashBag()->add('error', lang('idelist.status_slug_taken'));
            $this->redirectAdmin('idelist/statuses');
        }

        $requiresCompletion = isset($_POST['requires_completion']) && (string) $_POST['requires_completion'] === '1';
        $defaultApproval = isset($_POST['default_on_approval']) && (string) $_POST['default_on_approval'] === '1';
        $defaultOpen = isset($_POST['default_on_open']) && (string) $_POST['default_on_open'] === '1';

        $row = IdeaStatusDefinition::query()->create([
            'slug' => $slug,
            'name' => (string) $data['name'],
            'color' => trim((string) ($data['color'] ?? '')) ?: null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'requires_completion' => $requiresCompletion,
            'default_on_approval' => $defaultApproval,
            'default_on_open' => $defaultOpen,
        ]);
        IdeaStatusDefinition::syncExclusiveDefaults($row);
        $this->app->session()->getFlashBag()->add('success', lang('idelist.status_created'));
        $this->redirectAdmin('idelist/statuses');
    }

    public function update(string $id): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_status_def', (string) ($_POST['_token'] ?? ''), 'idelist/statuses', lang('common.invalid_csrf'));
        $req = new StoreStatusDefinitionRequest();
        if (!$req->validate()) {
            $this->app->session()->getFlashBag()->add('error', $req->firstError() ?? lang('idelist.status_validation_failed'));
            $this->redirectAdmin('idelist/statuses');
        }
        $data = $req->validated();
        $row = IdeaStatusDefinition::query()->findOrFail((int) $id);

        $requiresCompletion = isset($_POST['requires_completion']) && (string) $_POST['requires_completion'] === '1';
        $defaultApproval = isset($_POST['default_on_approval']) && (string) $_POST['default_on_approval'] === '1';
        $defaultOpen = isset($_POST['default_on_open']) && (string) $_POST['default_on_open'] === '1';

        $row->name = (string) $data['name'];
        $row->color = trim((string) ($data['color'] ?? '')) ?: null;
        $row->sort_order = (int) ($data['sort_order'] ?? 0);
        $row->requires_completion = $requiresCompletion;
        $row->default_on_approval = $defaultApproval;
        $row->default_on_open = $defaultOpen;
        $row->save();
        IdeaStatusDefinition::syncExclusiveDefaults($row);
        $this->app->session()->getFlashBag()->add('success', lang('idelist.status_saved'));
        $this->redirectAdmin('idelist/statuses');
    }

    public function destroy(string $id): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_status_def', (string) ($_POST['_token'] ?? ''), 'idelist/statuses', lang('common.invalid_csrf'));
        $row = IdeaStatusDefinition::query()->findOrFail((int) $id);
        $used = Idea::query()->where('status', $row->slug)->count();
        if ($used > 0) {
            $this->app->session()->getFlashBag()->add('error', lang('idelist.status_delete_has_ideas', ['count' => $used]));
            $this->redirectAdmin('idelist/statuses');
        }
        $row->delete();
        $this->app->session()->getFlashBag()->add('success', lang('idelist.status_deleted'));
        $this->redirectAdmin('idelist/statuses');
    }
}
