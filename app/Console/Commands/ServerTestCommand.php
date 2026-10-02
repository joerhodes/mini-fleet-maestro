<?php

namespace App\Console\Commands;

use App\Actions\Servers\TestServerConnectivity;
use App\Enums\ServerStatus;
use App\Traits\ResolvesServers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('server:test {server}')]
#[Description('Step a server through the ping, SSH, and Ansible connectivity ladder')]
class ServerTestCommand extends Command
{
    use ResolvesServers;

    public function handle(TestServerConnectivity $testServerConnectivity): int
    {
        $servers = $this->resolveServers([$this->argument('server')], 'Unable to test server.');

        if ($servers === null) {
            return self::FAILURE;
        }

        $server = $testServerConnectivity($servers->first());

        $this->components->twoColumnDetail($server->name, $server->status->label());

        if ($server->last_check_output) {
            $this->line($server->last_check_output);
        }

        if ($server->status === ServerStatus::Ready) {
            $this->components->info("Server [{$server->name}] is fully reachable.");

            return self::SUCCESS;
        }

        $this->components->error("Server [{$server->name}] stopped at [{$server->status->label()}].");

        return self::FAILURE;
    }
}
