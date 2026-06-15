<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementDismissal;
use App\Models\User;
use App\Services\Alerts\UserAlertService;

/**
 * Admin: Announcement management. Header badge, forum section, or notification.
 */
class AdminAnnouncementsController extends AdminController
{
    private const BADGE_KEYS = ['info', 'success', 'warning', 'danger', 'primary', 'secondary'];
    private const DISPLAY_KEYS = ['header', 'forum_section', 'both'];

    private function getBadgeTypes(): array
    {
        $out = [];
        foreach (self::BADGE_KEYS as $k) {
            $out[$k] = lang('admin.announcements.badge_' . $k);
        }
        return $out;
    }

    private function getDisplayLocations(): array
    {
        return [
            'header' => lang('admin.announcements.display_header'),
            'forum_section' => lang('admin.announcements.display_forum_section'),
            'both' => lang('admin.announcements.display_both'),
        ];
    }

    public function index(): string
    {
        $announcements = Announcement::orderBy('sort_order')->orderByDesc('id')->get([
            'id', 'title', 'badge_type', 'display_location', 'send_as_notification', 'is_dismissible', 'is_active',
            'show_from', 'show_until', 'sort_order', 'created_at',
        ])->all();
        return $this->view('announcements/index', [
            'pageTitle' => lang('admin.announcements.title'),
            'announcements' => $announcements,
            'badgeTypes' => $this->getBadgeTypes(),
            'displayLocations' => $this->getDisplayLocations(),
        ]);
    }

    public function create(): string
    {
        return $this->view('announcements/form', [
            'pageTitle' => lang('admin.announcements.add_title'),
            'announcement' => null,
            'badgeTypes' => $this->getBadgeTypes(),
            'displayLocations' => $this->getDisplayLocations(),
        ]);
    }

    public function store(): void
    {
        if (!core_csrf_valid('admin_announcements_store', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
            return;
        }
        $title = trim((string) ($_POST['title'] ?? ''));
        $body = (string) ($_POST['body'] ?? '');
        $badge_type = (string) ($_POST['badge_type'] ?? 'info');
        if (!in_array($badge_type, self::BADGE_KEYS, true)) {
            $badge_type = 'info';
        }
        $display_location = (string) ($_POST['display_location'] ?? 'both');
        if (!in_array($display_location, self::DISPLAY_KEYS, true)) {
            $display_location = 'both';
        }
        $send_as_notification = isset($_POST['send_as_notification']) && $_POST['send_as_notification'] === '1' ? 1 : 0;
        $is_dismissible = isset($_POST['is_dismissible']) && $_POST['is_dismissible'] === '1' ? 1 : 0;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;
        $show_from = $this->parseDateTime($_POST['show_from'] ?? '');
        $show_until = $this->parseDateTime($_POST['show_until'] ?? '');
        $sort_order = (int) ($_POST['sort_order'] ?? 0);

        $announcement = Announcement::create([
            'title' => $title,
            'body' => $body,
            'badge_type' => $badge_type,
            'display_location' => $display_location,
            'send_as_notification' => $send_as_notification,
            'is_dismissible' => $is_dismissible,
            'is_active' => $is_active,
            'show_from' => $show_from,
            'show_until' => $show_until,
            'sort_order' => $sort_order,
        ]);
        $announcementId = $announcement->id;

        if ($send_as_notification && $announcementId > 0) {
            $this->broadcastAnnouncementNotification((int) $announcementId, $title, $body);
        }

        Announcement::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
    }

    public function edit(int $id): string
    {
        $announcement = Announcement::find($id);
        if (!$announcement) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
            return '';
        }
        return $this->view('announcements/form', [
            'pageTitle' => lang('admin.announcements.edit_title'),
            'announcement' => $announcement,
            'badgeTypes' => $this->getBadgeTypes(),
            'displayLocations' => $this->getDisplayLocations(),
        ]);
    }

    public function update(int $id): void
    {
        if (!core_csrf_valid('admin_announcements_update', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
            return;
        }
        $announcement = Announcement::find($id);
        if (!$announcement) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
            return;
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $body = (string) ($_POST['body'] ?? '');
        $badge_type = (string) ($_POST['badge_type'] ?? 'info');
        if (!in_array($badge_type, self::BADGE_KEYS, true)) {
            $badge_type = 'info';
        }
        $display_location = (string) ($_POST['display_location'] ?? 'both');
        if (!in_array($display_location, self::DISPLAY_KEYS, true)) {
            $display_location = 'both';
        }
        $send_as_notification = isset($_POST['send_as_notification']) && $_POST['send_as_notification'] === '1' ? 1 : 0;
        $is_dismissible = isset($_POST['is_dismissible']) && $_POST['is_dismissible'] === '1' ? 1 : 0;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;
        $show_from = $this->parseDateTime($_POST['show_from'] ?? '');
        $show_until = $this->parseDateTime($_POST['show_until'] ?? '');
        $sort_order = (int) ($_POST['sort_order'] ?? 0);

        $announcement->update([
            'title' => $title,
            'body' => $body,
            'badge_type' => $badge_type,
            'display_location' => $display_location,
            'send_as_notification' => $send_as_notification,
            'is_dismissible' => $is_dismissible,
            'is_active' => $is_active,
            'show_from' => $show_from,
            'show_until' => $show_until,
            'sort_order' => $sort_order,
        ]);

        Announcement::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
    }

    public function delete(int $id): void
    {
        if (!core_csrf_valid('admin_announcements_delete', (string) ($_POST['_token'] ?? ''))) {
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
            return;
        }
        AnnouncementDismissal::where('announcement_id', $id)->delete();
        Announcement::destroy($id);
        Announcement::clearCache();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/announcements'));
    }

    private function parseDateTime(?string $val): ?string
    {
        $val = trim((string) ($val ?? ''));
        if ($val === '') {
            return null;
        }
        $ts = strtotime($val);
        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function broadcastAnnouncementNotification(int $announcementId, string $title, string $body): void
    {
        $userIds = User::where('is_banned', 0)->pluck('id');
        $payload = [
            'from_user_id' => 0,
            'from_username' => '',
            'url' => core_url(''),
            'label' => $title,
            'announcement_id' => $announcementId,
        ];
        $alerts = new UserAlertService();
        foreach ($userIds as $userId) {
            $alerts->insert((int) $userId, 'announcement', $payload, true);
        }
    }
}
