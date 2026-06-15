<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Events;

use App\Models\User;
use App\Modules\Idelist\Models\Idea;
use Symfony\Contracts\EventDispatcher\Event;

class IdeaVoted extends Event
{
    public const NAME = 'idelist.idea_voted';

    public function __construct(
        public Idea $idea,
        public User $user,
        public int $value
    ) {
    }
}
