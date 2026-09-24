<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Support\Collection;
use RuntimeException;

trait RoleVars
{
    /**
     * @param Collection<int, Role> $roles
     * @return array<string, mixed>
     */
    public function buildRoleVars(Collection $roles): array
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
