<?php

namespace App\Console\Commands;

use App\Actions\Roles\SetRoleConfig;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('role:config {role} {key} {value}')]
#[Description('Set a configuration value for a role')]
class RoleConfigCommand extends Command
{
    public function handle(SetRoleConfig $setRoleConfig): int
    {
        $role = $this->argument('role');

        $validator = Validator::make(
            [
                'role' => $role,
                'key' => $this->argument('key'),
                'value' => $this->argument('value'),
            ],
            [
                'role' => ['required', 'string', Rule::exists('roles', 'role')],
                'key' => ['required', 'string', 'max:255'],
                'value' => ['required', 'string'],
            ],
            [
                'role.exists' => "The role [{$role}] is not registered. Run `role:register` first.",
            ],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to set role configuration.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $data = $validator->validated();

        $config = $setRoleConfig($data['role'], $data['key'], $data['value']);

        $this->components->info("Set config [{$config->key}] for role [{$config->role}].");

        return self::SUCCESS;
    }
}
