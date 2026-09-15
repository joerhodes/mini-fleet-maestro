<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\AnsibleRoleRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('role:list')]
#[Description('List discovered Ansible roles and their registration status')]
class RoleListCommand extends Command
{
    public function __construct(private readonly AnsibleRoleRegistry $roles)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $discovered = $this->roles->discovered()->sort()->values();

        if ($discovered->isEmpty()) {
            $this->info('No Ansible roles discovered.');

            return self::SUCCESS;
        }

        $unregistered = $this->roles->unregistered();
        $registered = Role::whereIn('role', $discovered)->get()->keyBy('role');

        $rows = $discovered->map(function (string $role) use ($unregistered, $registered) {
            if ($unregistered->contains($role)) {
                return [$role, 'No', '—', '—'];
            }

            $model = $registered->get($role);

            return [$role, 'Yes', $model->label, $model->always_apply ? 'Yes' : 'No'];
        });

        $this->table(['Role', 'Registered', 'Label', 'Always Apply'], $rows);

        return self::SUCCESS;
    }
}
