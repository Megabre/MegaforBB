<?php

declare(strict_types=1);

namespace App\Events;

use App\Modules\Idelist\Models\Idea;
use Symfony\Contracts\EventDispatcher\Event;

class IdeaCleaned extends Event
{
    public const NAME = 'idelist.idea_cleaned';

    public function __construct(public Idea $idea)
    {
    }
}
