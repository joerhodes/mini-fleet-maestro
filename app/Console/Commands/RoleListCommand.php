<?php

namespace App\Console\Commands;

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
        $listing = $this->roles->listing();

        if ($listing->isEmpty()) {
            $this->info('No Ansible roles discovered.');

            return self::SUCCESS;
        }

        $rows = $listing->map(fn (array $role) => $role['registered']
            ? [$role['role'], 'Yes', $role['label'], $role['alwaysApply'] ? 'Yes' : 'No']
            : [$role['role'], 'No', '—', '—']);

        $this->table(['Role', 'Registered', 'Label', 'Always Apply'], $rows);

        return self::SUCCESS;
    }
}
