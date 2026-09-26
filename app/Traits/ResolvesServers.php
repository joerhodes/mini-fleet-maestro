<?php

namespace App\Traits;

use App\Models\Server;
use Illuminate\Support\Collection;

trait ResolvesServers
{
    /**
     * Load the named servers (or every server when no names are given) with everything the
     * inventory needs. Reports every unknown name and returns null when any are missing.
     *
     * @param  array<int, string>  $names
     * @return Collection<int, Server>|null
     */
    protected function resolveServers(array $names, string $failureMessage): ?Collection
    {
        $names = array_values(array_unique($names));

        $servers = Server::with(['roles.roleConfigs', 'secrets'])
            ->when($names, fn ($query) => $query->whereIn('name', $names))
            ->orderBy('name')
            ->get();

        $unknownNames = array_values(array_diff($names, $servers->pluck('name')->all()));

        if ($unknownNames) {
            $this->components->error($failureMessage);
            $this->components->bulletList(array_map(
                fn (string $name) => "The server [{$name}] was not found.",
                $unknownNames,
            ));
            $this->components->info('Run `server:list` to see registered servers.');

            return null;
        }

        return $servers;
    }
}
