<?php

/**
 * Event -> Listener mapping. Core listeners here; plugins can merge their own.
 * Key: event name (Forecor\Core\Events::* or string). Value: [ [ Class, 'method' ], ... ]
 */
return [
    \Forecor\Core\Events::TOPIC_CREATED => [
        [\App\Listeners\LogTopicActivity::class, 'onTopicCreated'],
        [\App\Listeners\IndexTopicInMeilisearch::class, 'onTopicCreated'],
        [\App\Listeners\SendTopicCreatedWebhook::class, 'onTopicCreated'],
        [\App\Listeners\VarnishPurgeListener::class, 'onTopicCreated'],
    ],
    \Forecor\Core\Events::POST_CREATED => [
        [\App\Listeners\LogPostActivity::class, 'onPostCreated'],
        [\App\Listeners\SendPostCreatedNotifications::class, 'onPostCreated'],
        [\App\Listeners\VarnishPurgeListener::class, 'onPostCreated'],
    ],
    \App\Events\TopicEdited::NAME => [
        [\App\Listeners\SendTopicEditMentionNotifications::class, 'onTopicEdited'],
        [\App\Listeners\VarnishPurgeListener::class, 'onTopicEdited'],
    ],
    \App\Events\PostLiked::NAME => [
        [\App\Listeners\SendPostLikedNotification::class, 'onPostLiked'],
    ],
    \App\Events\PostReported::NAME => [
        [\App\Listeners\SendPostReportedNotification::class, 'onPostReported'],
    ],
    \App\Events\ReputationGiven::NAME => [
        [\App\Listeners\SendReputationGivenNotification::class, 'onReputationGiven'],
    ],
    \Forecor\Core\Events::TOPIC_DELETED => [
        [\App\Listeners\SendTopicDeletedWebhook::class, 'onTopicDeleted'],
        [\App\Listeners\VarnishPurgeListener::class, 'onTopicDeleted'],
    ],
    \Forecor\Core\Events::POST_DELETED => [
        // Eklentiler plugin.php → events ile listener ekleyebilir
        [\App\Listeners\VarnishPurgeListener::class, 'onPostDeleted'],
    ],
    \Forecor\Core\Events::USER_REGISTERED => [
        // Eklentiler plugin.php → events ile listener ekleyebilir
    ],
    \Forecor\Core\Events::USER_LOGIN => [
        // Eklentiler plugin.php → events ile listener ekleyebilir
    ],
];
