<?php

declare(strict_types=1);

namespace App\Events;

use App\Modules\Idelist\Models\Idea;
use Symfony\Contracts\EventDispatcher\Event;

class IdeaStatusChanged extends Event
{
    public const NAME = 'idelist.idea_status_changed';

    public function __construct(
        public Idea $idea,
        public string $oldStatus,
        public string $newStatus
    ) {
    }
}
