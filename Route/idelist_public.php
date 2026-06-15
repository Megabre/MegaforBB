<?php

declare(strict_types=1);

return static function (\Forecor\Core\Router $router): void {
    $router->get('/idelist', '\App\Modules\Idelist\Controllers\IdeaController@index');
    $router->get('/idelist/create', '\App\Modules\Idelist\Controllers\IdeaController@create');
    $router->post('/idelist', '\App\Modules\Idelist\Controllers\IdeaController@store');
    $router->get('/idelist/{slug}', '\App\Modules\Idelist\Controllers\IdeaController@show');
    $router->get('/idelist/user/{username}', '\App\Modules\Idelist\Controllers\IdeaController@userIdeas');
    $router->get('/idelist/{slug}/edit', '\App\Modules\Idelist\Controllers\IdeaController@edit');
    $router->post('/idelist/{slug}/edit', '\App\Modules\Idelist\Controllers\IdeaController@update');
    $router->post('/idelist/{slug}/delete', '\App\Modules\Idelist\Controllers\IdeaController@destroy');
    $router->post('/idelist/{ideaId}/vote', '\App\Modules\Idelist\Controllers\VoteController@vote');
    $router->post('/idelist/{ideaId}/unvote', '\App\Modules\Idelist\Controllers\VoteController@unvote');
    $router->post('/idelist/{ideaId}/comments', '\App\Modules\Idelist\Controllers\CommentController@store');
    $router->post('/idelist/comments/{commentId}/edit', '\App\Modules\Idelist\Controllers\CommentController@update');
    $router->post('/idelist/comments/{commentId}/delete', '\App\Modules\Idelist\Controllers\CommentController@destroy');
};
