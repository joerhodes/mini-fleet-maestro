<?php

namespace App\Console\Commands;

use App\Actions\Ansible\BuildInventory;
use App\Traits\ResolvesServers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('ansible:inventory {servers?* : Server names (default: all)}')]
#[Description('Output the Ansible inventory for the given servers, or all servers')]
class AnsibleInventoryCommand extends Command
{
    use ResolvesServers;

    public function handle(BuildInventory $buildInventory): int
    {
        $servers = $this->resolveServers($this->argument('servers'), 'Unable to build Ansible inventory.');

        if ($servers === null) {
            return self::FAILURE;
        }

        if ($servers->isEmpty()) {
            $this->components->error('Unable to build Ansible inventory: no servers are registered.');

            return self::FAILURE;
        }

        try {
            $inventory = $buildInventory->handle($servers);
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line(json_encode(['all' => $inventory], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
