<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Services;

use App\Models\User;
use App\Modules\Idelist\Events\IdeaVoted;
use App\Modules\Idelist\Models\Idea;
use App\Modules\Idelist\Models\IdeaVote;
use App\Modules\Idelist\Models\IdelistSetting;
use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;

class VoteService
{
    public function vote(User $user, Idea $idea, int $value): void
    {
        if (!in_array($value, [1, -1], true)) {
            throw new InvalidArgumentException('Invalid vote value.');
        }

        if ($value === -1 && IdelistSetting::getValue('allow_downvotes', '1') !== '1') {
            throw new InvalidArgumentException('Downvotes disabled.');
        }

        DB::connection()->transaction(function () use ($user, $idea, $value): void {
            IdeaVote::query()->updateOrCreate(
                ['idea_id' => $idea->id, 'user_id' => $user->id],
                ['value' => $value]
            );

            $count = (int) IdeaVote::query()->where('idea_id', $idea->id)->sum('value');
            $idea->vote_count = $count;
            $idea->save();
        });

        app()?->event()->dispatch(new IdeaVoted($idea, $user, $value), IdeaVoted::NAME);
    }

    public function unvote(User $user, Idea $idea): void
    {
        DB::connection()->transaction(function () use ($user, $idea): void {
            IdeaVote::query()->where('idea_id', $idea->id)->where('user_id', $user->id)->delete();
            $idea->vote_count = (int) IdeaVote::query()->where('idea_id', $idea->id)->sum('value');
            $idea->save();
        });
    }
}
