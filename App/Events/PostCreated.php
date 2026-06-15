<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Post;
use Symfony\Contracts\EventDispatcher\Event;

class PostCreated extends Event
{
    public const NAME = 'post.created';

    public function __construct(
        public Post $post,
        public array $data = []
    ) {
    }
}
