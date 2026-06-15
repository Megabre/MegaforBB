<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Observers;

use App\Events\IdeaCleaned;
use App\Events\IdeaCreated;
use App\Modules\Idelist\Models\Idea;

class IdeaObserver
{
    public function created(Idea $idea): void
    {
        app()?->event()->dispatch(new IdeaCreated($idea), IdeaCreated::NAME);
    }

    public function updated(Idea $idea): void
    {
        if ($idea->isDirty('status')) {
            app()?->cache()->delete('idelist.enabled');
            app()?->cache()->delete('idelist.counts');
        }
    }

    public function deleted(Idea $idea): void
    {
        app()?->event()->dispatch(new IdeaCleaned($idea), IdeaCleaned::NAME);
    }
}
