<?php

use App\Actions\Roles\DeleteRoleConfig;
use App\Livewire\Forms\RoleConfigForm;
use App\Models\Role;
use App\Models\RoleConfig;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $role = '';

    public RoleConfigForm $form;

    public function mount(string $role): void
    {
        $this->role = $role;
    }

    #[Computed]
    public function isRegistered(): bool
    {
        return Role::whereKey($this->role)->exists();
    }

    /**
     * @return Collection<int, RoleConfig>
     */
    #[Computed]
    public function configs(): Collection
    {
        return RoleConfig::where('role', $this->role)->orderBy('key')->get();
    }

    #[On('role-saved')]
    public function refresh(): void
    {
        unset($this->isRegistered, $this->configs);
    }

    public function edit(string $key): void
    {
        $config = RoleConfig::where('role', $this->role)->where('key', $key)->firstOrFail();

        $this->form->load($config);

        Flux::modal('edit-role-config')->show();
    }

    public function create(): void
    {
        abort_unless($this->isRegistered, 404);

        $this->form->create($this->role);

        Flux::modal('edit-role-config')->show();
    }

    public function save(): void
    {
        $this->form->save();

        unset($this->configs);

        Flux::modal('edit-role-config')->close();
        Flux::toast('Configuration saved.', variant: 'success');
    }

    public function delete(string $key): void
    {
        app(DeleteRoleConfig::class)($this->role, $key);

        unset($this->configs);

        Flux::toast('Configuration deleted.');
    }
};
?>

<div class="space-y-5">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">Configuration</flux:heading>

        @if($this->isRegistered)
            <flux:button size="sm" icon="plus" wire:click="create">Add Configuration</flux:button>
        @endif
    </div>

    @if(! $this->isRegistered)
        <flux:text>Save this role to register it before managing its configuration.</flux:text>
    @elseif($this->configs->isEmpty())
        <flux:text>No configuration found for this role.</flux:text>
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

    <flux:modal name="edit-role-config" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $form->configId === null ? 'Add configuration' : 'Edit configuration' }}</flux:heading>
                <flux:subheading>{{ $role }}</flux:subheading>
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
