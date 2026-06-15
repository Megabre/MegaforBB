<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers;

use App\Controllers\BaseController;
use App\Modules\Idelist\Models\Idea;
use App\Modules\Idelist\Models\IdeaComment;

class CommentController extends BaseController
{
    public function store(string $ideaId): void
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
        }
        if (!core_csrf_valid('idelist_comment', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('idelist'));
        }
        $idea = Idea::query()->findOrFail((int) $ideaId);
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body !== '') {
            IdeaComment::query()->create([
                'idea_id' => $idea->id,
                'user_id' => $user->id,
                'body' => $body,
                'is_admin_note' => isset($_POST['is_admin_note']) && (int) ($user->role_id ?? 0) <= 2,
            ]);
        }
        $this->redirect(core_url('idelist/' . $idea->slug));
    }

    public function destroy(string $commentId): void
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
        }
        if (!core_csrf_valid('idelist_comment_delete', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('idelist'));
        }
        $comment = IdeaComment::query()->findOrFail((int) $commentId);
        if ((int) $comment->user_id !== (int) $user->id && (int) ($user->role_id ?? 0) > 2) {
            http_response_code(403);
            exit('403');
        }
        $slug = $comment->idea?->slug ?? '';
        $comment->delete();
        $this->redirect(core_url('idelist/' . $slug));
    }

    public function update(string $commentId): void
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
        }
        if (!core_csrf_valid('idelist_comment_edit', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url('idelist'));
        }

        $comment = IdeaComment::query()->findOrFail((int) $commentId);
        if ((int) $comment->user_id !== (int) $user->id && (int) ($user->role_id ?? 0) > 2) {
            http_response_code(403);
            exit('403');
        }

        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body !== '') {
            $comment->body = $body;
            $comment->save();
        }

        $slug = $comment->idea?->slug ?? '';
        $this->redirect(core_url('idelist/' . $slug));
    }
}
