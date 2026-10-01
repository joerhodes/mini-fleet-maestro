<?php

use App\Livewire\Forms\ServerForm;
use App\Models\Server;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public ServerForm $form;

    public function mount(?Server $server = null): void
    {
        $this->form->load($server);
    }

    public function save(): void
    {
        $isNew = $this->form->serverId === null;

        $server = $this->form->save();

        Flux::toast('Server saved.', variant: 'success');

        if ($isNew) {
            $this->redirectRoute('servers.edit', $server, navigate: true);
        }
    }
};
?>

<form wire:submit="save" class="space-y-6">
    <div>
        <flux:heading size="xl">{{ $form->serverId === null ? 'Add server' : 'Edit server' }}</flux:heading>
        @if($form->serverId !== null)
            <flux:subheading>{{ $form->name }}</flux:subheading>
        @endif
    </div>

    <flux:input wire:model="form.name" label="Name" class="max-w-md" />

    <flux:input wire:model="form.hostname" label="Hostname" class="max-w-md" />

    <div class="flex max-w-md gap-4">
        <flux:input wire:model="form.sshUser" label="SSH user" class="flex-1" />
        <flux:input wire:model="form.sshPort" label="SSH port" type="number" min="1" max="65535" class="w-28" />
    </div>

    <flux:textarea wire:model="form.notes" label="Notes" class="max-w-md" />

    <div class="flex gap-2">
        <flux:button variant="filled" :href="route('servers.index')" wire:navigate>Cancel</flux:button>
        <flux:button type="submit" variant="primary">Save</flux:button>
    </div>
</form>
