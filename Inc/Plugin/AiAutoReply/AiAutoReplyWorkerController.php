<?php

declare(strict_types=1);

namespace Plugins\AiAutoReply;

use Forecor\Core\Application;

final class AiAutoReplyWorkerController
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function run(string $token): string
    {
        if (!PluginHooks::isValidWorkerToken($token)) {
            http_response_code(403);
            return 'forbidden';
        }

        $result = PluginHooks::runWorkerNow($this->app);
        header('Content-Type: application/json; charset=utf-8');

        return (string) json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
