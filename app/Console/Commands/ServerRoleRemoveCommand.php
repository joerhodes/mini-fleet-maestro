<?php

namespace App\Console\Commands;

use App\Actions\Servers\RemoveRolesFromServer;
use App\Models\Server;
use App\Models\ServerRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('server:role-remove {server} {roles*}')]
#[Description('Remove one or more roles from a server')]
class ServerRoleRemoveCommand extends Command
{
    public function handle(RemoveRolesFromServer $removeRolesFromServer): int
    {
        $name = $this->argument('server');
        $roleNames = $this->argument('roles');

        $serverValidator = Validator::make(
            ['server' => $name],
            ['server' => ['required', 'string', Rule::exists('servers', 'name')]],
            ['server.exists' => "The server [{$name}] was not found. Run `server:list` to see registered servers."],
        );

        if ($serverValidator->fails()) {
            $this->components->error('Unable to remove roles.');
            $this->components->bulletList($serverValidator->errors()->all());

            return self::FAILURE;
        }

        $server = Server::where('name', $name)->firstOrFail();

        $assignedRoles = ServerRole::where('server_id', $server->id)->pluck('role');

        $rolesValidator = Validator::make(
            ['roles' => $roleNames],
            [
                'roles' => ['array'],
                'roles.*' => ['string', Rule::in($assignedRoles)],
            ],
            ['roles.*.in' => "That role is not assigned to server [{$name}]."],
        );

        if ($rolesValidator->fails()) {
            $this->components->error('Unable to remove roles.');
            $this->components->bulletList($rolesValidator->errors()->all());

            return self::FAILURE;
        }

        $removeRolesFromServer($server, $roleNames);

        $this->components->info('Removed ['.implode(', ', $roleNames)."] from server [{$server->name}].");

        return self::SUCCESS;
    }
}
