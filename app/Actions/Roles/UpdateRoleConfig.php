<?php

namespace App\Actions\Roles;

use App\Models\RoleConfig;

class UpdateRoleConfig
{
    public function __invoke(RoleConfig $config, string $key, string $value): RoleConfig
    {
        $config->update(['key' => $key, 'value' => $value]);

        return $config;
    }
}
