<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\PostReport;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Layout için ortak veri: istatistikler, engellenen kullanıcılar, duyurular, reklamlar.
 * Tüm PDO kullanımı Eloquent/Query Builder ile değiştirildi.
 */
class LayoutDataService
{
    public function __construct(
        protected \Forecor\Core\Application $app
    ) {
    }

    /** Çevrimiçi istatistikleri: üyeler (son 15 dk), ziyaretçiler ve botlar (trafik log; son 15 dk). Üye sayısı ayrı COUNT ile; liste en fazla 50 (phpBB benzeri). */
    public function getOnlineStats(): object
    {
        $onlineMinutes = 15;
        $threshold = date('Y-m-d H:i:s', strtotime("-{$onlineMinutes} minutes"));
        $membersCount = 0;
        $members = [];
        try {
            $membersCount = (int) User::where('last_activity_at', '>=', $threshold)->count();
            $members = User::where('last_activity_at', '>=', $threshold)
                ->orderBy('username')
                ->limit(50)
                ->get(['id', 'username'])
                ->all();
        } catch (\Throwable $e) {
        }
        $guestsCount = 0;
        $botsCount = 0;
        if (class_exists(AnalyticsLogger::class) && AnalyticsLogger::isEnabled()) {
            $guestsCount = AnalyticsLogger::uniqueGuestsCount($onlineMinutes);
            $botsCount = AnalyticsLogger::uniqueBotsCount($onlineMinutes);
        }
        $totalOnline = $membersCount + $guestsCount + $botsCount;

        $recordOnlineUsers = 0;
        $recordOnlineDate = null;
        try {
            $row = DB::table('forum_stats')->where('id', 1)->first();
            if ($row !== null) {
                $recordOnlineUsers = (int) (property_exists($row, 'record_online_users') ? $row->record_online_users : 0);
                $recordOnlineDate = property_exists($row, 'record_online_date') ? $row->record_online_date : null;
                if ($totalOnline > $recordOnlineUsers && property_exists($row, 'record_online_users')) {
                    DB::table('forum_stats')->where('id', 1)->update([
                        'record_online_users' => $totalOnline,
                        'record_online_date' => now()->format('Y-m-d H:i:s'),
                    ]);
                    $recordOnlineUsers = $totalOnline;
                    $recordOnlineDate = now()->format('Y-m-d H:i:s');
                }
            }
        } catch (\Throwable $e) {
        }

        return (object) [
            'members' => $members,
            'members_count' => $membersCount,
            'guests_count' => $guestsCount,
            'bots_count' => $botsCount,
            'total_online' => $totalOnline,
            'record_online_users' => $recordOnlineUsers,
            'record_online_date' => $recordOnlineDate,
        ];
    }

    /** Çevrimiçi sayfası: toplam sayılar (COUNT) + sadece örnek listeler (sabit limit). 50k/25k çevrimiçi olsa bile sayfa aynı kalır. */
    private const ONLINE_PAGE_SAMPLE_MEMBERS = 24;
    private const ONLINE_PAGE_SAMPLE_GUESTS = 24;
    private const ONLINE_PAGE_SAMPLE_BOTS = 20;

