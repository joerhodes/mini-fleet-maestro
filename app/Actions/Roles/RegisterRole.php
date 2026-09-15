<?php

namespace App\Actions\Roles;

use App\Models\Role;

class RegisterRole
{
    public function __invoke(string $role, string $label, bool $alwaysApply = false): Role
    {
        return Role::create([
            'role' => $role,
            'label' => $label,
            'always_apply' => $alwaysApply,
        ]);
    }
}
