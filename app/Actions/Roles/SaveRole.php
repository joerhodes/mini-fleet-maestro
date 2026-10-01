<?php

namespace App\Actions\Roles;

use App\Models\Role;

class SaveRole
{
    public function __invoke(string $role, string $label, bool $alwaysApply): Role
    {
        return Role::updateOrCreate(
            ['role' => $role],
            ['label' => $label, 'always_apply' => $alwaysApply],
        );
    }
}
