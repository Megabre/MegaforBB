<?php

declare(strict_types=1);

return static function (\Forecor\Core\Router $router): void {
    $admin = env('ADMIN_PATH', 'admin');
    $router->get('/' . $admin . '/idelist', '\App\Modules\Idelist\Controllers\Admin\IdeaAdminController@index');
    $router->get('/' . $admin . '/idelist/{id}/status', '\App\Modules\Idelist\Controllers\Admin\IdeaAdminController@editStatus');
    $router->post('/' . $admin . '/idelist/{id}/status', '\App\Modules\Idelist\Controllers\Admin\IdeaAdminController@updateStatus');
    $router->post('/' . $admin . '/idelist/{id}/delete', '\App\Modules\Idelist\Controllers\Admin\IdeaAdminController@destroy');
    $router->post('/' . $admin . '/idelist/{id}/pin', '\App\Modules\Idelist\Controllers\Admin\IdeaAdminController@togglePin');
    $router->get('/' . $admin . '/idelist/categories', '\App\Modules\Idelist\Controllers\Admin\CategoryAdminController@index');
    $router->post('/' . $admin . '/idelist/categories', '\App\Modules\Idelist\Controllers\Admin\CategoryAdminController@store');
    $router->post('/' . $admin . '/idelist/categories/{id}', '\App\Modules\Idelist\Controllers\Admin\CategoryAdminController@update');
    $router->post('/' . $admin . '/idelist/categories/{id}/delete', '\App\Modules\Idelist\Controllers\Admin\CategoryAdminController@destroy');
    $router->get('/' . $admin . '/idelist/settings', '\App\Modules\Idelist\Controllers\Admin\SettingsAdminController@index');
    $router->post('/' . $admin . '/idelist/settings', '\App\Modules\Idelist\Controllers\Admin\SettingsAdminController@update');
    $router->get('/' . $admin . '/idelist/statuses', '\App\Modules\Idelist\Controllers\Admin\StatusAdminController@index');
    $router->post('/' . $admin . '/idelist/statuses', '\App\Modules\Idelist\Controllers\Admin\StatusAdminController@store');
    $router->post('/' . $admin . '/idelist/statuses/{id}', '\App\Modules\Idelist\Controllers\Admin\StatusAdminController@update');
    $router->post('/' . $admin . '/idelist/statuses/{id}/delete', '\App\Modules\Idelist\Controllers\Admin\StatusAdminController@destroy');
};
