<?php

namespace App\Actions\Servers;

use App\Enums\ServerStatus;
use App\Models\Server;

class RegisterServer
{
    public function __invoke(string $name, string $hostname, string $sshUser, int $sshPort = 22): Server
    {
        return Server::firstOrCreate(
            ['name' => $name],
            [
                'hostname' => $hostname,
                'ssh_user' => $sshUser,
                'ssh_port' => $sshPort,
                'status' => ServerStatus::Pending,
            ],
        );
    }
}
