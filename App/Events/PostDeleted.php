<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;
use Symfony\Contracts\EventDispatcher\Event;

class PostDeleted extends Event
{
    public const NAME = 'post.deleted';

    public function __construct(
        public Post $post,
        public ?int $deletedByUserId = null
    ) {
    }
}
