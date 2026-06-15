<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Poll voting actions.
 */
class PollController extends BaseController
{
    public function vote(string $topicId): string
    {
        $topicIdInt = function_exists('resolve_topic_id') ? resolve_topic_id($topicId) : (ctype_digit($topicId) ? (int) $topicId : null);
        if ($topicIdInt === null || $topicIdInt <= 0) {
            $this->app->session()->getFlashBag()->add('poll_error', lang('poll.not_found'));
            $this->redirect(core_url(''));
            return '';
        }
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
            return '';
        }

        if (!core_csrf_valid('poll_vote', (string)($_POST['_token'] ?? ''))) {
            $this->app->session()->getFlashBag()->add('poll_error', core__('common.invalid_csrf'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        }

        $poll = Poll::where('topic_id', $topicIdInt)->first();

        if (!$poll) {
            $this->app->session()->getFlashBag()->add('poll_error', lang('poll.not_found'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        }

        if ($poll->isClosed()) {
            $this->app->session()->getFlashBag()->add('poll_error', lang('poll.closed'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        }

        $optionIds = [];
        if (isset($_POST['option_id']) && $_POST['option_id'] !== '') {
            $optionIds = [(int) $_POST['option_id']];
        } elseif (isset($_POST['option_ids']) && is_array($_POST['option_ids'])) {
            $optionIds = array_map('intval', array_filter($_POST['option_ids']));
        }

        if (empty($optionIds)) {
            $this->app->session()->getFlashBag()->add('poll_error', lang('poll.select_at_least_one'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        }

        $optionIds = array_unique($optionIds);
        $maxVotes = (int) $poll->max_votes;
        if (count($optionIds) > $maxVotes) {
            $this->app->session()->getFlashBag()->add('poll_error', lang('poll.max_options', ['max' => $maxVotes]));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        }

        $validOptionIds = PollOption::where('poll_id', $poll->id)
            ->whereIn('id', $optionIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (count($validOptionIds) !== count($optionIds)) {
            $this->app->session()->getFlashBag()->add('poll_error', lang('poll.invalid_option'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        }

        $userId = (int) $user->id;

        try {
            DB::transaction(function () use ($poll, $userId, $validOptionIds) {
                if ($poll->allow_change_vote) {
                    $oldVotes = $poll->votes()->where('user_id', $userId)->pluck('option_id')->all();
                    if (!empty($oldVotes)) {
                        $poll->votes()->where('user_id', $userId)->delete();
                        foreach ($oldVotes as $oid) {
                            PollOption::where('id', $oid)->update([
                                'vote_count' => DB::raw('GREATEST(0, CAST(vote_count AS SIGNED) - 1)'),
                            ]);
                        }
                    }
                } else {
                    if ($poll->votes()->where('user_id', $userId)->exists()) {
                        throw new \RuntimeException('already_voted');
                    }
                }

                $now = \now();
                foreach ($validOptionIds as $oid) {
                    PollVote::create([
                        'poll_id' => $poll->id,
                        'option_id' => $oid,
                        'user_id' => $userId,
                        'created_at' => $now,
                    ]);
                    PollOption::where('id', $oid)->increment('vote_count');
                }
            });

            $this->app->session()->getFlashBag()->add('poll_ok', lang('poll.vote_saved'));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'already_voted') {
                $this->app->session()->getFlashBag()->add('poll_error', lang('poll.already_voted_no_change'));
            } else {
                throw $e;
            }
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        } catch (\Throwable $e) {
            error_log('Poll vote error: ' . $e->getMessage());
            $this->app->session()->getFlashBag()->add('poll_error', $this->dbErrorMessage($e));
            $this->redirect(core_url('topic/' . topic_url_path_by_id($topicIdInt)));
            return '';
        }
    }
}
