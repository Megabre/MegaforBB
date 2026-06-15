<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers;

use App\Controllers\BaseController;
use App\Modules\Idelist\Models\Idea;
use App\Modules\Idelist\Models\IdeaCategory;
use App\Modules\Idelist\Models\IdeaStatusDefinition;
use App\Modules\Idelist\Models\IdeaVote;
use App\Modules\Idelist\Models\IdelistSetting;
use App\Modules\Idelist\Requests\StoreIdeaRequest;
use Forecor\Core\Str;

class IdeaController extends BaseController
{
    public function disabled(): string
    {
        return $this->layout('idelist/disabled', ['pageTitle' => lang('idelist.page_title')], true);
    }

    public function index(): string
    {
        if (IdelistSetting::getValue('allow_anonymous_view', '1') !== '1' && !$this->app->auth()->user()) {
            $this->redirect(core_url('login'));
        }

        $category = trim((string) ($_GET['category'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $sort = trim((string) ($_GET['sort'] ?? 'votes'));

        $query = Idea::query()
            ->with(['user', 'category', 'lastComment.user', 'statusDefinition'])
            ->withCount('comments')
            ->withMax('comments', 'created_at')
            ->withCount([
                'votes as upvotes_count' => static function ($q): void {
                    $q->where('value', 1);
                },
                'votes as downvotes_count' => static function ($q): void {
                    $q->where('value', -1);
                },
            ]);
        if ($category !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }
        if ($status !== '' && in_array($status, IdeaStatusDefinition::allSlugs(), true)) {
            $query->where('status', $status);
        }

        if ($sort === 'newest') {
            $query->orderByDesc('created_at');
        } elseif ($sort === 'oldest') {
            $query->orderBy('created_at');
        } elseif ($sort === 'status') {
            $query->orderBy('status')->orderByDesc('created_at');
        } elseif ($sort === 'category') {
            $query->leftJoin('idea_categories', 'ideas.category_id', '=', 'idea_categories.id')
                ->select('ideas.*')
                ->orderBy('idea_categories.sort_order')
                ->orderBy('idea_categories.name')
                ->orderByDesc('ideas.created_at');
        } else {
            $query->orderByDesc('is_pinned')->orderByDesc('vote_count')->orderByDesc('created_at');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $total = (clone $query)->count();
        $ideas = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $user = $this->app->auth()->user();
        $userVotes = [];
        if ($user) {
            $userVotes = IdeaVote::query()->where('user_id', $user->id)->pluck('value', 'idea_id')->toArray();
        }

        return $this->layout('idelist/index', [
            'pageTitle' => lang('idelist.page_title'),
            'ideas' => $ideas,
            'categories' => IdeaCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => IdeaStatusDefinition::query()->orderBy('sort_order')->orderBy('name')->get(),
            'currentFilters' => ['category' => $category, 'status' => $status, 'sort' => $sort],
            'userVotes' => $userVotes,
            'showVoteCounts' => IdelistSetting::getValue('show_vote_counts', '1') === '1',
            'allowDownvotes' => IdelistSetting::getValue('allow_downvotes', '1') === '1',
            'pagination' => ['page' => $page, 'last' => max(1, (int) ceil($total / $perPage)), 'total' => $total],
        ], true);
    }

    public function show(string $slug): string
    {
        $idea = Idea::query()->with(['user', 'category', 'comments.user', 'statusDefinition'])->where('slug', $slug)->firstOrFail();
        $idea->increment('views_count');
        $user = $this->app->auth()->user();
        $userVotes = [];
        if ($user) {
            $userVotes = IdeaVote::query()->where('user_id', $user->id)->pluck('value', 'idea_id')->toArray();
        }

        $authorId = (int) $idea->user_id;
        $authorStats = [
            'ideas_total' => Idea::query()->where('user_id', $authorId)->count(),
            'ideas_completed' => Idea::query()->where('user_id', $authorId)->whereHas('statusDefinition', static function ($q): void {
                $q->where('requires_completion', true);
            })->count(),
            'votes_cast' => IdeaVote::query()->where('user_id', $authorId)->count(),
            'votes_up' => IdeaVote::query()->where('user_id', $authorId)->where('value', 1)->count(),
            'votes_down' => IdeaVote::query()->where('user_id', $authorId)->where('value', -1)->count(),
            'comments_count' => \App\Modules\Idelist\Models\IdeaComment::query()->where('user_id', $authorId)->count(),
            'votes_received' => (int) Idea::query()->where('user_id', $authorId)->sum('vote_count'),
        ];
        $authorIdeas = Idea::query()
            ->where('user_id', $authorId)
            ->where('id', '!=', $idea->id)
            ->orderByDesc('vote_count')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'status', 'vote_count', 'created_at']);

        return $this->layout('idelist/show', [
            'pageTitle' => $idea->title,
            'idea' => $idea,
            'userVotes' => $userVotes,
            'allowDownvotes' => IdelistSetting::getValue('allow_downvotes', '1') === '1',
            'authorStats' => $authorStats,
            'authorIdeas' => $authorIdeas,
        ], true);
    }

    public function userIdeas(string $username): string
    {
        $username = trim($username);
        $author = \App\Models\User::query()->where('username', $username)->firstOrFail();

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $query = Idea::query()
            ->with(['category', 'statusDefinition'])
            ->where('user_id', (int) $author->id)
            ->orderByDesc('created_at');

        $total = (clone $query)->count();
        $ideas = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return $this->layout('idelist/user-ideas', [
            'pageTitle' => $author->username . ' - Ideas',
            'author' => $author,
            'ideas' => $ideas,
            'pagination' => ['page' => $page, 'last' => max(1, (int) ceil($total / $perPage)), 'total' => $total],
        ], true);
    }

    public function create(): string
    {
        if (!$this->app->auth()->user()) {
            $this->redirect(core_url('login'));
        }
        return $this->layout('idelist/create', ['categories' => IdeaCategory::query()->orderBy('sort_order')->get()], true);
    }

    public function store(): void
    {
        if (!$this->app->auth()->user()) {
            $this->redirect(core_url('login'));
        }
        if (!core_csrf_valid('idelist_store', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('idelist/create'));
        }

        $req = new StoreIdeaRequest();
        if (!$req->validate()) {
            $this->app->session()->getFlashBag()->add('error', $req->firstError() ?? lang('common.invalid_csrf'));
            $this->redirect(core_url('idelist/create'));
        }

        $data = $req->validated();
        $base = Str::slug((string) $data['title']) ?: 'idea';
        $slug = $base;
        $i = 2;
        while (Idea::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $needsApproval = IdelistSetting::getValue('require_approval', '0') === '1';
        $status = IdeaStatusDefinition::defaultSlugForNewIdea($needsApproval);
        $idea = Idea::query()->create([
            'user_id' => $this->app->auth()->user()->id,
            'category_id' => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'title' => (string) $data['title'],
            'slug' => $slug,
            'description' => (string) $data['description'],
            'status' => $status,
        ]);

        $this->app->session()->getFlashBag()->add('success', $needsApproval ? lang('idelist.idea_pending') : lang('idelist.idea_submitted'));
        $this->redirect(core_url('idelist/' . $idea->slug));
    }

    public function edit(string $slug): string
    {
        $idea = Idea::query()->where('slug', $slug)->firstOrFail();
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
        }
        if ((int) $idea->user_id !== (int) $user->id && (int) ($user->role_id ?? 0) !== 1) {
            http_response_code(403);
            exit('403');
        }
        return $this->layout('idelist/create', ['idea' => $idea, 'categories' => IdeaCategory::query()->orderBy('sort_order')->get()], true);
    }

    public function update(string $slug): void
    {
        if (!core_csrf_valid('idelist_store', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('idelist/' . $slug . '/edit'));
        }
        $idea = Idea::query()->where('slug', $slug)->firstOrFail();
        $user = $this->app->auth()->user();
        if (!$user || ((int) $idea->user_id !== (int) $user->id && (int) ($user->role_id ?? 0) > 2)) {
            http_response_code(403);
            exit('403');
        }
        $req = new StoreIdeaRequest();
        if (!$req->validate()) {
            $this->app->session()->getFlashBag()->add('error', $req->firstError() ?? 'Validation failed');
            $this->redirect(core_url('idelist/' . $idea->slug . '/edit'));
        }
        $data = $req->validated();
        $idea->title = (string) $data['title'];
        $idea->description = (string) $data['description'];
        $idea->category_id = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        $idea->save();
        $this->redirect(core_url('idelist/' . $idea->slug));
    }

    public function destroy(string $slug): void
    {
        if (!core_csrf_valid('idelist_delete', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('idelist/' . $slug));
        }
        $idea = Idea::query()->where('slug', $slug)->firstOrFail();
        $user = $this->app->auth()->user();
        if (!$user || ((int) $idea->user_id !== (int) $user->id && (int) ($user->role_id ?? 0) > 2)) {
            http_response_code(403);
            exit('403');
        }
        $idea->delete();
        $this->redirect(core_url('idelist'));
    }
}
