<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class AnsibleRoleRegistry
{
    public function discovered(): Collection
    {
        return collect(File::directories(config('ansible.paths.roles')))
            ->filter(fn (string $dir) => File::exists($dir.'/tasks/main.yml'))
            ->map(fn (string $dir) => basename($dir))
            ->values();
    }

    public function registered(): Collection
    {
        return Role::pluck('role');
    }

    public function unregistered(): Collection
    {
        return $this->discovered()->diff($this->registered())->values();
    }

    public function assignable(): Collection
    {
        return Role::where('always_apply', false)->pluck('role');
    }
}
