<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\Topic;

/**
 * Admin çöp kutusu: soft-delete edilmiş konu ve mesajları yönetir.
 * Topic and Post models use SoftDeletes trait; restore/forceDelete via Eloquent.
 */
class AdminTrashController extends AdminController
{
    public function index(): string
    {
        $user = $this->app->auth()->user();
        if (!$user || !in_array((int) $user->role_id, [1, 2], true)) {
            $this->redirect(core_url(''));
            return '';
        }

        $deletedTopics = [];
        $deletedPosts = [];

        try {
            $deletedTopics = Topic::onlyTrashed()
                ->with('deletedByUser:id,username')
                ->orderByDesc('deleted_at')
                ->limit(100)
                ->get()
                ->all();
        } catch (\Throwable $e) {
        }

        try {
            $deletedPosts = Post::onlyTrashed()
                ->with(['topic:id,title', 'deletedByUser:id,username'])
                ->orderByDesc('deleted_at')
                ->limit(100)
                ->get()
                ->all();
        } catch (\Throwable $e) {
        }

        return $this->view('trash', [
            'topics' => $deletedTopics,
            'posts' => $deletedPosts,
            'pageTitle' => lang('admin.trash.title'),
        ]);
    }

    public function restore(string $type, string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user || !in_array((int) $user->role_id, [1, 2], true)) {
            $this->redirect(core_url(''));
            return '';
        }

        if (!core_csrf_valid('trash_action', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/trash'));
            return '';
        }

        $idInt = (int) $id;

        if ($type === 'topic') {
            $topic = Topic::onlyTrashed()->find($idInt);
            if ($topic) {
                $topic->restore();
                $topic->update(['deleted_by' => null]);
            }
        } elseif ($type === 'post') {
            $post = Post::onlyTrashed()->find($idInt);
            if ($post) {
                $post->restore();
                $post->update(['deleted_by' => null]);
            }
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/trash'));
        return '';
    }

    public function purge(string $type, string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user || (int) $user->role_id !== 1) {
            $this->redirect(core_url(''));
            return '';
        }

        if (!core_csrf_valid('trash_action', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/trash'));
            return '';
        }

        $idInt = (int) $id;

        if ($type === 'topic') {
            $topic = Topic::withTrashed()->find($idInt);
            if ($topic) {
                Post::where('topic_id', $idInt)->withTrashed()->forceDelete();
                $topic->forceDelete();
            }
        } elseif ($type === 'post') {
            $post = Post::withTrashed()->find($idInt);
            if ($post) {
                $post->forceDelete();
            }
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/trash'));
        return '';
    }

    /**
     * Çöpteki tüm soft-delete edilmiş konu ve mesajları kalıcı siler (sadece süper admin).
     */
    public function emptyTrash(): string
    {
        $user = $this->app->auth()->user();
        if (!$user || (int) $user->role_id !== 1) {
            $this->redirect(core_url(''));
            return '';
        }

        if (!core_csrf_valid('trash_empty', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/trash'));
            return '';
        }

        try {
            $topicIds = Topic::onlyTrashed()->pluck('id')->all();
            foreach ($topicIds as $topicId) {
                Post::where('topic_id', $topicId)->withTrashed()->forceDelete();
                Topic::withTrashed()->where('id', $topicId)->forceDelete();
            }
            Post::onlyTrashed()->forceDelete();
        } catch (\Throwable $e) {
            // Log and continue or redirect with error
        }

        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/trash'));
        return '';
    }
}
