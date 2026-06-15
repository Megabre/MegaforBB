<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Models\UserReputation;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Admin: Reputation listesi, düzenleme, silme ve reputation_enabled ayarı.
 */
class AdminReputationController extends AdminController
{
    public function index(): string
    {
        $reputationEnabled = $this->getSetting('reputation_enabled', '1') === '1';
        $list = [];
        try {
            $list = UserReputation::with(['fromUser', 'toUser'])
                ->orderByDesc('created_at')
                ->limit(200)
                ->get()
                ->map(fn ($r) => (object) [
                    'id' => $r->id,
                    'value' => $r->value,
                    'comment' => $r->comment,
                    'created_at' => $r->created_at,
                    'post_id' => $r->post_id,
                    'from_username' => $r->fromUser ? $r->fromUser->username : null,
                    'to_username' => $r->toUser ? $r->toUser->username : null,
                ])
                ->all();
        } catch (\Throwable $e) {
        }
        return $this->view('reputations/index', [
            'pageTitle' => lang('admin.reputation.title'),
            'list' => $list,
            'reputationEnabled' => $reputationEnabled,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function edit(string $id): string
    {
        $repModel = UserReputation::with(['fromUser', 'toUser'])->find($id);
        if (!$repModel) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/reputations'));
            return '';
        }
        $rep = (object) [
            'id' => $repModel->id,
            'value' => $repModel->value,
            'comment' => $repModel->comment,
            'created_at' => $repModel->created_at,
            'from_username' => $repModel->fromUser ? $repModel->fromUser->username : null,
            'to_username' => $repModel->toUser ? $repModel->toUser->username : null,
        ];
        return $this->view('reputations/edit', [
            'pageTitle' => lang('admin.reputation.edit_title'),
            'rep' => $rep,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function update(string $id): void
    {
        if (!core_csrf_valid('admin_rep_edit', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/reputations'));
            return;
        }
        $comment = trim((string)($_POST['comment'] ?? ''));
        UserReputation::where('id', $id)->update(['comment' => $comment ?: null]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/reputations'));
    }

    public function delete(string $id): void
    {
        if (!core_csrf_valid('admin_rep_delete', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/reputations'));
            return;
        }
        $row = UserReputation::find($id);
        if ($row) {
            $toUserId = $row->to_user_id;
            $value = (int) $row->value;
            $row->delete();
            if ($value === 1) {
                User::where('id', $toUserId)->update([
                    'reputation_positive' => DB::raw('GREATEST(0, COALESCE(reputation_positive,0) - 1)'),
                ]);
            } else {
                User::where('id', $toUserId)->update([
                    'reputation_negative' => DB::raw('GREATEST(0, COALESCE(reputation_negative,0) - 1)'),
                ]);
            }
        }
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/reputations'));
    }

    public function toggleSetting(): void
    {
        if (!core_csrf_valid('admin_rep_setting', (string)($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/reputations'));
            return;
        }
        $enabled = isset($_POST['enabled']) && $_POST['enabled'] === '1' ? '1' : '0';
        \App\Models\Setting::setValue('reputation_enabled', $enabled, 'forum');
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/reputations'));
    }
}
