<?php

namespace App\Console\Commands;

use App\Models\Server;
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

        $this->table(
            ['ID', 'Name', 'Hostname', 'SSH User', 'SSH Port', 'Status'],
            $servers->map(fn (Server $server) => [
                $server->id,
                $server->name,
                $server->hostname,
                $server->ssh_user,
                $server->ssh_port,
                $server->status->label(),
            ]),
        );

        return self::SUCCESS;
    }
}
