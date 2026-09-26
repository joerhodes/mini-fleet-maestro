<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Support\Collection;

trait RoleSets
{
    public function roleSetFor(Collection $servers, bool $includeAlwaysApplyRoles = false): Collection
    {
        $roles = collect([]);

        if ($includeAlwaysApplyRoles) {
            $roles = Role::where('always_apply', true)->get();
        }

        $roles = $roles->merge($servers->flatMap(function ($server) {
            return $server->roles;
        })->unique());

        return $roles;
    }
}
