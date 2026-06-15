<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Controllers;

use App\Controllers\BaseController;
use App\Modules\Idelist\Models\Idea;
use App\Modules\Idelist\Services\VoteService;

class VoteController extends BaseController
{
    public function __construct(\Forecor\Core\Application $app, private ?VoteService $voteService = null)
    {
        parent::__construct($app);
        $this->voteService ??= new VoteService();
    }

    public function vote(string $ideaId): void
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
        }
        $this->enforceVoteRateLimit((int) $user->id);
        if (!core_csrf_valid('idelist_vote', (string) ($_POST['_token'] ?? ''))) {
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => lang('common.invalid_csrf')], 422);
                return;
            }
            $this->redirect($_SERVER['HTTP_REFERER'] ?? core_url('idelist'));
        }
        $idea = Idea::query()->findOrFail((int) $ideaId);
        $value = (int) ($_POST['value'] ?? 1);
        $this->voteService->vote($user, $idea, $value);
        if ($this->isAjaxRequest()) {
            $this->json(['ok' => true, 'vote_count' => $idea->fresh()->vote_count]);
            return;
        }
        $this->redirect($_SERVER['HTTP_REFERER'] ?? core_url('idelist/' . $idea->slug));
    }

    public function unvote(string $ideaId): void
    {
        $user = $this->app->auth()->user();
        if (!$user) {
            $this->redirect(core_url('login'));
        }
        $idea = Idea::query()->findOrFail((int) $ideaId);
        $this->voteService->unvote($user, $idea);
        $this->redirect($_SERVER['HTTP_REFERER'] ?? core_url('idelist/' . $idea->slug));
    }

    private function enforceVoteRateLimit(int $userId): void
    {
        $key = 'idelist.vote.rl.' . $userId;
        $cache = $this->app->cache();
        $data = $cache->get($key) ?? ['count' => 0, 'start' => time()];
        $now = time();
        if (($now - (int) $data['start']) >= 60) {
            $data = ['count' => 0, 'start' => $now];
        }
        $data['count']++;
        $cache->set($key, $data, 120);
        if ((int) $data['count'] > 60) {
            if ($this->isAjaxRequest()) {
                $this->json(['ok' => false, 'error' => 'Too many requests'], 429);
            }
            http_response_code(429);
            exit('429');
        }
    }
}
