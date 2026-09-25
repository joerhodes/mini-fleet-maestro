<?php

namespace App\Actions\Ansible;

use App\Models\Role;
use App\Models\Server;
use App\Traits\RoleSets;
use App\Traits\RoleVars;
use Illuminate\Support\Collection;

class BuildServerVars
{
    use RoleSets;
    use RoleVars;

    /**
     * server_config keys that overrode a role_config value of the same name during the last handle() call.
     *
     * @var array<int, string>
     */
    public array $overriddenKeys = [];

    /**
     * @return array<string, mixed>
     */
    public function handle(Server $server): array
    {
        $this->overriddenKeys = [];

        $roles = $this->roleSetFor(collect([$server]), true);

        $vars = $this->buildRoleVars($roles);

        $serverVars = $server->secrets->pluck('value', 'key')->all();

        $this->overriddenKeys = array_values(array_intersect(array_keys($vars), array_keys($serverVars)));

        $vars = array_merge($vars, $serverVars);

        $vars['app_roles'] = $roles->pluck('role')->sort()->values()->all();

        return $vars;
    }

//     /**
//      * The union of this server's assigned roles and every always_apply role.
//      *
//      * @return Collection<int, Role>
//      */
//     private function roleSetFor(Server $server): Collection
//     {
//         $assignedRoleNames = $server->roles->pluck('role');
//
//         $assignedRoles = Role::whereIn('role', $assignedRoleNames)->with('roleConfigs')->get();
//
//         $alwaysApplyRoles = Role::where('always_apply', true)->with('roleConfigs')->get();
//
//         return $assignedRoles->merge($alwaysApplyRoles)->unique('role')->values();
//     }
}
