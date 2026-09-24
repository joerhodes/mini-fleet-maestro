<?php

namespace App\Actions\Ansible;

use Illuminate\Support\Collection;
use RuntimeException;


class BuildRoleVars
{
    /**
     * @param Collection<int, Role> $roles
     * @return array<string, mixed>
     */
    public function handle(Collection $roles): array
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
