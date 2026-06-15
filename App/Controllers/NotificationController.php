<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ForumStats;
use App\Models\Notification;
use App\Models\Topic;

/**
 * Kullanıcı bildirimleri listesi ve okundu işaretleme.
 * Detay metni tipe göre üretilir; sekmeler kategoriye göre (Beğeniler, Etiketler, Konu-Cevap, Rep, vb.).
 */
class NotificationController extends BaseController
{
    private const KEEP_LIMIT = 30;

    /** Bildirim tipine göre kategori (sekme) anahtarı. */
    private static function categoryForType(string $type): string
    {
        $map = [
            'reaction' => 'likes',
            'mention' => 'mentions',
            'reply' => 'replies',
            'subscribed_topic_reply' => 'replies',
            'quote' => 'quotes',
            'reputation' => 'reputation',
            'announcement' => 'announcements',
            'report' => 'reports',
            'private_topic_added' => 'other',
            'pm_undeliverable' => 'other',
        ];
        return $map[$type] ?? 'other';
    }

    /** Tipe ve data'ya göre tam detay cümlesi üretir (örn: "Ali, ... konusundaki mesajınızı beğendi."). */
    private function buildDetailMessage(string $type, array $data): string
    {
        $user = $data['from_username'] ?? '';
        $topic = $data['topic_title'] ?? '';
        $topicFallback = $topic ?: lang('notification.topic_unknown');

        switch ($type) {
            case 'reaction':
                return lang('notification.detail_reaction', ['user' => $user, 'topic' => $topicFallback]);
            case 'mention':
                return lang('notification.detail_mention', ['user' => $user, 'topic' => $topicFallback]);
            case 'reply':
                return lang('notification.detail_reply', ['user' => $user, 'topic' => $topicFallback]);
            case 'subscribed_topic_reply':
                return lang('notification.detail_subscribed_topic_reply', ['user' => $user, 'topic' => $topicFallback]);
            case 'quote':
                return lang('notification.detail_quote', ['user' => $user, 'topic' => $topicFallback]);
            case 'reputation':
                $value = (int) ($data['value'] ?? 0);
                $rep = $value >= 0 ? lang('notification.rep_positive') : lang('notification.rep_negative');
                if ($topic !== '') {
                    return lang('notification.detail_reputation_with_topic', ['user' => $user, 'topic' => $topic, 'rep' => $rep]);
                }
                return lang('notification.detail_reputation', ['user' => $user, 'rep' => $rep]);
            case 'announcement':
                return $data['message'] ?? $data['label'] ?? lang('api.notification_announcement');
            case 'report':
                return $data['message'] ?? lang('api.notification_report');
            case 'private_topic_added':
                return lang('notification.detail_private_topic_added', [
                    'user' => $data['from_username'] ?? '',
                    'topic' => $topicFallback,
                ]);
            case 'pm_undeliverable':
                return (string) ($data['message'] ?? lang('api.notification_pm_undeliverable'));
            default:
                return $data['message'] ?? $data['title'] ?? $type;
        }
    }

    /** Tarihi sayfa gösterimi için biçimlendirir: "Bugün 14:48:57" veya "06-09-2025 22:09:07". */
    private function formatNotificationDate(?\DateTimeInterface $dt): string
    {
        if (!$dt) {
            return '';
        }
        $now = \now();
        $today = $now->format('Y-m-d');
        $date = $dt->format('Y-m-d');
        if ($date === $today) {
            return lang('notification.today') . ' ' . $dt->format('H:i:s');
        }
        return $dt->format('d-m-Y H:i:s');
    }

