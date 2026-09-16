<?php

namespace App\Console\Commands;

use App\Actions\Servers\RegisterServer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Signature('server:add {name} {hostname} {ssh-user} {--ssh-port=22}')]
#[Description('Register a new server')]
class ServerAddCommand extends Command
{
    public function handle(RegisterServer $registerServer): int
    {
        $validator = Validator::make(
            [
                'name' => $this->argument('name'),
                'hostname' => $this->argument('hostname'),
                'ssh_user' => $this->argument('ssh-user'),
                'ssh_port' => $this->option('ssh-port'),
            ],
            [
                'name' => ['required', 'string', 'max:255', Rule::unique('servers', 'name')],
                'hostname' => ['required', 'string', 'max:255'],
                'ssh_user' => ['required', 'string', 'max:255'],
                'ssh_port' => ['required', 'integer', 'between:1,65535'],
            ],
        );

        if ($validator->fails()) {
            $this->components->error('Unable to add server.');
            $this->components->bulletList($validator->errors()->all());

            return self::FAILURE;
        }

        $data = $validator->validated();

        $server = $registerServer($data['name'], $data['hostname'], $data['ssh_user'], (int) $data['ssh_port']);

        $this->components->info("Added server [{$server->name}].");

        return self::SUCCESS;
    }
}
