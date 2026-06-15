<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PostReport;
use App\Models\User;

/**
 * Moderasyon: raporlanan mesajlar. Sadece admin (role_id=1) veya moderatör (role_id=2) erişebilir.
 */
class ModerationController extends BaseController
{
    private function requireStaff(): ?object
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return null;
        }
        $rid = (int)($user->role_id ?? 0);
        if ($rid !== 1 && $rid !== 2) {
            $this->app->session()->getFlashBag()->add('mod_error', lang('mod.no_access'));
            $this->redirect(core_url(''));
            return null;
        }
        return $user;
    }

    /**
     * Rapor listesi: bekleyen ve incelenen raporlar.
     */
    public function reports(): string
    {
        if ($this->requireStaff() === null) {
            return '';
        }

        $reports = PostReport::query()
            ->with(['post.topic', 'post.user', 'reporter'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($r) {
                $p = $r->post;
                $t = $p ? $p->topic : null;
                return (object) [
                    'id' => $r->id,
                    'post_id' => $r->post_id,
                    'reason' => $r->reason,
                    'status' => $r->status,
                    'created_at' => $r->created_at,
                    'reviewed_at' => $r->reviewed_at,
                    'topic_id' => $t ? $t->id : null,
                    'body_html' => $p ? $p->body_html : null,
                    'post_author_id' => $p ? $p->user_id : null,
                    'topic_title' => $t ? $t->title : null,
                    'topic_slug' => $t ? $t->slug : null,
                    'reporter_username' => $r->reporter ? $r->reporter->username : null,
                    'post_author_username' => $p && $p->user ? $p->user->username : null,
                ];
            })
            ->all();
        $stats = $this->getStats();
        $currentUser = $this->app->auth()->user();
        $canEditPost = $currentUser && in_array((int) ($currentUser->role_id ?? 0), [1, 2], true);

        return $this->layout('moderation/reports', [
            'reports' => $reports,
            'stats' => $stats,
            'pageTitle' => lang('mod.page_title_reports'),
            'canEditPost' => $canEditPost,
        ], false);
    }

    /**
     * Raporu "incelendi" olarak işaretle.
     */
    public function markReviewed(string $id): string
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return '';
        }
        if (!core_csrf_valid('mod_report', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('mod_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('moderation/reports'));
            return '';
        }

        PostReport::where('id', (int) $id)->update(['status' => PostReport::STATUS_REVIEWED, 'reviewed_at' => \now(), 'reviewed_by' => $user->id]);
        $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.report_reviewed'));
        $this->redirect(core_url('moderation/reports'));
        return '';
    }

    /**
     * Onay listesi: approved_at boş olan üyeler. Sadece "Kayıt için yönetici onayı gerekli" açıkken kayıt olan ve henüz admin tarafından onaylanmamış kullanıcılar.
     */
    public function approvals(): string
    {
        if ($this->requireStaff() === null) {
            return '';
        }
        $list = [];
        try {
            $list = User::whereNull('approved_at')->where('role_id', 3)->where('is_banned', 0)->orderBy('created_at')->get(['id', 'username', 'email', 'created_at'])->all();
        } catch (\Throwable $e) {
        }
        $stats = $this->getStats();
        return $this->layout('moderation/approvals', [
            'list' => $list,
            'stats' => $stats,
            'pageTitle' => lang('mod.page_title_approvals'),
        ], false);
    }

    /**
     * Kullanıcıyı onayla (approved_at = NOW()).
     */
    public function approveUser(string $id): string
    {
        if ($this->requireStaff() === null) {
            return '';
        }
        if (!core_csrf_valid('mod_approve', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('mod_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('moderation/approvals'));
            return '';
        }
        try {
            User::where('id', $id)->whereNull('approved_at')->update(['approved_at' => \now()]);
        } catch (\Throwable $e) {
        }
        $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.user_approved'));
        $this->redirect(core_url('moderation/approvals'));
        return '';
    }

    /**
     * Raporu "reddedildi" olarak işaretle (şikayet kabul edilmedi).
     */
    public function dismiss(string $id): string
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return '';
        }
        if (!core_csrf_valid('mod_report', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('mod_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('moderation/reports'));
            return '';
        }

        PostReport::where('id', (int) $id)->update(['status' => PostReport::STATUS_DISMISSED, 'reviewed_at' => \now(), 'reviewed_by' => $user->id]);
        $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.report_rejected'));
        $this->redirect(core_url('moderation/reports'));
        return '';
    }

    public function reportsBulkReviewed(): string
    {
        return $this->runReportsBulk(PostReport::STATUS_REVIEWED, 'mod.bulk_reports_reviewed');
    }

    public function reportsBulkDismiss(): string
    {
        return $this->runReportsBulk(PostReport::STATUS_DISMISSED, 'mod.bulk_reports_dismissed');
    }

    public function approvalsBulkApprove(): string
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return '';
        }
        if (!core_csrf_valid('mod_approve', (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('mod_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('moderation/approvals'));
            return '';
        }
        $applyAll = isset($_POST['apply_all']) && (string) $_POST['apply_all'] === '1';
        $ids = $this->normalizeIdList($_POST['user_ids'] ?? []);
        $currentId = (int) $user->id;
        $now = \now();

        if ($applyAll) {
            $count = (int) User::whereNull('approved_at')->where('role_id', 3)->where('is_banned', 0)->update(['approved_at' => $now]);
        } elseif ($ids !== []) {
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0 && $id !== $currentId));
            $count = (int) User::whereIn('id', $ids)->whereNull('approved_at')->where('role_id', 3)->where('is_banned', 0)->update(['approved_at' => $now]);
        } else {
            $this->app->session()->getFlashBag()->add('mod_error', lang('mod.bulk_nothing_selected_users'));
            $this->redirect(core_url('moderation/approvals'));
            return '';
        }
        if ($count > 0) {
            $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.bulk_users_approved', ['count' => $count]));
        } else {
            $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.bulk_no_changes'));
        }
        $this->redirect(core_url('moderation/approvals'));
        return '';
    }

    public function approvalsBulkReject(): string
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return '';
        }
        if (!core_csrf_valid('mod_approve', (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('mod_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('moderation/approvals'));
            return '';
        }
        $applyAll = isset($_POST['apply_all']) && (string) $_POST['apply_all'] === '1';
        $ids = $this->normalizeIdList($_POST['user_ids'] ?? []);
        $currentId = (int) $user->id;

        if ($applyAll) {
            $count = (int) User::whereNull('approved_at')->where('role_id', 3)->where('is_banned', 0)->update(['is_banned' => 1]);
        } elseif ($ids !== []) {
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0 && $id !== $currentId));
            $count = (int) User::whereIn('id', $ids)->whereNull('approved_at')->where('role_id', 3)->where('is_banned', 0)->update(['is_banned' => 1]);
        } else {
            $this->app->session()->getFlashBag()->add('mod_error', lang('mod.bulk_nothing_selected_users'));
            $this->redirect(core_url('moderation/approvals'));
            return '';
        }
        if ($count > 0) {
            $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.bulk_users_rejected', ['count' => $count]));
        } else {
            $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.bulk_no_changes'));
        }
        $this->redirect(core_url('moderation/approvals'));
        return '';
    }

    private function runReportsBulk(string $status, string $langKey): string
    {
        $user = $this->requireStaff();
        if ($user === null) {
            return '';
        }
        if (!core_csrf_valid('mod_report', (string) ($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('mod_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('moderation/reports'));
            return '';
        }
        $applyAll = isset($_POST['apply_all']) && (string) $_POST['apply_all'] === '1';
        $ids = $this->normalizeIdList($_POST['report_ids'] ?? []);
        $now = \now();
        $reviewerId = (int) $user->id;

        if ($applyAll) {
            $count = (int) PostReport::where('status', PostReport::STATUS_PENDING)->update([
                'status' => $status,
                'reviewed_at' => $now,
                'reviewed_by' => $reviewerId,
            ]);
        } elseif ($ids !== []) {
            $count = (int) PostReport::whereIn('id', $ids)->where('status', PostReport::STATUS_PENDING)->update([
                'status' => $status,
                'reviewed_at' => $now,
                'reviewed_by' => $reviewerId,
            ]);
        } else {
            $this->app->session()->getFlashBag()->add('mod_error', lang('mod.bulk_nothing_selected'));
            $this->redirect(core_url('moderation/reports'));
            return '';
        }
        if ($count > 0) {
            $this->app->session()->getFlashBag()->add('mod_ok', lang($langKey, ['count' => $count]));
        } else {
            $this->app->session()->getFlashBag()->add('mod_ok', lang('mod.bulk_no_changes'));
        }
        $this->redirect(core_url('moderation/reports'));
        return '';
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function normalizeIdList(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $v) {
            $i = (int) $v;
            if ($i > 0) {
                $out[$i] = true;
            }
        }

        return array_keys($out);
    }
}
