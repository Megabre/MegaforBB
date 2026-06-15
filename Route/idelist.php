<?php

declare(strict_types=1);

use App\Modules\Idelist\Models\IdelistSetting;

return function (\Forecor\Core\Router $router): void {
    $enabled = app()?->cache()->get('idelist.enabled');
    if ($enabled === null) {
        $enabled = IdelistSetting::isEnabled(true);
        app()?->cache()->set('idelist.enabled', $enabled, 60);
    }

    if (!$enabled) {
        $router->get('/idelist', '\App\Modules\Idelist\Controllers\IdeaController@disabled');
        $router->get('/idelist/{*path}', '\App\Modules\Idelist\Controllers\IdeaController@disabled');
        $router->post('/idelist/{*path}', '\App\Modules\Idelist\Controllers\IdeaController@disabled');
        return;
    }

    $registerPublic = require __DIR__ . '/idelist_public.php';
    $registerPublic($router);
    $registerAdmin = require __DIR__ . '/idelist_admin.php';
    $registerAdmin($router);
};
