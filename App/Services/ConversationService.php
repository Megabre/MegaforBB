<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Özel mesajlaşma: tüm yazma işlemleri Query Builder ile.
 * Eloquent create/attach kullanılmaz; sütun isimleri tam kontrol altında.
 */
class ConversationService
{
    private const PIVOT_TABLE = 'conversation_user';
    private const MESSAGES_TABLE = 'private_messages';

    /**
     * Yeni konuşmaya iki kullanıcıyı ekler. Sadece mevcut sütunlar kullanılır.
     */
    public function attachUsersToConversation(int $conversationId, int $user1Id, int $user2Id): void
    {
        $rows = [
            [
                'conversation_id' => $conversationId,
                'user_id' => $user1Id,
                'last_read_at' => null,
            ],
            [
                'conversation_id' => $conversationId,
                'user_id' => $user2Id,
                'last_read_at' => null,
            ],
        ];
        DB::table(self::PIVOT_TABLE)->insert($rows);
    }

    /**
     * Kullanıcının konuşmadaki last_read_at değerini günceller.
     */
    public function markConversationRead(int $conversationId, int $userId): void
    {
        DB::table(self::PIVOT_TABLE)
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['last_read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Mesaj ekler. Sadece tabloda olan sütunlar: conversation_id, user_id, body, body_html, created_at.
     */
    public function addMessage(int $conversationId, int $userId, string $body, string $bodyHtml): void
    {
        DB::table(self::MESSAGES_TABLE)->insert([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'body' => $body,
            'body_html' => $bodyHtml,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Kullanıcı bu konuşmada mı (pivot’a dokunmadan). */
    public function userInConversation(int $conversationId, int $userId): bool
    {
        return DB::table(self::PIVOT_TABLE)
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    /** Konuşmadaki diğer kullanıcının user_id’si (tek kişilik konuşma). */
    public function getOtherUserId(int $conversationId, int $currentUserId): ?int
    {
        $id = DB::table(self::PIVOT_TABLE)
            ->where('conversation_id', $conversationId)
            ->where('user_id', '!=', $currentUserId)
            ->value('user_id');
        return $id !== null ? (int) $id : null;
    }

    /** Pivot’taki last_read_at (sadece bu sütun). */
    public function getLastReadAt(int $conversationId, int $userId): ?string
    {
        $at = DB::table(self::PIVOT_TABLE)
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->value('last_read_at');
        return $at;
    }

    /** İki kullanıcı arasında zaten konuşma var mı; varsa conversation_id. */
    public function findConversationBetween(int $user1Id, int $user2Id): ?int
    {
        $id = DB::table(self::PIVOT_TABLE)
            ->whereIn('user_id', [$user1Id, $user2Id])
            ->groupBy('conversation_id')
            ->havingRaw('COUNT(DISTINCT user_id) = 2')
            ->value('conversation_id');
        return $id !== null ? (int) $id : null;
    }

    /**
     * Kullanıcının konuşma listesi: conversation_id’ler, son mesaja göre sıralı.
     * @return int[]
     */
    public function getConversationIdsOrderedByLastMessage(int $userId): array
    {
        $convIds = DB::table(self::PIVOT_TABLE)
            ->where('user_id', $userId)
            ->whereNull('hidden_at')
            ->pluck('conversation_id')
            ->all();
        if ($convIds === []) {
            return [];
        }
        return DB::table(self::MESSAGES_TABLE . ' as pm')
            ->select('pm.conversation_id')
            ->selectRaw('MAX(pm.id) as max_id')
            ->whereIn('pm.conversation_id', $convIds)
            ->whereNotExists(function ($q) use ($userId) {
                $q->selectRaw('1')
                    ->from('private_message_hidden as h')
                    ->whereColumn('h.private_message_id', 'pm.id')
                    ->where('h.user_id', $userId);
            })
            ->groupBy('pm.conversation_id')
            ->orderByDesc('max_id')
            ->pluck('pm.conversation_id')
            ->map(static fn ($cid) => (int) $cid)
            ->all();
    }

    public function isConversationVisibleToUser(int $conversationId, int $userId): bool
    {
        return DB::table(self::PIVOT_TABLE)
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->whereNull('hidden_at')
            ->exists();
    }

    /** Son görünür mesaj (kullanıcı için gizlenmemiş). */
    public function getLastVisibleMessageRow(int $conversationId, int $userId): ?object
    {
        $row = DB::table(self::MESSAGES_TABLE . ' as pm')
            ->where('pm.conversation_id', $conversationId)
            ->whereNotExists(function ($q) use ($userId) {
                $q->selectRaw('1')
                    ->from('private_message_hidden as h')
                    ->whereColumn('h.private_message_id', 'pm.id')
                    ->where('h.user_id', $userId);
            })
            ->orderByDesc('pm.id')
            ->first(['pm.body', 'pm.user_id', 'pm.created_at']);
        return $row ?: null;
    }

    public function countUnreadVisible(int $conversationId, int $userId): int
    {
        $lastRead = $this->getLastReadAt($conversationId, $userId) ?? '1970-01-01 00:00:00';
        return (int) DB::table(self::MESSAGES_TABLE . ' as pm')
            ->where('pm.conversation_id', $conversationId)
            ->where('pm.user_id', '!=', $userId)
            ->where('pm.created_at', '>', $lastRead)
            ->whereNotExists(function ($q) use ($userId) {
                $q->selectRaw('1')
                    ->from('private_message_hidden as h')
                    ->whereColumn('h.private_message_id', 'pm.id')
                    ->where('h.user_id', $userId);
            })
            ->count();
    }

    /** Yeni mesajda sohbet her iki tarafta da listede görünsün diye gizlemeyi kaldırır. */
    public function unhideConversationForAllParticipants(int $conversationId): void
    {
        DB::table(self::PIVOT_TABLE)
            ->where('conversation_id', $conversationId)
            ->update(['hidden_at' => null]);
    }
}
