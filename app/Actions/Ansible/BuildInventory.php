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

    public function handle(Collection $servers): array
    {
        if (!$servers->count()) {
            return [];
        }

        $all = [];

        $commonRoles = Role::where('always_apply', true)->get();
        $commonVars = $this->buildRoleVars($commonRoles);

        if ($commonVars) {
            $all['vars'] = $commonVars;
        }

        $assignedRoles = $this->roleSetFor($servers);
        $roles = Role::whereIn('role', $assignedRoles->pluck('role')->toArray())->get();

        if ($roles->count()) {
            $all['children'] = $roles->map(function ($role) {
                return [$role->role => [
                    'vars' => $this->buildRoleVars(collect([$role])),
                    'hosts' => $role->servers->map(function ($server) {
                        return [$server->name => $this->varsForServer($server)];
                    })->toArray()
                ]];
            })->toArray();
        }

        return $all;
    }

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
