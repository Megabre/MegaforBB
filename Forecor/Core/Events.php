<?php

declare(strict_types=1);

namespace Forecor\Core;

/**
 * MegaforBB hook/event isimleri (çekirdek).
 * Eklentiler config/events.php ile bu isimlere listener ekleyebilir; çekirdek dosyalara dokunmaz.
 */
final class Events
{
    /** Konu (thread) oluşturuldu. Payload: TopicCreated event (topic, data). */
    public const TOPIC_CREATED = 'topic.created';

    /** Mesaj (post) oluşturuldu. Payload: PostCreated event (post, data). */
    public const POST_CREATED = 'post.created';

    /** Konu silindi (soft veya kalıcı). Payload: topic id veya Topic model. */
    public const TOPIC_DELETED = 'topic.deleted';

    /** Mesaj silindi. Payload: post id veya Post model. */
    public const POST_DELETED = 'post.deleted';

    /** Kullanıcı kayıt oldu. Payload: User model. */
    public const USER_REGISTERED = 'user.registered';

    /** Kullanıcı giriş yaptı. Payload: User model. */
    public const USER_LOGIN = 'user.login';

    /** Mesaj raporlandı. Payload: post, reporter user, reason. */
    public const POST_REPORTED = 'post.reported';

    /** Rep verildi. Payload: from_user, to_user, value, post_id. */
    public const REPUTATION_GIVEN = 'reputation.given';

    /** Alias: thread.created = topic.created (aynı olay). */
    public const THREAD_CREATED = self::TOPIC_CREATED;

    private function __construct()
    {
    }
}
