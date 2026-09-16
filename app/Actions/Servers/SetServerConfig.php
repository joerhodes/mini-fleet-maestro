<?php

namespace App\Actions\Servers;

use App\Models\Server;
use App\Models\ServerConfig;

class SetServerConfig
{
    public function __invoke(Server $server, string $key, string $value): ServerConfig
    {
        return $server->secrets()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }
}
