<?php

declare(strict_types=1);

return [
    'events' => [],
    'actions' => [
        'after_topic_create' => [
            [\Plugins\AiAutoReply\PluginHooks::class, 'enqueueTopicReply', 10],
        ],
        'after_post_create' => [
            [\Plugins\AiAutoReply\PluginHooks::class, 'enqueuePostReply', 10],
        ],
    ],
    'filters' => [
        'translator.lines' => [
            [\Plugins\AiAutoReply\PluginHooks::class, 'translatorLines'],
        ],
        'admin.menu' => [
            [\Plugins\AiAutoReply\PluginHooks::class, 'adminMenu'],
        ],
    ],
];
