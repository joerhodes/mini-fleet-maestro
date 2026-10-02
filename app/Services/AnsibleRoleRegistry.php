<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class AnsibleRoleRegistry
{
    public function discovered(): Collection
    {
        return collect(File::directories(config('maestro.ansible.paths.roles')))
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

    /**
     * Every discovered role, sorted by name, with its registration details.
     *
     * @return Collection<int, array{role: string, registered: bool, label: ?string, alwaysApply: ?bool}>
     */
    public function listing(): Collection
    {
        $registered = Role::whereIn('role', $this->discovered())->get()->keyBy('role');

        return $this->discovered()->sort()->values()->map(function (string $role) use ($registered) {
            $model = $registered->get($role);

            return [
                'role' => $role,
                'registered' => $model !== null,
                'label' => $model?->label,
                'alwaysApply' => $model?->always_apply,
            ];
        });
    }
}
