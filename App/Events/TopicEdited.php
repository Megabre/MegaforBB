<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Topic;
use App\Models\User;
use Symfony\Contracts\EventDispatcher\Event;

class TopicEdited extends Event
{
    public const NAME = 'topic.edited';

    public function __construct(
        public Topic $topic,
        public User $editor,
        public string $body,
        public string $title,
    ) {
    }
}
