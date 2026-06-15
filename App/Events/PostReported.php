<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Symfony\Contracts\EventDispatcher\Event;

class PostReported extends Event
{
    public const NAME = 'post.reported';

    public function __construct(
        public Post $post,
        public User $reporter,
    ) {
    }
}
