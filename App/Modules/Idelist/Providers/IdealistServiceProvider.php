<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Providers;

use App\Modules\Idelist\Models\IdelistSetting;

class IdealistServiceProvider
{
    public function register(): void
    {
        // Forecor's container autowires services on demand.
    }

    public function boot(): void
    {
        $app = app();
        if (!$app) {
            return;
        }
        $enabled = $app->cache()->get('idelist.enabled');
        if ($enabled === null) {
            $enabled = IdelistSetting::isEnabled(true);
            $app->cache()->set('idelist.enabled', $enabled, 60);
        }
    }
}
