<?php

namespace App\Console\Commands;

use App\Actions\Ansible\BuildServerVars;
use App\Models\Server;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('ansible:vars {server} {--output=}')]
#[Description('Output the merged Ansible variables for a server')]
class AnsibleVarsCommand extends Command
{
    public function handle(BuildServerVars $buildServerVars): int
    {
        $name = $this->argument('server');

        $validator = Validator::make(
            ['server' => $name],
            ['server' => ['required', 'string', Rule::exists('servers', 'name')]],
            ['server.exists' => "The server [{$name}] was not found. Run `server:list` to see registered servers."],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to build Ansible vars.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $server = Server::where('name', $name)->firstOrFail();

        $vars = $buildServerVars->handle($server);

        foreach ($buildServerVars->overriddenKeys as $key) {
            fwrite(STDERR, "Warning: server_config [{$key}] overrides a role_config value of the same name.".PHP_EOL);
        }

        $json = json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($output = $this->option('output')) {
            File::put($output, $json.PHP_EOL);

            $this->components->info("Wrote Ansible vars for [{$server->name}] to [{$output}].");

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }
}
