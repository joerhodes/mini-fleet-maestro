<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Support\Collection;

trait RoleSets
{
    public function roleSetFor(Collection $servers, bool $includeAlwaysApplyRoles = false): Collection
    {
        $roles = $servers->flatMap(function ($server) {
            return $server->roles;
        })->unique();

        if ($includeAlwaysApplyRoles) {
            $roles = $roles->merge(Role::where('always_apply', true)->get());
        }

        return $roles;
    }
}
