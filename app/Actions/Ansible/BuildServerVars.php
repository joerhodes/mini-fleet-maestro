<?php

namespace App\Actions\Ansible;

use App\Models\Role;
use App\Models\Server;
use Illuminate\Support\Collection;
use RuntimeException;

class BuildServerVars
{
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

        $roles = $this->roleSetFor($server);

        $vars = $this->roleConfigVars($roles);

        $serverVars = $server->secrets->pluck('value', 'key')->all();

        $this->overriddenKeys = array_values(array_intersect(array_keys($vars), array_keys($serverVars)));

        $vars = array_merge($vars, $serverVars);

        $vars['app_roles'] = $roles->pluck('role')->sort()->values()->all();

        return $vars;
    }

    /**
     * The union of this server's assigned roles and every always_apply role.
     *
     * @return Collection<int, Role>
     */
    private function roleSetFor(Server $server): Collection
    {
        $assignedRoleNames = $server->roles->pluck('role');

        $assignedRoles = Role::whereIn('role', $assignedRoleNames)->with('roleConfigs')->get();

        $alwaysApplyRoles = Role::where('always_apply', true)->with('roleConfigs')->get();

        return $assignedRoles->merge($alwaysApplyRoles)->unique('role')->values();
    }

    /**
     * @param  Collection<int, Role>  $roles
     * @return array<string, mixed>
     */
    private function roleConfigVars(Collection $roles): array
    {
        $vars = [];
        $sourceRole = [];

        foreach ($roles as $role) {
            foreach ($role->roleConfigs as $config) {
                if (array_key_exists($config->key, $vars) && $vars[$config->key] !== $config->value) {
                    throw new RuntimeException(
                        "Role config key [{$config->key}] is defined with conflicting values by roles [{$sourceRole[$config->key]}] and [{$role->role}]."
                    );
                }

                $vars[$config->key] = $config->value;
                $sourceRole[$config->key] = $role->role;
            }
        }

        return $vars;
    }
}
