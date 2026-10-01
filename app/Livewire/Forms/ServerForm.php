<?php

namespace App\Livewire\Forms;

use App\Actions\Servers\SaveServer;
use App\Models\Server;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class ServerForm extends Form
{
    #[Locked]
    public ?int $serverId = null;

    public string $name = '';

    public string $hostname = '';

    public string $sshUser = '';

    public string $sshPort = '22';

    public string $notes = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('servers', 'name')->ignore($this->serverId)],
            'hostname' => ['required', 'string', 'max:255'],
            'sshUser' => ['required', 'string', 'max:255'],
            'sshPort' => ['required', 'integer', 'between:1,65535'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function load(?Server $server): void
    {
        if ($server === null) {
            return;
        }

        $this->serverId = $server->id;
        $this->name = $server->name;
        $this->hostname = $server->hostname;
        $this->sshUser = $server->ssh_user;
        $this->sshPort = (string) $server->ssh_port;
        $this->notes = $server->notes ?? '';
    }

    public function save(): Server
    {
        $this->validate();

        $server = $this->serverId === null ? null : Server::findOrFail($this->serverId);

        $server = app(SaveServer::class)(
            $server,
            $this->name,
            $this->hostname,
            $this->sshUser,
            (int) $this->sshPort,
            $this->notes === '' ? null : $this->notes,
        );

        $this->serverId = $server->id;

        return $server;
    }
}
