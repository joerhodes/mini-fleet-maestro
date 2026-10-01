<?php

use App\Livewire\Forms\RoleForm;
use Flux\Flux;
use Livewire\Component;

new class extends Component
{
    public RoleForm $form;

    public function mount(string $role): void
    {
        $this->form->load($role);
    }

    public function save(): void
    {
        $this->form->save();

        $this->dispatch('role-saved');

        Flux::toast('Role saved.', variant: 'success');
    }
};
?>

<form wire:submit="save" class="space-y-6">
    <div>
        <flux:heading size="xl">{{ $form->isRegistered ? 'Edit role' : 'Register role' }}</flux:heading>
        <flux:subheading>{{ $form->role }}</flux:subheading>
    </div>

    <flux:input wire:model="form.label" label="Label" class="max-w-md" />

    <flux:switch
        wire:model="form.alwaysApply"
        label="Always apply"
        description="Apply this role to every server, regardless of assignment."
    />

    <div class="flex gap-2">
        <flux:button variant="filled" :href="route('roles.index')" wire:navigate>Cancel</flux:button>
        <flux:button type="submit" variant="primary">Save</flux:button>
    </div>
</form>
