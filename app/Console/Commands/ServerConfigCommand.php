<?php

namespace App\Console\Commands;

use App\Actions\Servers\SetServerConfig;
use App\Models\Server;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('server:config {server} {key} {value}')]
#[Description('Set a configuration value for a server')]
class ServerConfigCommand extends Command
{
    public function handle(SetServerConfig $setServerConfig): int
    {
        $name = $this->argument('server');

        $validator = Validator::make(
            [
                'server' => $name,
                'key' => $this->argument('key'),
                'value' => $this->argument('value'),
            ],
            [
                'server' => ['required', 'string', Rule::exists('servers', 'name')],
                'key' => ['required', 'string', 'max:255'],
                'value' => ['required', 'string'],
            ],
            [
                'server.exists' => "The server [{$name}] was not found. Run `server:list` to see registered servers.",
            ],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to set server configuration.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $data = $validator->validated();

        $server = Server::where('name', $data['server'])->firstOrFail();

        $config = $setServerConfig($server, $data['key'], $data['value']);

        $this->components->info("Set config [{$config->key}] for server [{$server->name}].");

        return self::SUCCESS;
    }
}
