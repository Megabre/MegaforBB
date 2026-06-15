<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Özel mesaj kotası: günlük gönderim + yaşam boyu toplam mesaj (görünür satırlar).
 */
class PrivateMessageQuotaService
{
    /**
     * Kullanıcının gördüğü tüm mesajlar (gönderdiği + aldığı), konuşma listede gizli olsa da sayılır;
     * sadece kullanıcı tarafından gizlenen satırlar düşülür.
     */
    public function countVisibleMessagesForUser(int $userId): int
    {
        return (int) DB::table('private_messages as pm')
            ->join('conversation_user as cu', function ($join) use ($userId) {
                $join->on('cu.conversation_id', '=', 'pm.conversation_id')
                    ->where('cu.user_id', '=', $userId);
            })
            ->whereNotExists(function ($q) use ($userId) {
                $q->selectRaw('1')
                    ->from('private_message_hidden as h')
                    ->whereColumn('h.private_message_id', 'pm.id')
                    ->where('h.user_id', $userId);
            })
            ->count();
    }

    public function countVisibleSentMessages(int $userId): int
    {
        return (int) DB::table('private_messages as pm')
            ->join('conversation_user as cu', function ($join) use ($userId) {
                $join->on('cu.conversation_id', '=', 'pm.conversation_id')
                    ->where('cu.user_id', '=', $userId);
            })
            ->where('pm.user_id', '=', $userId)
            ->whereNotExists(function ($q) use ($userId) {
                $q->selectRaw('1')
                    ->from('private_message_hidden as h')
                    ->whereColumn('h.private_message_id', 'pm.id')
                    ->where('h.user_id', $userId);
            })
            ->count();
    }

    public function countVisibleReceivedMessages(int $userId): int
    {
        return (int) DB::table('private_messages as pm')
            ->join('conversation_user as cu', function ($join) use ($userId) {
                $join->on('cu.conversation_id', '=', 'pm.conversation_id')
                    ->where('cu.user_id', '=', $userId);
            })
            ->where('pm.user_id', '!=', $userId)
            ->whereNotExists(function ($q) use ($userId) {
                $q->selectRaw('1')
                    ->from('private_message_hidden as h')
                    ->whereColumn('h.private_message_id', 'pm.id')
                    ->where('h.user_id', $userId);
            })
            ->count();
    }

    /**
     * @return string|null Dil anahtarı
     */
    public function senderLifetimeBlockReason(User $user): ?string
    {
        $role = $user->role;
        if (!$role) {
            return null;
        }
        $limit = (int) ($role->pm_lifetime_total_quota ?? 0);
        if ($limit <= 0) {
            return null;
        }
        $used = $this->countVisibleMessagesForUser((int) $user->id);
        if ($used >= $limit) {
            return 'quota.pm_sender_lifetime_full';
        }
        return null;
    }

    /**
     * @return string|null Dil anahtarı
     */
    public function recipientDeliveryBlockReason(User $recipient): ?string
    {
        $role = $recipient->role;
        if (!$role) {
            return null;
        }
        $limit = (int) ($role->pm_lifetime_total_quota ?? 0);
        if ($limit <= 0) {
            return null;
        }
        $used = $this->countVisibleMessagesForUser((int) $recipient->id);
        if ($used >= $limit) {
            return 'quota.pm_recipient_lifetime_full';
        }
        return null;
    }

    /**
     * Panel + pasta grafiği için özet.
     *
     * @return array<string, mixed>
     */
    public function getPanelSummary(User $user): array
    {
        $role = $user->role;
        $dailySendLimit = $role ? (int) ($role->pm_daily_limit ?? 0) : 0;
        $lifetimeLimit = $role ? (int) ($role->pm_lifetime_total_quota ?? 0) : 0;

        $todayStart = now()->startOfDay()->format('Y-m-d H:i:s');
        $sentToday = (int) DB::table('private_messages')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $todayStart)
            ->count();

        $uid = (int) $user->id;
        $totalVisible = $this->countVisibleMessagesForUser($uid);
        $sentVisible = $this->countVisibleSentMessages($uid);
        $recvVisible = $this->countVisibleReceivedMessages($uid);

        $lifetimeRemaining = $lifetimeLimit > 0 ? max(0, $lifetimeLimit - $totalVisible) : null;

        $pieSentDeg = 0.0;
        $pieRecvDeg = 0.0;
        $pieRemDeg = 0.0;
        $pieUnlimited = $lifetimeLimit <= 0;

