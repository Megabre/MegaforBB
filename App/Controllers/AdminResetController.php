<?php

declare(strict_types=1);

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;

class AdminResetController extends AdminController
{
    /**
     * Content tables that should be emptied during a full system reset.
     * Settings, permissions and system configuration are intentionally preserved.
     *
     * @var list<string>
     */
    private const RESET_TABLES = [
        'announcement_dismissals',
        'announcements',
        'attachments',
        'categories',
        'contact_message_replies',
        'contact_messages',
        'conversation_user',
        'conversations',
        'doc_pages',
        'doc_sections',
        'forums',
        'import_errors',
        'import_id_map',
        'import_progress',
        'invitations',
        'notifications',
        'pages',
        'password_resets',
        'poll_votes',
        'poll_options',
        'polls',
        'posts',
        'post_edits',
        'post_likes',
        'post_reports',
        'post_votes',
        'prefixes',
        'private_messages',
        'profile_comments',
        'tags',
        'topics',
        'topic_bumps',
        'topic_private_viewers',
        'topic_reads',
        'topic_subscriptions',
        'topic_tags',
        'topic_prefixes',
        'user_activities',
        'user_bans',
        'user_blocks',
        'user_custom_fields',
        'user_follows',
        'user_preferences',
        'user_reputations',
        'user_warnings',
    ];

    public function index(): string
    {
        return $this->view('reset/index', [
            'pageTitle' => lang('admin.reset.title'),
        ]);
    }

    public function execute(): void
    {
        $adminPath = env('ADMIN_PATH', 'admin');
        $flash = $this->app->session()->getFlashBag();

        if (!core_csrf_valid('admin_reset_system', (string) ($_POST['_token'] ?? ''))) {
            $flash->add('error', lang('admin.reset.invalid_csrf'));
            $this->redirect(core_url($adminPath . '/reset'));
            return;
        }

        $confirmText = trim((string) ($_POST['confirm_text'] ?? ''));
        if ($confirmText !== 'SIFIRLA') {
            $flash->add('error', lang('admin.reset.confirm_text_invalid'));
            $this->redirect(core_url($adminPath . '/reset'));
            return;
        }

        $adminUser = $this->app->auth()->user();
        $adminPassword = (string) ($_POST['admin_password'] ?? '');

        if (!$adminUser || !password_verify($adminPassword, $adminUser->password_hash)) {
            $flash->add('error', lang('admin.reset.admin_password_invalid'));
            $this->redirect(core_url($adminPath . '/reset'));
            return;
        }

        DB::beginTransaction();
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach (self::RESET_TABLES as $table) {
                DB::statement('TRUNCATE TABLE `' . $table . '`');
            }

            DB::table('users')->where('id', '!=', $adminUser->id)->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $flash->add('error', lang('admin.reset.failed'));
            $this->redirect(core_url($adminPath . '/reset'));
            return;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        DB::table('forum_stats')->where('id', 1)->update([
            'total_topics' => 0,
            'total_posts' => 0,
            'total_members' => 1,
            'last_member_id' => $adminUser->id,
            'last_member_username' => $adminUser->username,
        ]);

        $this->app->cache()->clear();

        $flash->add('success', lang('admin.reset.success'));
        $this->redirect(core_url($adminPath . '/reset'));
    }
}
