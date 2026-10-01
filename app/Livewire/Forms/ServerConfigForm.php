<?php

namespace App\Livewire\Forms;

use App\Actions\Servers\SetServerConfig;
use App\Actions\Servers\UpdateServerConfig;
use App\Models\Server;
use App\Models\ServerConfig;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class ServerConfigForm extends Form
{
    #[Locked]
    public ?int $serverId = null;

    #[Locked]
    public ?int $configId = null;

    public string $key = '';

    public string $value = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('server_configs', 'key')->where('server_id', $this->serverId)->ignore($this->configId),
            ],
            'value' => ['required', 'string'],
        ];
    }

    public function create(int $serverId): void
    {
        $this->resetValidation();

        $this->serverId = $serverId;
        $this->configId = null;
        $this->key = '';
        $this->value = '';
    }

    public function load(ServerConfig $config): void
    {
        $this->resetValidation();

        $this->serverId = $config->server_id;
        $this->configId = $config->id;
        $this->key = $config->key;
        $this->value = $config->value;
    }

    public function save(): ServerConfig
    {
        $this->validate();

        $server = Server::findOrFail($this->serverId);

        if ($this->configId === null) {
            return app(SetServerConfig::class)($server, $this->key, $this->value);
        }

        $config = $server->secrets()->findOrFail($this->configId);

        return app(UpdateServerConfig::class)($config, $this->key, $this->value);
    }
}
