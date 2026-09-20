<?php

namespace App\Console\Commands;

use App\Actions\Servers\DeleteServerConfig;
use App\Models\Server;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('server:config-delete {server} {key}')]
#[Description('Delete a configuration value for a server')]
class ServerConfigDeleteCommand extends Command
{
    public function handle(DeleteServerConfig $deleteServerConfig): int
    {
        $name = $this->argument('server');
        $key = $this->argument('key');

        $validator = Validator::make(
            [
                'server' => $name,
                'key' => $key,
            ],
            [
                'server' => ['required', 'string', Rule::exists('servers', 'name')],
                'key' => ['required', 'string', 'max:255'],
            ],
            [
                'server.exists' => "The server [{$name}] was not found. Run `server:list` to see registered servers.",
            ],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to delete server configuration.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $data = $validator->validated();

        $server = Server::where('name', $data['server'])->firstOrFail();

        $deleted = $deleteServerConfig($server, $data['key']);

        if (! $deleted) {
            $this->components->error("No config [{$data['key']}] found for server [{$server->name}].");

            return self::FAILURE;
        }

        $this->components->info("Deleted config [{$data['key']}] for server [{$server->name}].");

        return self::SUCCESS;
    }
}
