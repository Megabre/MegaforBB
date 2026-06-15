<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Conversation;
use App\Models\ForumStats;
use App\Models\PrivateMessage;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\Alerts\UserAlertService;
use App\Services\PrivateMessageQuotaService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Kullanıcılar arası özel mesajlaşma (DM). Eloquent modelleri kullanır.
 */
class ConversationController extends BaseController
{
    public function index(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $blocked = $this->getBlockedUserIds((int)$user->id);
        $conversations = $this->getConversationList((int)$user->id, new ConversationService());
        $conversations = array_values(array_filter($conversations, function ($c) use ($blocked) {
            $otherId = (int)($c->other?->id ?? 0);
            return $otherId > 0 && !in_array($otherId, $blocked, true);
        }));
        $stats = $this->getConvStats();
        $user->loadMissing('role');
        $pmQuota = (new PrivateMessageQuotaService())->getPanelSummary($user);
        return $this->layout('conversations/index', [
            'conversations' => $conversations,
            'stats' => $stats,
            'pmQuota' => $pmQuota,
            'pageTitle' => lang('conv.page_title'),
        ], false);
    }

    public function show(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $convId = resolve_conversation_id($id);
        if ($convId === null) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.no_access'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        $convSvc = new ConversationService();
        $conv = Conversation::find($convId);
        if (function_exists('sef_service') && sef_service()->getMode() === 'random' && $conv && empty($conv->url_key)) {
            sef_service()->ensureConversationUrlKey($conv);
        }
        if (!$conv || !$convSvc->userInConversation($convId, (int)$user->id)) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.no_access'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        $other = $this->getOtherParticipant($convId, (int)$user->id);
        $blocked = $this->getBlockedUserIds((int)$user->id);
        if ($other && in_array((int)$other->id, $blocked, true)) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.no_access'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        $convSvc->markConversationRead($convId, (int) $user->id);
        $messages = $this->getMessages($convId, (int)$user->id);
        $stats = $this->getConvStats();
        $user->loadMissing('role');
        $pmQuota = (new PrivateMessageQuotaService())->getPanelSummary($user);
        $conversations = $this->getConversationList((int)$user->id, $convSvc);
        $conversations = array_values(array_filter($conversations, function ($c) use ($blocked) {
            $otherId = (int)($c->other?->id ?? 0);
            return $otherId > 0 && !in_array($otherId, $blocked, true);
        }));
        return $this->layout('conversations/show', [
            'conversationId' => $convId,
            'conversations' => $conversations,
            'other' => $other,
            'messages' => $messages,
            'stats' => $stats,
            'pmQuota' => $pmQuota,
            'pageTitle' => lang('conv.page_title_with', ['name' => $other->username ?? '']),
        ], false);
    }