    public function getOnlinePageData(int $onlineMinutes = 15): object
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$onlineMinutes} minutes"));
        $totalMembersCount = 0;
        $membersSample = [];
        try {
            $totalMembersCount = (int) User::where('last_activity_at', '>=', $threshold)->count();
            $membersSample = User::where('last_activity_at', '>=', $threshold)
                ->orderByDesc('last_activity_at')
                ->limit(self::ONLINE_PAGE_SAMPLE_MEMBERS)
                ->get(['id', 'username', 'avatar_path'])
                ->map(fn ($u) => (object) [
                    'id' => $u->id,
                    'username' => $u->username,
                    'avatar_path' => $u->avatar_path,
                ])
                ->all();
        } catch (\Throwable $e) {
        }
        $totalGuestsCount = 0;
        $totalBotsCount = 0;
        $guestsSample = [];
        $botsSample = [];
        if (class_exists(AnalyticsLogger::class) && AnalyticsLogger::isEnabled()) {
            $totalGuestsCount = AnalyticsLogger::uniqueGuestsCount($onlineMinutes);
            $totalBotsCount = AnalyticsLogger::uniqueBotsCount($onlineMinutes);
            $rawGuests = AnalyticsLogger::getRecentGuestsList($onlineMinutes, self::ONLINE_PAGE_SAMPLE_GUESTS);
            $rawBots = AnalyticsLogger::getRecentBotsList($onlineMinutes, self::ONLINE_PAGE_SAMPLE_BOTS);
            foreach ($rawGuests as $g) {
                $g['last_seen_formatted'] = date('H:i', (int) ($g['last_seen'] ?? 0));
                $guestsSample[] = $g;
            }
            foreach ($rawBots as $b) {
                $b['last_seen_formatted'] = date('H:i', (int) ($b['last_seen'] ?? 0));
                $botsSample[] = $b;
            }
        }
        $totalOnline = $totalMembersCount + $totalGuestsCount + $totalBotsCount;

        $recordOnlineUsers = 0;
        $recordOnlineDate = null;
        try {
            $row = DB::table('forum_stats')->where('id', 1)->first();
            $recordOnlineUsers = (int) ($row->record_online_users ?? 0);
            $recordOnlineDate = $row->record_online_date ?? null;
            if ($totalOnline > $recordOnlineUsers && isset($row->record_online_users)) {
                DB::table('forum_stats')->where('id', 1)->update([
                    'record_online_users' => $totalOnline,
                    'record_online_date' => now()->format('Y-m-d H:i:s'),
                ]);
                $recordOnlineUsers = $totalOnline;
                $recordOnlineDate = now()->format('Y-m-d H:i:s');
            }
        } catch (\Throwable $e) {
        }

        return (object) [
            'members' => $membersSample,
            'members_count' => $totalMembersCount,
            'guests_list' => $guestsSample,
            'guests_count' => $totalGuestsCount,
            'bots_list' => $botsSample,
            'bots_count' => $totalBotsCount,
            'total_online' => $totalOnline,
            'record_online_users' => $recordOnlineUsers,
            'record_online_date' => $recordOnlineDate,
            'sample_size_members' => self::ONLINE_PAGE_SAMPLE_MEMBERS,
            'sample_size_guests' => self::ONLINE_PAGE_SAMPLE_GUESTS,
            'sample_size_bots' => self::ONLINE_PAGE_SAMPLE_BOTS,
        ];
    }

    public function getStats(): object
    {
        $default = (object) [
            'total_topics' => 0,
            'total_posts' => 0,
            'total_members' => 0,
            'last_member_username' => null,
        ];
        try {
            $row = DB::table('forum_stats')->where('id', 1)->first();
            if (!$row) {
                return $this->repairAndGetStats();
            }
            $totalTopics = (int) ($row->total_topics ?? 0);
            $totalPosts = (int) ($row->total_posts ?? 0);
            $totalMembers = (int) User::where('is_banned', 0)->count();
            $lastMember = User::where('is_banned', 0)->orderByDesc('id')->value('username');
            if ($totalTopics === 0 && $totalPosts === 0 && $totalMembers > 0) {
                return $this->repairAndGetStats();
            }
            return (object) [
                'total_topics' => $totalTopics,
                'total_posts' => $totalPosts,
                'total_members' => $totalMembers,
                'last_member_username' => $lastMember,
            ];
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * forum_stats id=1: konu/mesaj ve (footer ile uyumlu) banlı olmayan üye sayısı; silinmiş konudaki mesajlar hariç.
     * Önbelleği temizler (forum_stats, home_categories).
     *
     * @return array{total_topics:int,total_posts:int,total_members:int,last_member_username:?string}
     */
    public function recalculateForumStatsTotals(): array
    {
        $totalTopics = (int) DB::table('topics')->whereNull('deleted_at')->count();
        $totalPosts = (int) DB::table('posts as p')
            ->join('topics as t', 't.id', '=', 'p.topic_id')
            ->whereNull('p.deleted_at')
            ->whereNull('t.deleted_at')
            ->count();
        $totalMembers = (int) User::where('is_banned', 0)->count();
        $lastMember = User::where('is_banned', 0)->orderByDesc('id')->first(['id', 'username']);
        DB::table('forum_stats')->updateOrInsert(
            ['id' => 1],
            [
                'total_topics' => $totalTopics,
                'total_posts' => $totalPosts,
                'total_members' => $totalMembers,
                'last_member_id' => $lastMember->id ?? null,
                'last_member_username' => $lastMember->username ?? null,
                'updated_at' => now(),
            ]
        );
        try {
            $this->app->cache()->delete('forum_stats');
        } catch (\Throwable $e) {
        }
        try {
            $this->app->cache()->delete('home_categories');
        } catch (\Throwable $e) {
        }

        return [
            'total_topics' => $totalTopics,
            'total_posts' => $totalPosts,
            'total_members' => $totalMembers,
            'last_member_username' => $lastMember->username ?? null,
        ];
    }

    /**
     * forum_stats satırı yok veya sıfır (bozuk) ise gerçek sayılardan hesaplayıp tabloyu günceller ve döner.
     * Böylece cron çalışmasa veya cache/DB sıfırlanmış olsa bile footer istatistikleri kendini toparlar.
     */
    private function repairAndGetStats(): object
    {
        try {
            $totals = $this->recalculateForumStatsTotals();

            return (object) [
                'total_topics' => $totals['total_topics'],
                'total_posts' => $totals['total_posts'],
                'total_members' => $totals['total_members'],
                'last_member_username' => $totals['last_member_username'],
            ];
        } catch (\Throwable $e) {
            return (object) [
                'total_topics' => 0,
                'total_posts' => 0,
                'total_members' => 0,
                'last_member_username' => null,
            ];
        }
    }

    /** Engelleyen + engellenen taraftaki kullanıcı id listesi. */
    public function getBlockedUserIds(int $userId): array
    {
        try {
            $mine = UserBlock::where('user_id', $userId)->pluck('blocked_user_id')->map(fn ($id) => (int) $id)->all();
            $theirs = UserBlock::where('blocked_user_id', $userId)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
            return array_values(array_unique(array_merge($mine, $theirs)));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function countUnreadNotifications(int $userId, array $blockedUserIds): int
    {
        try {
            $q = Notification::query()
                ->where('user_id', $userId)
                ->whereNull('read_at')
                ->where('type', '!=', 'message');
            if (!empty($blockedUserIds)) {
                $blocked = array_values(array_unique(array_map(static fn ($id): int => (int) $id, $blockedUserIds)));
                $blocked = array_values(array_filter($blocked, static fn (int $id): bool => $id > 0));
                if ($blocked !== []) {
                    $driver = (string) DB::connection()->getDriverName();
                    if ($driver === 'mysql' || $driver === 'mariadb') {
                        $ph = implode(',', array_fill(0, count($blocked), '?'));
                        $expr = 'COALESCE(notifications.sender_user_id, IF(JSON_VALID(notifications.`data`), CAST(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(notifications.`data`, \'$.from_user_id\')), \'\') AS UNSIGNED), NULL), 0)';
                        $q->whereRaw("({$expr} = 0 OR {$expr} NOT IN ({$ph}))", $blocked);
                    } else {
                        $q->where(function ($w) use ($blocked): void {
                            $w->whereNull('sender_user_id')->orWhereNotIn('sender_user_id', $blocked);
                        });
                    }
                }
            }
            return (int) $q->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function countUnreadConversations(int $userId, array $blockedUserIds): int
    {
        try {
            $conversationIds = DB::table('conversation_user')
                ->where('user_id', $userId)
                ->whereNull('hidden_at')
                ->pluck('conversation_id');
            if ($conversationIds->isEmpty()) {
                return 0;
            }
            $convSvc = new \App\Services\ConversationService();
            $total = 0;
            foreach ($conversationIds as $cid) {
                $otherId = (int) DB::table('conversation_user')->where('conversation_id', $cid)->where('user_id', '!=', $userId)->value('user_id');
                if (!empty($blockedUserIds) && in_array($otherId, $blockedUserIds, true)) {
                    continue;
                }
                $total += $convSvc->countUnreadVisible((int) $cid, $userId);
            }
            return $total;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function countPendingReports(): int
    {
        try {
            return (int) PostReport::where('status', PostReport::STATUS_PENDING)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Admin onayı bekleyen üye sayısı (approved_at boş). "Kayıt için yönetici onayı gerekli" açıkken kayıt olup henüz onaylanmamış kullanıcılar. */
    public function countPendingApprovals(): int
    {
        try {
            return (int) User::whereNull('approved_at')->where('role_id', 3)->where('is_banned', 0)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Aktif duyurular (header + forum_section); kullanıcı kapatmış olanları hariç tutar. */
    public function loadActiveAnnouncements(int $userId = 0): array
    {
        try {
            $all = \App\Models\Announcement::getCachedActive();
        } catch (\Throwable $e) {
            return ['header' => [], 'forum_section' => []];
        }
        $dismissed = [];
        $cookieName = 'dismissed_announcements';
        if (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] !== '') {
            $dismissed = array_map('intval', explode(',', $_COOKIE[$cookieName]));
        }

        if ($userId > 0 && !empty($all)) {
            try {
                $ids = array_map(fn ($a) => (int) $a->id, $all);
                $dbDismissed = \App\Models\AnnouncementDismissal::where('user_id', $userId)
                    ->whereIn('announcement_id', $ids)
                    ->pluck('announcement_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $dismissed = array_values(array_unique(array_merge($dismissed, $dbDismissed)));
            } catch (\Throwable $e) {
            }
        }
        $header = [];
        $forumSection = [];
        foreach ($all as $a) {
            if (in_array((int) $a->id, $dismissed, true) && !empty($a->is_dismissible)) {
                continue;
            }
            $loc = $a->display_location ?? 'both';
            if ($loc === 'header' || $loc === 'both') {
                $header[] = $a;
            }
            if ($loc === 'forum_section' || $loc === 'both') {
                $forumSection[] = $a;
            }
        }
        return ['header' => $header, 'forum_section' => $forumSection];
    }

    /** Reklamları pozisyona göre yükle (position_key => [html, ...]); önbellek 5 dk. */
    public function loadAdsByPosition(): array
    {
        try {
            return \App\Models\Ad::getCachedByPosition();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
