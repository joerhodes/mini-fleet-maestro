<?php

namespace App\Actions\Roles;

use App\Models\RoleConfig;

class DeleteRoleConfig
{
    public function __invoke(string $role, string $key): bool
    {
        return (bool) RoleConfig::where('role', $role)->where('key', $key)->delete();
    }
}
