<?php

namespace App\Console\Commands;

use App\Models\RoleConfig;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('role:config-list {role}')]
#[Description('List the configuration values stored for a role')]
class RoleConfigListCommand extends Command
{
    public function handle(): int
    {
        $role = $this->argument('role');

        $validator = Validator::make(
            ['role' => $role],
            ['role' => ['required', 'string', Rule::exists('roles', 'role')]],
            ['role.exists' => "The role [{$role}] is not registered. Run `role:register` first."],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to list role configuration.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $configs = RoleConfig::where('role', $role)->orderBy('key')->get();

        if ($configs->isEmpty()) {
            $this->components->info("No configuration found for role [{$role}].");

            return self::SUCCESS;
        }

        $this->table(
            ['Key', 'Value'],
            $configs->map(fn (RoleConfig $config) => [$config->key, $config->value]),
        );

        return self::SUCCESS;
    }
}
