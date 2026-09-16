<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('server:list')]
#[Description('List registered servers')]
class ServerListCommand extends Command
{
    public function handle(): int
    {
        $servers = Server::orderBy('name')->get();

        if ($servers->isEmpty()) {
            $this->components->info('No servers registered.');

            return self::SUCCESS;
        }

        $roleLabels = Role::pluck('label', 'role');

        $alwaysApplyRoles = Role::where('always_apply', true)->pluck('label');

        $assignedRolesByServer = ServerRole::whereIn('server_id', $servers->pluck('id'))
            ->get()
            ->groupBy('server_id')
            ->map(fn ($rows) => $rows->pluck('role')->map(fn (string $role) => $roleLabels->get($role)));

        $this->table(
            ['ID', 'Name', 'Hostname', 'SSH User', 'SSH Port', 'Status', 'Roles'],
            $servers->map(function (Server $server) use ($alwaysApplyRoles, $assignedRolesByServer) {
                $roles = $assignedRolesByServer->get($server->id, collect())
                    ->merge($alwaysApplyRoles)
                    ->unique()
                    ->sort()
                    ->values();

                return [
                    $server->id,
                    $server->name,
                    $server->hostname,
                    $server->ssh_user,
                    $server->ssh_port,
                    $server->status->label(),
                    $roles->isEmpty() ? '—' : $roles->implode(', '),
                ];
            }),
        );

        return self::SUCCESS;
    }
}
