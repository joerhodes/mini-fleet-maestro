<?php

namespace App\Actions\Servers;

use App\Models\Server;

class DeleteServerConfig
{
    public function __invoke(Server $server, string $key): bool
    {
        return (bool) $server->secrets()->where('key', $key)->delete();
    }
}
