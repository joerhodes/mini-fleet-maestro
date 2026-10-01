<?php

use App\Actions\Servers\DeleteServerConfig;
use App\Livewire\Forms\ServerConfigForm;
use App\Models\Server;
use App\Models\ServerConfig;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $serverId = null;

    public ServerConfigForm $form;

    public function mount(?Server $server = null): void
    {
        $this->serverId = $server?->id;
    }

    /**
     * @return Collection<int, ServerConfig>
     */
    #[Computed]
    public function configs(): Collection
    {
        return ServerConfig::where('server_id', $this->serverId)->orderBy('key')->get();
    }

    public function edit(string $key): void
    {
        abort_if($this->serverId === null, 404);

        $config = ServerConfig::where('server_id', $this->serverId)->where('key', $key)->firstOrFail();

        $this->form->load($config);

        Flux::modal('edit-server-config')->show();
    }

    public function create(): void
    {
        abort_if($this->serverId === null, 404);

        $this->form->create($this->serverId);

        Flux::modal('edit-server-config')->show();
    }

    public function save(): void
    {
        $this->form->save();

        unset($this->configs);

        Flux::modal('edit-server-config')->close();
        Flux::toast('Configuration saved.', variant: 'success');
    }

    public function delete(string $key): void
    {
        abort_if($this->serverId === null, 404);

        app(DeleteServerConfig::class)(Server::findOrFail($this->serverId), $key);

        unset($this->configs);

        Flux::toast('Configuration deleted.');
    }
};
?>

<div class="space-y-5">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">Configuration</flux:heading>

        @if($serverId !== null)
            <flux:button size="sm" icon="plus" wire:click="create">Add Configuration</flux:button>
        @endif
    </div>

    @if($serverId === null)
        <flux:text>Save this server before managing its configuration.</flux:text>
    @elseif($this->configs->isEmpty())
        <flux:text>No configuration found for this server.</flux:text>
    @else
        <flux:table bleed>
            <flux:table.columns>
                <flux:table.column>Key</flux:table.column>
                <flux:table.column>Value</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            @foreach($this->configs as $config)
                <flux:table.row wire:key="config-{{ $config->id }}">
                    <flux:table.cell>{{ $config->key }}</flux:table.cell>
                    <flux:table.cell>••••••••</flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="edit(@js($config->key))">Edit</flux:button>
                            <flux:button
                                size="sm"
                                variant="danger"
                                wire:click="delete(@js($config->key))"
                                wire:confirm="Delete the configuration value [{{ $config->key }}]?"
                            >
                                Delete
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table>
    @endif

    <flux:modal name="edit-server-config" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->configId === null ? 'Add configuration' : 'Edit configuration' }}</flux:heading>
            </div>

            <flux:input wire:model="form.key" label="Key" />

            <flux:input wire:model="form.value" label="Value" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
