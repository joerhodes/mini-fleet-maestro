<?php

namespace App\Console\Commands;

use App\Actions\Roles\DeleteRoleConfig;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('role:config-delete {role} {key}')]
#[Description('Delete a configuration value for a role')]
class RoleConfigDeleteCommand extends Command
{
    public function handle(DeleteRoleConfig $deleteRoleConfig): int
    {
        $role = $this->argument('role');
        $key = $this->argument('key');

        $validator = Validator::make(
            [
                'role' => $role,
                'key' => $key,
            ],
            [
                'role' => ['required', 'string', Rule::exists('roles', 'role')],
                'key' => ['required', 'string', 'max:255'],
            ],
            [
                'role.exists' => "The role [{$role}] is not registered. Run `role:register` first.",
            ],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to delete role configuration.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $data = $validator->validated();

        $deleted = $deleteRoleConfig($data['role'], $data['key']);

        if (! $deleted) {
            $this->components->error("No config [{$data['key']}] found for role [{$data['role']}].");

            return self::FAILURE;
        }

        $this->components->info("Deleted config [{$data['key']}] for role [{$data['role']}].");

        return self::SUCCESS;
    }
}
