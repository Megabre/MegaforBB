<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Symfony\Contracts\EventDispatcher\Event;

class PostLiked extends Event
{
    public const NAME = 'post.liked';

    public function __construct(
        public Post $post,
        public User $userWhoLiked,
    ) {
    }
}
