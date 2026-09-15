<?php

namespace App\Actions\Roles;

use App\Models\RoleConfig;

class SetRoleConfig
{
    public function __invoke(string $role, string $key, string $value): RoleConfig
    {
        return RoleConfig::updateOrCreate(
            ['role' => $role, 'key' => $key],
            ['value' => $value],
        );
    }
}
