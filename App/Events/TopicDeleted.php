<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Topic;
use Symfony\Contracts\EventDispatcher\Event;

class TopicDeleted extends Event
{
    public const NAME = 'topic.deleted';

    public function __construct(
        public Topic $topic,
        public ?int $deletedByUserId = null
    ) {
    }
}
