<?php

namespace App\Actions\Servers;

use App\Models\Server;
use App\Models\ServerRole;

class RemoveRolesFromServer
{
    /**
     * @param  array<int, string>  $roles
     */
    public function __invoke(Server $server, array $roles): int
    {
        return ServerRole::where('server_id', $server->id)
            ->whereIn('role', $roles)
            ->delete();
    }
}
