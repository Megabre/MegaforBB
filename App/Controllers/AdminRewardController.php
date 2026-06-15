<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\RewardLevel;

/**
 * Admin: Ödül seviyeleri CRUD (mesaj, rep, beğeni eşiği + rozet).
 */
class AdminRewardController extends AdminController
{
    public function index(): string
    {
        $list = [];
        try {
            $list = RewardLevel::orderBy('sort_order')->orderBy('id')
                ->get(['id', 'name', 'min_posts', 'min_reputation', 'min_likes', 'badge_label', 'badge_icon', 'badge_css', 'sort_order'])
                ->all();
        } catch (\Throwable $e) {
        }
        return $this->view('rewards/index', [
            'pageTitle' => lang('admin.rewards.title'),
            'list' => $list,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function create(): string
    {
        return $this->view('rewards/form', [
            'pageTitle' => lang('admin.rewards.add_title'),
            'level' => null,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function store(): void
    {
        if (!core_csrf_valid('admin_reward', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rewards'));
            return;
        }
        RewardLevel::create([
            'name' => trim((string)($_POST['name'] ?? '')),
            'min_posts' => (int)($_POST['min_posts'] ?? 0),
            'min_reputation' => (int)($_POST['min_reputation'] ?? 0),
            'min_likes' => (int)($_POST['min_likes'] ?? 0),
            'badge_label' => trim((string)($_POST['badge_label'] ?? '')),
            'badge_icon' => trim((string)($_POST['badge_icon'] ?? '')) ?: null,
            'badge_css' => trim((string)($_POST['badge_css'] ?? '')) ?: null,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rewards'));
    }

    public function edit(string $id): string
    {
        $level = RewardLevel::find($id, ['id', 'name', 'min_posts', 'min_reputation', 'min_likes', 'badge_label', 'badge_icon', 'badge_css', 'sort_order']);
        if (!$level) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rewards'));
            return '';
        }
        return $this->view('rewards/form', [
            'pageTitle' => lang('admin.rewards.edit_title'),
            'level' => $level,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function update(string $id): void
    {
        if (!core_csrf_valid('admin_reward', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rewards'));
            return;
        }
        RewardLevel::where('id', $id)->update([
            'name' => trim((string)($_POST['name'] ?? '')),
            'min_posts' => (int)($_POST['min_posts'] ?? 0),
            'min_reputation' => (int)($_POST['min_reputation'] ?? 0),
            'min_likes' => (int)($_POST['min_likes'] ?? 0),
            'badge_label' => trim((string)($_POST['badge_label'] ?? '')),
            'badge_icon' => trim((string)($_POST['badge_icon'] ?? '')) ?: null,
            'badge_css' => trim((string)($_POST['badge_css'] ?? '')) ?: null,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rewards'));
    }

    public function delete(string $id): void
    {
        if (!core_csrf_valid('admin_reward_delete', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rewards'));
            return;
        }
        RewardLevel::where('id', $id)->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/rewards'));
    }
}
