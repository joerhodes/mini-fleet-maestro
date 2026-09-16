<?php

namespace App\Actions\Servers;

use App\Models\Server;
use App\Models\ServerRole;
use Illuminate\Support\Collection;

class AssignRolesToServer
{
    /**
     * @param  array<int, string>  $roles
     * @return Collection<int, ServerRole>
     */
    public function __invoke(Server $server, array $roles): Collection
    {
        return collect($roles)->map(
            fn (string $role) => ServerRole::firstOrCreate([
                'server_id' => $server->id,
                'role' => $role,
            ]),
        );
    }
}
