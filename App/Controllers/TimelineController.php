<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserActivityService;
use Forecor\Core\Application;

class TimelineController extends BaseController
{
    private UserActivityService $activityService;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->activityService = new UserActivityService();
    }

    public function index(): string
    {
        if ($this->getSetting('enable_timeline', '0') !== '1') {
            http_response_code(404);
            return $this->layout('404', ['pageTitle' => lang('error.not_found')], false);
        }

        // Members only
        if (!$this->app->auth()->user()) {
            $this->app->session()->getFlashBag()->add('auth_error', core__('auth.login_required'));
            $this->redirect(core_url('login'));
            return '';
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $filters = $this->activityService->normalizeFilters([
            'user' => (string) ($_GET['user'] ?? ''),
            'action' => (string) ($_GET['action'] ?? UserActivityService::FILTER_ALL),
        ]);

        $activities = $this->activityService->getActivities($page, $perPage, $filters);

        $isAjax = $this->isAjaxRequest();
        if ($isAjax) {
            return $this->view('timeline/items', [
                'activities' => $activities,
                'timelineStartIndex' => ($page - 1) * $perPage,
            ]);
        }

        $actionOptions = [
            UserActivityService::FILTER_ALL,
            UserActivityService::ACTION_TOPIC_CREATED,
            UserActivityService::ACTION_POST_CREATED,
            UserActivityService::ACTION_LIKE_GIVEN,
            UserActivityService::ACTION_REP_GIVEN,
            UserActivityService::ACTION_USER_REGISTERED,
        ];

        $defaultTitle = core__('timeline.title');
        $defaultDesc = core__('timeline.description');
        return $this->layout('timeline/index', [
            'pageTitle' => $this->getSetting('timeline_title', $defaultTitle),
            'activities' => $activities,
            'page' => $page,
            'hero_visible' => false,
            'timelineTitle' => $this->getSetting('timeline_title', $defaultTitle),
            'timelineDescription' => $this->getSetting('timeline_description', $defaultDesc),
            'timelineFilters' => $filters,
            'timelineActionOptions' => $actionOptions,
        ], false);
    }
}
