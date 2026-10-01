<?php

namespace App\Actions\Servers;

use App\Enums\ServerStatus;
use App\Models\Server;

class SaveServer
{
    public function __invoke(
        ?Server $server,
        string $name,
        string $hostname,
        string $sshUser,
        int $sshPort,
        ?string $notes,
    ): Server {
        $server ??= new Server(['status' => ServerStatus::Pending]);

        $server->fill([
            'name' => $name,
            'hostname' => $hostname,
            'ssh_user' => $sshUser,
            'ssh_port' => $sshPort,
            'notes' => $notes,
        ]);

        if ($server->exists && $server->isDirty(['hostname', 'ssh_user', 'ssh_port'])) {
            $server->fill([
                'status' => ServerStatus::Pending,
                'last_checked_at' => null,
                'last_check_output' => null,
            ]);
        }

        $server->save();

        return $server;
    }
}