    public function index(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $list = [];
        $listForJs = [];
        try {
            $rows = Notification::where('user_id', $user->id)
                ->where('type', '!=', 'message')
                ->orderByDesc('created_at')
                ->limit(self::KEEP_LIMIT)
                ->get(['id', 'type', 'data', 'read_at', 'created_at']);
            $list = $rows->all();
            $keepIds = $rows->pluck('id')->all();
            if (!empty($keepIds)) {
                Notification::where('user_id', $user->id)
                    ->where('type', '!=', 'message')
                    ->whereNotIn('id', $keepIds)
                    ->delete();
            }
            $topicIds = [];
            foreach ($rows as $n) {
                $data = $n->data ? (json_decode($n->data, true) ?: []) : [];
                if (is_array($data) && !empty($data['topic_id']) && empty($data['topic_title'])) {
                    $topicIds[(int) $data['topic_id']] = true;
                }
            }
            $topicTitles = [];
            if (!empty($topicIds)) {
                $ids = array_keys($topicIds);
                $topicTitles = Topic::whereIn('id', $ids)->pluck('title', 'id')->all();
            }
            foreach ($rows as $n) {
                $data = $n->data ? (json_decode($n->data, true) ?: []) : [];
                if (is_array($data) && !empty($data['topic_id']) && empty($data['topic_title']) && isset($topicTitles[(int) $data['topic_id']])) {
                    $data['topic_title'] = $topicTitles[(int) $data['topic_id']];
                }
                $type = $n->type ?? '';
                $detail = $this->buildDetailMessage($type, $data);
                $listForJs[] = [
                    'id' => (string) $n->id,
                    'type' => $type,
                    'category' => self::categoryForType($type),
                    'detail' => $detail,
                    'url' => $data['url'] ?? '',
                    'read_at' => $n->read_at ? $n->read_at->format('c') : null,
                    'created_at' => $n->created_at ? $n->created_at->format('c') : '',
                    'created_at_label' => $this->formatNotificationDate($n->created_at),
                    'is_read' => !empty($n->read_at),
                ];
            }
        } catch (\Throwable $e) {
            if (function_exists('core_config') && core_config('app.debug', false)) {
                error_log('NotificationController::index: ' . $e->getMessage());
            }
        }
        $stats = (object)['total_topics' => 0, 'total_posts' => 0, 'total_members' => 0];
        try {
            $row = ForumStats::singleton();
            if ($row) {
                $stats = (object)['total_topics' => (int) $row->total_topics, 'total_posts' => (int) $row->total_posts, 'total_members' => (int) $row->total_members];
            }
        } catch (\Throwable $e) {
        }
        return $this->layout('notifications/index', [
            'list' => $list,
            'listForJs' => $listForJs,
            'stats' => $stats,
            'pageTitle' => lang('notification.page_title'),
        ], false);
    }

    public function markRead(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $notifId = resolve_notification_id($id);
        if ($notifId === null) {
            $this->redirect(core_url('notifications'));
            return '';
        }
        $row = Notification::where('id', $notifId)->where('user_id', $user->id)->first(['type', 'data']);
        Notification::where('id', $notifId)->where('user_id', $user->id)->update(['read_at' => \now()]);
        $url = core_url('notifications');
        if ($row && !empty($row->data)) {
            $data = json_decode($row->data, true);
            if (is_array($data)) {
                $url = $this->resolveNotificationTargetUrl((string) ($row->type ?? ''), $data);
            }
        }
        $this->redirect($url);
        return '';
    }

    /**
     * Bildirim tipine göre hedef URL üretir.
     * - Yorum/alıntı/yanıt/beğeni: ilgili mesaja (#post-id)
     * - Konu bildirimleri: konuya
     * - Diğerleri: payload URL veya bildirimler listesi
     */
    private function resolveNotificationTargetUrl(string $type, array $data): string
    {
        $topicId = (int) ($data['topic_id'] ?? 0);
        $postId = (int) ($data['post_id'] ?? 0);

        $topicOnlyTypes = ['private_topic_added'];
        $postPreferredTypes = ['reaction', 'reply', 'subscribed_topic_reply', 'quote', 'mention', 'report'];

        if ($topicId > 0) {
            $topicUrl = core_url('topic/' . topic_url_path_by_id($topicId));
            if (in_array($type, $postPreferredTypes, true) && $postId > 0) {
                return $topicUrl . '#post-' . $postId;
            }
            if (in_array($type, $topicOnlyTypes, true)) {
                return $topicUrl;
            }
            if ($postId > 0) {
                return $topicUrl . '#post-' . $postId;
            }
            return $topicUrl;
        }

        if (!empty($data['url']) && is_string($data['url'])) {
            return core_redirect_url_safe($data['url']);
        }

        return core_url('notifications');
    }

    /** Tüm bildirimleri okundu işaretle (AJAX POST). */
    public function markAllRead(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->json(['ok' => false, 'error' => 'unauthorized'], 401);
            return '';
        }
        if (!core_csrf_valid('notifications_read_all', (string) ($_POST['_token'] ?? ''))) {
            $this->json(['ok' => false, 'error' => 'invalid_csrf'], 403);
            return '';
        }
        Notification::where('user_id', $user->id)
            ->where('type', '!=', 'message')
            ->whereNull('read_at')
            ->update(['read_at' => \now()]);
        $this->json(['ok' => true]);
        return '';
    }
}