        if (!$pieUnlimited) {
            $pieSentDeg = round(360 * $sentVisible / $lifetimeLimit, 2);
            $pieRecvDeg = round(360 * $recvVisible / $lifetimeLimit, 2);
            $pieRemDeg = max(0.0, 360.0 - $pieSentDeg - $pieRecvDeg);
        } else {
            $sum = $sentVisible + $recvVisible;
            if ($sum > 0) {
                $pieSentDeg = round(360 * $sentVisible / $sum, 2);
                $pieRecvDeg = 360.0 - $pieSentDeg;
            }
        }

        $pieInlineStyle = $this->buildPieInlineStyle(
            $pieUnlimited,
            $lifetimeLimit,
            $sentVisible,
            $recvVisible,
            $pieSentDeg,
            $pieRecvDeg,
            $pieRemDeg
        );
        $pieSvg = $this->buildPieSvg(
            $pieUnlimited,
            $lifetimeLimit,
            $sentVisible,
            $recvVisible,
            $pieSentDeg,
            $pieRecvDeg,
            $pieRemDeg
        );

        return [
            'daily_send_limit' => $dailySendLimit,
            'sent_today' => $sentToday,
            'send_remaining' => $dailySendLimit > 0 ? max(0, $dailySendLimit - $sentToday) : null,
            'lifetime_total_limit' => $lifetimeLimit,
            'total_visible_messages' => $totalVisible,
            'sent_visible' => $sentVisible,
            'received_visible' => $recvVisible,
            'lifetime_remaining' => $lifetimeRemaining,
            'pie_lifetime_total' => $lifetimeLimit,
            'pie_sent_deg' => $pieSentDeg,
            'pie_recv_deg' => $pieRecvDeg,
            'pie_rem_deg' => $pieRemDeg,
            'pie_unlimited' => $pieUnlimited,
            'pie_inline_style' => $pieInlineStyle,
            'pie_svg' => $pieSvg,
        ];
    }

    /**
     * Tarayıcıdan bağımsız pasta (SVG). conic-gradient desteklenmeyen ortamlar için.
     */
    private function buildPieSvg(
        bool $unlimited,
        int $lifetimeLimit,
        int $sentVisible,
        int $recvVisible,
        float $pieSentDeg,
        float $pieRecvDeg,
        float $pieRemDeg
    ): string {
        $cx = 50.0;
        $cy = 50.0;
        $r = 40.0;
        $segments = [];
        if ($unlimited && ($sentVisible + $recvVisible) > 0) {
            $segments[] = ['c' => '#1a252f', 'deg' => $pieSentDeg];
            $segments[] = ['c' => '#64748b', 'deg' => $pieRecvDeg];
        } elseif ($lifetimeLimit > 0 && ($pieSentDeg + $pieRecvDeg + $pieRemDeg) > 0.01) {
            $segments[] = ['c' => '#1a252f', 'deg' => $pieSentDeg];
            $segments[] = ['c' => '#64748b', 'deg' => $pieRecvDeg];
            $segments[] = ['c' => '#cbd5e1', 'deg' => $pieRemDeg];
        } else {
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="112" height="112" aria-hidden="true"><circle cx="%.1F" cy="%.1F" r="%.1F" fill="#e2e8f0"/></svg>',
                $cx,
                $cy,
                $r
            );
        }

        $active = array_values(array_filter($segments, static fn (array $s): bool => $s['deg'] >= 0.05));
        if ($active === []) {
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="112" height="112" aria-hidden="true"><circle cx="%.1F" cy="%.1F" r="%.1F" fill="#e2e8f0"/></svg>',
                $cx,
                $cy,
                $r
            );
        }

        if (count($active) === 1 && $active[0]['deg'] >= 359.4) {
            $c = htmlspecialchars($active[0]['c'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $hole = sprintf('<circle cx="%.5F" cy="%.5F" r="18" fill="#f8fafc"/>', $cx, $cy);

            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="112" height="112" aria-hidden="true" focusable="false">'
                . sprintf('<circle fill="%s" cx="%.5F" cy="%.5F" r="%.5F"/>', $c, $cx, $cy, $r)
                . $hole
                . '</svg>';
        }

        $paths = [];
        $cursor = -90.0;
        foreach ($segments as $seg) {
            $deg = $seg['deg'];
            if ($deg < 0.05) {
                continue;
            }
            $startRad = deg2rad($cursor);
            $endRad = deg2rad($cursor + $deg);
            $x1 = $cx + $r * cos($startRad);
            $y1 = $cy + $r * sin($startRad);
            $x2 = $cx + $r * cos($endRad);
            $y2 = $cy + $r * sin($endRad);
            $large = $deg > 180.0 ? 1 : 0;
            $color = htmlspecialchars($seg['c'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $paths[] = sprintf(
                '<path fill="%s" d="M %.5F %.5F L %.5F %.5F A %.5F %.5F 0 %d 1 %.5F %.5F Z"/>',
                $color,
                $cx,
                $cy,
                $x1,
                $y1,
                $r,
                $r,
                $large,
                $x2,
                $y2
            );
            $cursor += $deg;
        }

        if ($paths === []) {
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="112" height="112" aria-hidden="true"><circle cx="%.1F" cy="%.1F" r="%.1F" fill="#e2e8f0"/></svg>',
                $cx,
                $cy,
                $r
            );
        }

        $hole = sprintf(
            '<circle cx="%.5F" cy="%.5F" r="18" fill="#f8fafc"/>',
            $cx,
            $cy
        );

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="112" height="112" aria-hidden="true" focusable="false">'
            . implode('', $paths)
            . $hole
            . '</svg>';
    }

    /**
     * Tek satır CSS (Twig’de çok satırlı style + locale ondalık sorunlarını önler).
     */
    private function buildPieInlineStyle(
        bool $unlimited,
        int $lifetimeLimit,
        int $sentVisible,
        int $recvVisible,
        float $pieSentDeg,
        float $pieRecvDeg,
        float $pieRemDeg
    ): string {
        if ($unlimited && ($sentVisible + $recvVisible) > 0) {
            $a = $pieSentDeg;
            $b = $pieSentDeg + $pieRecvDeg;

            return sprintf(
                'background:conic-gradient(from -90deg,#1a252f 0deg %.4Fdeg,#64748b %.4Fdeg %.4Fdeg);',
                $a,
                $a,
                $b
            );
        }
        if ($lifetimeLimit > 0 && ($pieSentDeg + $pieRecvDeg + $pieRemDeg) > 0.01) {
            $a = $pieSentDeg;
            $b = $pieSentDeg + $pieRecvDeg;
            $c = $pieSentDeg + $pieRecvDeg + $pieRemDeg;

            return sprintf(
                'background:conic-gradient(from -90deg,#1a252f 0deg %.4Fdeg,#64748b %.4Fdeg %.4Fdeg,#cbd5e1 %.4Fdeg %.4Fdeg);',
                $a,
                $a,
                $b,
                $b,
                $c
            );
        }

        return 'background:#e2e8f0;';
    }

    /**
     * @param int[] $messageIds
     */
    public function hideMessagesForUser(int $userId, int $conversationId, array $messageIds): int
    {
        if ($messageIds === []) {
            return 0;
        }
        $messageIds = array_values(array_unique(array_filter(array_map('intval', $messageIds))));
        if ($messageIds === []) {
            return 0;
        }
        $convSvc = new ConversationService();
        if (!$convSvc->userInConversation($conversationId, $userId)) {
            return 0;
        }
        $valid = DB::table('private_messages')
            ->where('conversation_id', $conversationId)
            ->whereIn('id', $messageIds)
            ->pluck('id')
            ->all();
        if ($valid === []) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $rows = array_map(static fn (int $mid) => [
            'private_message_id' => $mid,
            'user_id' => $userId,
            'hidden_at' => $now,
        ], $valid);
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('private_message_hidden')->insertOrIgnore($chunk);
        }
        return count($valid);
    }

    public function hideAllMessagesInConversationForUser(int $userId, int $conversationId): int
    {
        $convSvc = new ConversationService();
        if (!$convSvc->userInConversation($conversationId, $userId)) {
            return 0;
        }
        $ids = DB::table('private_messages')
            ->where('conversation_id', $conversationId)
            ->pluck('id')
            ->all();
        return $this->hideMessagesForUser($userId, $conversationId, $ids);
    }

    public function hideConversationForUser(int $userId, int $conversationId): void
    {
        $convSvc = new ConversationService();
        if (!$convSvc->userInConversation($conversationId, $userId)) {
            return;
        }
        // Konuşmayı listeden gizlemek, bu kullanıcı için mesaj geçmişini de "silinmiş" (gizli) ile aynı hizada tutar;
        // aksi halde yeni mesajda hidden_at kalkınca eski mesajlar tekrar görünürdü.
        $this->hideAllMessagesInConversationForUser($userId, $conversationId);
        DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['hidden_at' => date('Y-m-d H:i:s')]);
    }

    public function hideAllConversationsForUser(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $convIds = DB::table('conversation_user')
            ->where('user_id', $userId)
            ->whereNull('hidden_at')
            ->pluck('conversation_id')
            ->all();
        foreach ($convIds as $cid) {
            $this->hideAllMessagesInConversationForUser($userId, (int) $cid);
        }
        DB::table('conversation_user')
            ->where('user_id', $userId)
            ->whereNull('hidden_at')
            ->update(['hidden_at' => $now]);
    }
}
