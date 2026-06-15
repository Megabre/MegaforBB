<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Topic;
use Symfony\Contracts\EventDispatcher\Event;

class TopicCreated extends Event
{
    public const NAME = 'topic.created';

    public function __construct(
        public Topic $topic,
        public array $data = []
    ) {
    }
}