    public function createForm(string $username = ''): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $target = null;
        if ($username !== '') {
            $target = User::where('username', $username)->where('id', '!=', $user->id)->where('is_banned', 0)->first(['id', 'username']);
            if ($target) {
                $blocked = $this->getBlockedUserIds((int)$user->id);
                if (in_array((int)$target->id, $blocked, true)) {
                    $target = null;
                    $this->app->session()->getFlashBag()->add('conv_error', lang('conv.cannot_send_block'));
                }
            }
        }
        $stats = $this->getConvStats();
        $user->loadMissing('role');
        $pmQuota = (new PrivateMessageQuotaService())->getPanelSummary($user);
        return $this->layout('conversations/new', [
            'target' => $target,
            'stats' => $stats,
            'pmQuota' => $pmQuota,
            'pageTitle' => $target ? lang('conv.message_to', ['name' => $target->username]) : lang('conv.new_message'),
        ], false);
    }

    public function store(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        try {
            $user->loadMissing('role');
            if (!core_csrf_valid('conversation_new', (string)($_POST['_token'] ?? ''))) {
                $this->app->session()->getFlashBag()->add('conv_error', core__('common.invalid_csrf'));
                $this->redirect(core_url('conversations/new'));
                return '';
            }
            $ip = \App\Services\SecurityService::clientIp();
            $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_SEND_PM, (int) $user->id, $ip);
            if (!$r['allowed']) {
                $this->app->session()->getFlashBag()->add('conv_error', $r['message']);
                $this->redirect(core_url('conversations/new'));
                return '';
            }
            $role = $user->role;
            if ($role && $role->pm_daily_limit > 0) {
                $todayStart = now()->startOfDay()->format('Y-m-d H:i:s');
                $sentToday = PrivateMessage::where('user_id', $user->id)->where('created_at', '>=', $todayStart)->count();
                if ($sentToday >= $role->pm_daily_limit) {
                    $this->app->session()->getFlashBag()->add('conv_error', lang('quota.pm_daily_exceeded'));
                    $this->redirect(core_url('conversations/new'));
                    return '';
                }
            }
            $quotaSvc = new PrivateMessageQuotaService();
            if ($reason = $quotaSvc->senderLifetimeBlockReason($user)) {
                $this->app->session()->getFlashBag()->add('conv_error', lang($reason));
                $this->redirect(core_url('conversations/new'));
                return '';
            }
            $toUsername = trim((string)($_POST['to_username'] ?? ''));
            $body = trim((string)($_POST['body'] ?? ''));
            $cleanBody = trim(strip_tags(str_replace(['&nbsp;', '&#160;', '&zwj;'], '', $body)));
            if ($toUsername === '' || $cleanBody === '') {
                $this->app->session()->getFlashBag()->add('conv_error', lang('conv.recipient_required'));
                $this->redirect(core_url('conversations/new'));
                return '';
            }
            $toUser = User::where('username', $toUsername)->where('id', '!=', $user->id)->where('is_banned', 0)->with('role')->first(['id', 'username']);
            if (!$toUser) {
                $this->app->session()->getFlashBag()->add('conv_error', lang('conv.user_not_found'));
                $this->redirect(core_url('conversations/new'));
                return '';
            }
            $toUserId = (int)$toUser->id;
            $blocked = $this->getBlockedUserIds((int)$user->id);
            if (in_array($toUserId, $blocked, true)) {
                $this->app->session()->getFlashBag()->add('conv_error', lang('conv.cannot_send_block'));
                $this->redirect(core_url('conversations/new'));
                return '';
            }
            if ($blockReason = $quotaSvc->recipientDeliveryBlockReason($toUser)) {
                $this->notifySenderPmBlocked($user, (string)$toUser->username, $blockReason);
                $this->redirect(core_url('conversations/new'));
                return '';
            }
            $existing = (new ConversationService())->findConversationBetween((int)$user->id, $toUserId);
            if ($existing) {
                $bodyHtml = core_pm_body_to_html($body);
                $convW = new ConversationService();
                $convW->unhideConversationForAllParticipants($existing);
                $convW->addMessage($existing, (int)$user->id, $body, $bodyHtml);
                $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_SEND_PM, (int) $user->id, $ip);
                $this->redirect(core_url('conversations/' . conversation_url_path_by_id($existing)));
                return '';
            }
            $svc = new ConversationService();
            $conv = DB::transaction(function () use ($user, $toUserId, $body, $svc) {
                $conv = Conversation::create(['created_at' => \now()]);
                if (function_exists('sef_service') && sef_service()->getMode() === 'random') {
                    $conv->url_key = sef_service()->generateUniqueUrlKeyForTable('conversations', 'url_key');
                    $conv->save();
                }
                $svc->attachUsersToConversation((int) $conv->id, (int) $user->id, $toUserId);
                $bodyHtml = core_pm_body_to_html($body);
                $svc->addMessage((int) $conv->id, (int) $user->id, $body, $bodyHtml);
                return $conv;
            });
            $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_SEND_PM, (int) $user->id, $ip);
            $this->redirect(core_url('conversations/' . conversation_url_path($conv)));
            return '';
        } catch (\Throwable $e) {
            $msg = $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            error_log('[ConversationController::store] ' . $msg);
            $showDetail = core_config('app.debug', false);
            $this->app->session()->getFlashBag()->add('conv_error', $showDetail ? $msg : lang('conv.send_failed'));
            $this->redirect(core_url('conversations/new'));
            return '';
        }
    }

    public function storeReply(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $convId = resolve_conversation_id($id);
        if ($convId === null) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.no_access'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        if (!core_csrf_valid('conversation_reply', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('conv_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        $user->loadMissing('role');
        $ip = \App\Services\SecurityService::clientIp();
        $r = $this->app->security()->checkAndRecordViolationOnFail(\App\Services\SecurityService::ACTION_SEND_PM, (int) $user->id, $ip);
        if (!$r['allowed']) {
            $this->app->session()->getFlashBag()->add('conv_error', $r['message']);
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        $role = $user->role;
        if ($role && $role->pm_daily_limit > 0) {
            $todayStart = now()->startOfDay()->format('Y-m-d H:i:s');
            $sentToday = PrivateMessage::where('user_id', $user->id)->where('created_at', '>=', $todayStart)->count();
            if ($sentToday >= $role->pm_daily_limit) {
                $this->app->session()->getFlashBag()->add('conv_error', lang('quota.pm_daily_exceeded'));
                $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
                return '';
            }
        }
        $quotaSvc = new PrivateMessageQuotaService();
        if ($reason = $quotaSvc->senderLifetimeBlockReason($user)) {
            $this->app->session()->getFlashBag()->add('conv_error', lang($reason));
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        $convSvc = new ConversationService();
        if (!Conversation::find($convId) || !$convSvc->userInConversation($convId, (int)$user->id)) {
            $this->redirect(core_url('conversations'));
            return '';
        }
        $body = trim((string)($_POST['body'] ?? ''));
        $cleanBody = trim(strip_tags(str_replace(['&nbsp;', '&#160;', '&zwj;'], '', $body)));
        if ($cleanBody === '') {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.recipient_required') ?? 'Mesaj içeriği boş olamaz.');
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        $other = $this->getOtherParticipant($convId, (int)$user->id);
        $blocked = $this->getBlockedUserIds((int)$user->id);
        if ($other && in_array((int)$other->id, $blocked, true)) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.cannot_send'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        $otherFull = $other ? User::with('role')->find($other->id) : null;
        if ($otherFull) {
            if ($blockReason = $quotaSvc->recipientDeliveryBlockReason($otherFull)) {
                $this->notifySenderPmBlocked($user, (string)$otherFull->username, $blockReason);
                $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
                return '';
            }
        }
        $bodyHtml = core_pm_body_to_html($body);
        $convSvc->unhideConversationForAllParticipants($convId);
        $convSvc->addMessage($convId, (int)$user->id, $body, $bodyHtml);

        $this->app->security()->recordAction(\App\Services\SecurityService::ACTION_SEND_PM, (int) $user->id, $ip);
        $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
        return '';
    }

    public function purgeInbox(): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        if (!core_csrf_valid('conversation_manage', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.invalid_csrf'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        (new PrivateMessageQuotaService())->hideAllConversationsForUser((int)$user->id);
        $this->app->session()->getFlashBag()->add('conv_success', lang('conv.inbox_purged_ok'));
        $this->redirect(core_url('conversations'));
        return '';
    }

    public function hideConversation(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $convId = resolve_conversation_id($id);
        if ($convId === null) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.no_access'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        if (!core_csrf_valid('conversation_manage', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.invalid_csrf'));
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        (new PrivateMessageQuotaService())->hideConversationForUser((int)$user->id, $convId);
        $this->app->session()->getFlashBag()->add('conv_success', lang('conv.hidden_ok'));
        $this->redirect(core_url('conversations'));
        return '';
    }

    public function deleteMessages(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $convId = resolve_conversation_id($id);
        if ($convId === null) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.no_access'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        if (!core_csrf_valid('conversation_manage', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.invalid_csrf'));
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        $ids = $_POST['message_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        if ($ids === []) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.nothing_selected'));
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        $n = (new PrivateMessageQuotaService())->hideMessagesForUser((int)$user->id, $convId, $ids);
        $this->app->session()->getFlashBag()->add('conv_success', $n > 0 ? lang('conv.messages_deleted_ok') : lang('conv.nothing_selected'));
        $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
        return '';
    }

    public function purgeThreadMessages(string $id): string
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }
        $convId = resolve_conversation_id($id);
        if ($convId === null) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.no_access'));
            $this->redirect(core_url('conversations'));
            return '';
        }
        if (!core_csrf_valid('conversation_manage', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('conv_error', lang('conv.invalid_csrf'));
            $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
            return '';
        }
        (new PrivateMessageQuotaService())->hideAllMessagesInConversationForUser((int)$user->id, $convId);
        $this->app->session()->getFlashBag()->add('conv_success', lang('conv.thread_purged_ok'));
        $this->redirect(core_url('conversations/' . conversation_url_path_by_id($convId)));
        return '';
    }

    protected function getConvStats(): object
    {
        $row = ForumStats::singleton();
        if (!$row) {
            return (object)['total_topics' => 0, 'total_posts' => 0, 'total_members' => 0];
        }
        return (object)[
            'total_topics' => (int) $row->total_topics,
            'total_posts' => (int) $row->total_posts,
            'total_members' => (int) $row->total_members,
        ];
    }

    private function getConversationList(int $userId, ConversationService $svc): array
    {
        $convIds = $svc->getConversationIdsOrderedByLastMessage($userId);
        $list = [];
        foreach ($convIds as $convId) {
            $otherId = $svc->getOtherUserId($convId, $userId);
            $other = $otherId ? User::where('id', $otherId)->first(['id', 'username', 'avatar_path']) : null;
            $last = $svc->getLastVisibleMessageRow($convId, $userId);
            $unread = $svc->countUnreadVisible($convId, $userId);
            $list[] = (object)[
                'conversation_id' => $convId,
                'other' => $other,
                'last_message' => $last,
                'unread' => $unread,
            ];
        }
        return $list;
    }

    private function getOtherParticipant(int $conversationId, int $currentUserId): ?User
    {
        $otherId = (new ConversationService())->getOtherUserId($conversationId, $currentUserId);
        return $otherId ? User::where('id', $otherId)->first(['id', 'username', 'avatar_path']) : null;
    }

    private function getMessages(int $convId, int $viewerId): array
    {
        return PrivateMessage::where('conversation_id', $convId)
            ->whereNotExists(function ($q) use ($viewerId) {
                $q->selectRaw('1')
                    ->from('private_message_hidden as h')
                    ->whereColumn('h.private_message_id', 'private_messages.id')
                    ->where('h.user_id', $viewerId);
            })
            ->with('user:id,username,avatar_path')
            ->orderBy('id')
            ->get(['id', 'body', 'body_html', 'user_id', 'created_at'])
            ->map(fn ($pm) => (object)[
                'id' => $pm->id,
                'body' => $pm->body,
                'body_html' => $pm->body_html,
                'user_id' => $pm->user_id,
                'created_at' => $pm->created_at,
                'username' => $pm->user->username ?? null,
                'avatar_path' => $pm->user->avatar_path ?? null,
            ])
            ->all();
    }

    /**
     * @param object $sender Auth user (id)
     */
    private function notifySenderPmBlocked(object $sender, string $recipientUsername, string $reasonLangKey): void
    {
        $text = lang($reasonLangKey, ['user' => $recipientUsername]);
        $this->app->session()->getFlashBag()->add('conv_error', $text);
        (new UserAlertService())->insert((int) $sender->id, 'pm_undeliverable', [
            'from_user_id' => 0,
            'from_username' => '',
            'message' => $text,
            'recipient_username' => $recipientUsername,
            'url' => core_url('conversations/new'),
        ], true);
    }
}
