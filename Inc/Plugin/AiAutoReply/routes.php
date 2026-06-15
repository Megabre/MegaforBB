<?php

declare(strict_types=1);

/**
 * @var \Forecor\Core\Router $router
 */

$adminPath = env('ADMIN_PATH', 'admin');

$router->get('/' . $adminPath . '/ai-auto-reply', 'Plugins\\AiAutoReply\\AdminAiAutoReplyController@index');
$router->post('/' . $adminPath . '/ai-auto-reply/save', 'Plugins\\AiAutoReply\\AdminAiAutoReplyController@save');
$router->get('/ai-auto-reply/worker/{token}', 'Plugins\\AiAutoReply\\AiAutoReplyWorkerController@run');
