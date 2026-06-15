<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers\Admin;

use App\Modules\Idelist\Models\Idea;
use App\Modules\Idelist\Models\IdeaCategory;
use App\Modules\Idelist\Models\IdeaStatusDefinition;
use App\Modules\Idelist\Requests\UpdateIdeaStatusRequest;
use App\Modules\Idelist\Services\IdeaStatusService;

class IdeaAdminController extends IdelistAdminController
{
    public function __construct(\Forecor\Core\Application $app, private ?IdeaStatusService $statusService = null)
    {
        parent::__construct($app);
        $this->statusService ??= new IdeaStatusService();
    }

    public function index(): string
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($_GET['per_page'] ?? 20)));
        $status = trim((string) ($_GET['status'] ?? ''));
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $sort = trim((string) ($_GET['sort'] ?? 'latest'));
        $queryText = trim((string) ($_GET['q'] ?? ''));

        $query = Idea::query()->with(['user', 'category'])->orderByDesc('is_pinned');
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }
        if ($queryText !== '') {
            $query->where('title', 'like', '%' . $queryText . '%');
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at');
                break;
            case 'votes':
                $query->orderByDesc('vote_count')->orderByDesc('created_at');
                break;
            case 'updated':
                $query->orderByDesc('updated_at');
                break;
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $total = (clone $query)->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $ideas = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return $this->view('idelist/index', [
            'ideas' => $ideas,
            'statuses' => IdeaStatusDefinition::query()->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => IdeaCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'filters' => [
                'status' => $status,
                'category_id' => $categoryId > 0 ? (string) $categoryId : '',
                'sort' => $sort,
                'q' => $queryText,
                'per_page' => $perPage,
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
            'pageTitle' => lang('idelist.admin_title'),
        ]);
    }

    public function editStatus(string $id): string
    {
        return $this->view('idelist/edit-status', [
            'idea' => Idea::query()->findOrFail((int) $id),
            'statuses' => IdeaStatusDefinition::query()->orderBy('sort_order')->orderBy('name')->get(),
            'pageTitle' => lang('idelist.admin_title'),
        ]);
    }

    public function updateStatus(string $id): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_status', (string) ($_POST['_token'] ?? ''), 'idelist/' . $id . '/status', lang('common.invalid_csrf'));
        $idea = Idea::query()->findOrFail((int) $id);
        $req = new UpdateIdeaStatusRequest();
        if ($req->validate()) {
            $data = $req->validated();
            try {
                $this->statusService->transition($idea, (string) ($data['status'] ?? ''), $data);
                $newSlug = (string) ($data['status'] ?? '');
                $def = IdeaStatusDefinition::query()->where('slug', $newSlug)->first();
                $msg = ($def !== null && $def->requires_completion) ? lang('idelist.marked_completed') : lang('idelist.status_updated');
                $this->app->session()->getFlashBag()->add('success', $msg);
            } catch (\InvalidArgumentException $e) {
                $this->app->session()->getFlashBag()->add('error', $e->getMessage());
                $this->redirectAdmin('idelist/' . $id . '/status');
            }
        } else {
            $err = $req->firstError();
            $this->app->session()->getFlashBag()->add('error', $err !== null && $err !== '' ? $err : lang('idelist.status_validation_failed'));
            $this->redirectAdmin('idelist/' . $id . '/status');
        }
        $this->redirectAdmin('idelist');
    }

    public function destroy(string $id): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_delete', (string) ($_POST['_token'] ?? ''), 'idelist', lang('common.invalid_csrf'));
        Idea::query()->findOrFail((int) $id)->delete();
        $this->redirectAdmin('idelist');
    }

    public function togglePin(string $id): void
    {
        $this->requireCsrfOrRedirect('idelist_admin_pin', (string) ($_POST['_token'] ?? ''), 'idelist', lang('common.invalid_csrf'));
        $idea = Idea::query()->findOrFail((int) $id);
        $idea->is_pinned = !$idea->is_pinned;
        $idea->save();
        $this->redirectAdmin('idelist');
    }
}
