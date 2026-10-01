<?php

use App\Actions\Servers\DeleteServer;
use App\Models\Server;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $deletingId = null;

    /**
     * @return Collection<int, Server>
     */
    #[Computed]
    public function servers(): Collection
    {
        return Server::orderBy('name')->get();
    }

    #[Computed]
    public function deletingServer(): ?Server
    {
        return $this->deletingId === null ? null : Server::withCount(['roles', 'secrets'])->find($this->deletingId);
    }

    public function confirmDelete(int $serverId): void
    {
        $this->deletingId = Server::findOrFail($serverId)->id;

        Flux::modal('delete-server')->show();
    }

    public function delete(): void
    {
        $server = $this->deletingServer;

        if ($server !== null) {
            app(DeleteServer::class)($server);

            Flux::toast("Server [{$server->name}] deleted.");
        }

        $this->deletingId = null;
        unset($this->servers, $this->deletingServer);

        Flux::modal('delete-server')->close();
    }
};
?>

<div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
    <flux:card class="space-y-5 h-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">Server List</flux:heading>
            </div>
            <flux:button icon="plus" :href="route('servers.create')" wire:navigate>Add Server</flux:button>
        </div>
        @if($this->servers->isEmpty())
            <flux:text>No servers have been added yet.</flux:text>
        @else
            <flux:table bleed>
                <flux:table.columns>
                    <flux:table.column>Server</flux:table.column>
                    <flux:table.column>Hostname</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                @foreach($this->servers as $server)
                    <flux:table.row wire:key="server-{{ $server->id }}">
                        <flux:table.cell>{{ $server->name }}</flux:table.cell>
                        <flux:table.cell>{{ $server->hostname }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$server->status->color()">{{ $server->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button size="sm" :href="route('servers.edit', $server)" wire:navigate>Edit</flux:button>
                                <flux:button size="sm" variant="danger" wire:click="confirmDelete({{ $server->id }})">Delete</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table>
        @endif
    </flux:card>

    <flux:modal name="delete-server" class="md:w-96">
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">Delete server?</flux:heading>
                <flux:text>
                    You are about to delete <strong>{{ $this->deletingServer?->name }}</strong>.
                </flux:text>
                <flux:text>
                    The server will be detached from all roles
                    ({{ $this->deletingServer?->roles_count ?? 0 }}) and all of its configuration
                    ({{ $this->deletingServer?->secrets_count ?? 0 }}) will also be deleted.
                    This cannot be undone.
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">Delete server</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
