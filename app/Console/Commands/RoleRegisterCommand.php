<?php

namespace App\Console\Commands;

use App\Actions\Roles\RegisterRole;
use App\Services\AnsibleRoleRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('role:register {role} {label} {--always-apply}')]
#[Description('Register a discovered Ansible role')]
class RoleRegisterCommand extends Command
{
    public function __construct(private readonly AnsibleRoleRegistry $roles)
    {
        parent::__construct();
    }

    public function handle(RegisterRole $registerRole): int
    {
        $role = $this->argument('role');

        $validator = Validator::make(
            [
                'role' => $role,
                'label' => $this->argument('label'),
            ],
            [
                'role' => ['required', 'string', Rule::in($this->roles->discovered()), Rule::unique('roles', 'role')],
                'label' => ['required', 'string', 'max:255'],
            ],
            [
                'role.in' => "The role [{$role}] was not discovered. Run `role:list` to see available roles.",
                'role.unique' => "The role [{$role}] is already registered.",
            ],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to register role.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $data = $validator->validated();

        $model = $registerRole($data['role'], $data['label'], (bool) $this->option('always-apply'));

        $this->components->info("Registered role [{$model->role}].");

        return self::SUCCESS;
    }
}
