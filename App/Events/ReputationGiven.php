<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Symfony\Contracts\EventDispatcher\Event;

class ReputationGiven extends Event
{
    public const NAME = 'reputation.given';

    public function __construct(
        public User $fromUser,
        public User $toUser,
        public int $value,
        public ?int $postId = null,
    ) {
    }
}
