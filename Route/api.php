<?php

declare(strict_types=1);

/**
 * API ve AJAX routes. Loaded by public/index.php and passed to Application router.
 */

return function (\Forecor\Core\Router $router) {
    // API (AJAX): rozet sayıları, kullanıcı arama, bildirim listesi, portal tab
    $router->get('/api/badges', 'ApiController@badges');
    $router->get('/api/users/search', 'ApiController@userSearch');
    $router->get('/api/notifications/unread', 'ApiController@notificationsUnread');
    $router->get('/api/notifications/dropdown', 'ApiController@notificationsDropdown');
    $router->get('/api/sse', 'ApiController@sseStream');
    $router->get('/api/portal-tab', 'ApiController@portalTab');
    $router->get('/api/search', 'SearchController@api');
    $router->get('/api/tags/suggest', 'ApiController@tagsSuggest');
    $router->post('/api/tags/create', 'ApiController@tagCreate');
    $router->post('/api/announcement-dismiss', 'ApiController@announcementDismiss');
    $router->get('/api/hover/user', 'ApiController@hoverUser');
    $router->get('/api/hover/post', 'ApiController@hoverPost');
    $router->get('/api/smileys', 'ApiController@smileys');
    $router->get('/api/user-nav', 'ApiController@userNav');
    $router->post('/api/topic/viewer-ping', 'ApiController@topicViewerPing');
    $router->get('/api/topic/viewers', 'ApiController@topicViewers');
    $router->get('/api/topic/{id}/live-stream', 'TopicController@liveRepliesStream');
    $router->get('/api/topic/{id}/live-post/{postId}', 'TopicController@livePostCard');
};
