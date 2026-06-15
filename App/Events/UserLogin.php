<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserLogin extends Event
{
    public const NAME = 'user.login';

    public function __construct(
        public User $user
    ) {
    }
}
