<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerConfig;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('server:config-list {server}')]
#[Description('List the configuration values stored for a server')]
class ServerConfigListCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('server');

        $validator = Validator::make(
            ['server' => $name],
            ['server' => ['required', 'string', Rule::exists('servers', 'name')]],
            ['server.exists' => "The server [{$name}] was not found. Run `server:list` to see registered servers."],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to list server configuration.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $server = Server::where('name', $name)->firstOrFail();
        $configs = $server->secrets()->orderBy('key')->get();

        if ($configs->isEmpty()) {
            $this->components->info("No configuration found for server [{$name}].");

            return self::SUCCESS;
        }

        $this->table(
            ['Key', 'Value'],
            $configs->map(fn (ServerConfig $config) => [$config->key, $config->value]),
        );

        return self::SUCCESS;
    }
}
