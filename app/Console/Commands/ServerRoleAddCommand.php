<?php

namespace App\Console\Commands;

use App\Actions\Servers\AssignRolesToServer;
use App\Models\Server;
use App\Services\AnsibleRoleRegistry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('server:role-add {server} {roles*}')]
#[Description('Assign one or more roles to a server')]
class ServerRoleAddCommand extends Command
{
    public function __construct(private readonly AnsibleRoleRegistry $roles)
    {
        parent::__construct();
    }

    public function handle(AssignRolesToServer $assignRolesToServer): int
    {
        $name = $this->argument('server');
        $roleNames = $this->argument('roles');

        $validator = Validator::make(
            [
                'server' => $name,
                'roles' => $roleNames,
            ],
            [
                'server' => ['required', 'string', Rule::exists('servers', 'name')],
                'roles' => ['array'],
                'roles.*' => ['string', Rule::in($this->roles->assignable())],
            ],
            [
                'server.exists' => "The server [{$name}] was not found. Run `server:list` to see registered servers.",
                'roles.*.in' => 'Each role must be a registered, assignable role. Run `role:list` to see available roles.',
            ],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to assign roles.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $server = Server::where('name', $name)->firstOrFail();

        $assignRolesToServer($server, $roleNames);

        $this->components->info('Assigned ['.implode(', ', $roleNames)."] to server [{$server->name}].");

        return self::SUCCESS;
    }
}
