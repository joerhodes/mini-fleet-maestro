<?php

namespace App\Actions\Servers;

use App\Models\Server;

class DeleteServer
{
    /**
     * Roles and configs are removed by the foreign keys' cascade-on-delete.
     */
    public function __invoke(Server $server): bool
    {
        return (bool) $server->delete();
    }
}
