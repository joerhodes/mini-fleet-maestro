<?php

namespace App\Actions\Ansible;

use App\Models\Role;
use App\Models\Server;
use App\Traits\RoleSets;
use App\Traits\RoleVars;
use Illuminate\Support\Collection;

class BuildInventory
{
    use RoleSets;
    use RoleVars;

    /**
     * Build the contents of Ansible's `all` group for the given servers.
     *
     * Every server is listed under `hosts` with its connection vars and secrets, so servers
     * with no assigned roles still receive always_apply roles. Each assigned role becomes a
     * child group whose `hosts` names only the given servers that have that role.
     *
     * @param  Collection<int, Server>  $servers
     * @return array{
     *     vars?: array<string, mixed>,
     *     hosts?: array<string, array<string, mixed>>,
     *     children?: array<string, array{vars: array<string, mixed>, hosts: array<string, null>}>
     * }
     */
    public function handle(Collection $servers): array
    {
        if ($servers->isEmpty()) {
            return [];
        }

        $all = [];

        $commonRoles = Role::where('always_apply', true)->get();
        $commonVars = $this->buildRoleVars($commonRoles);

        if ($commonVars) {
            $all['vars'] = $commonVars;
        }

        $all['hosts'] = $servers->mapWithKeys(function (Server $server) {
            return [$server->name => $this->varsForServer($server)];
        })->all();

        $assignedRoles = $this->roleSetFor($servers)->sortBy('role');

        if ($assignedRoles->isNotEmpty()) {
            $all['children'] = $assignedRoles->mapWithKeys(function (Role $role) use ($servers) {
                $memberServers = $servers->filter(function (Server $server) use ($role) {
                    return $server->roles->contains('role', $role->role);
                });

                return [$role->role => [
                    'vars' => $this->buildRoleVars(collect([$role])),
                    'hosts' => $memberServers->mapWithKeys(function (Server $server) {
                        return [$server->name => null];
                    })->all(),
                ]];
            })->all();
        }

        return $all;
    }

    /**
     * @return array<string, mixed>
     */
    protected function varsForServer(Server $server): array
    {
        return array_merge([
            'ansible_host' => $server->hostname,
            'ansible_user' => $server->ssh_user,
            'ansible_port' => $server->ssh_port,
            'app_roles' => $this->roleSetFor(collect([$server]), true)->pluck('role')->toArray(),
        ], $server->secrets->pluck('value', 'key')->toArray());
    }
}
