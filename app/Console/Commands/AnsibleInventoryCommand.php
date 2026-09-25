<?php

namespace App\Console\Commands;

use App\Actions\Ansible\BuildInventory;
use App\Models\Server;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('ansible:inventory {servers?* : Server names (default: all)}')]
#[Description('Output the Ansible inventory for the given servers, or all servers')]
class AnsibleInventoryCommand extends Command
{
    public function handle(BuildInventory $buildInventory): int
    {
        /** @var array<int, string> $names */
        $names = array_values(array_unique($this->argument('servers')));

        $servers = Server::with(['roles.roleConfigs', 'secrets'])
            ->when($names, fn ($query) => $query->whereIn('name', $names))
            ->orderBy('name')
            ->get();

        $unknownNames = array_values(array_diff($names, $servers->pluck('name')->all()));

        if ($unknownNames) {
            $this->components->error('Unable to build Ansible inventory.');
            $this->components->bulletList(array_map(
                fn (string $name) => "The server [{$name}] was not found.",
                $unknownNames,
            ));
            $this->components->info('Run `server:list` to see registered servers.');

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
