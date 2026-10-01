<x-layouts::app :title="__('Edit Role')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:card class="space-y-6">
            <livewire:roles.role-form :role="$role" />

            <flux:separator />

            <livewire:roles.role-config-list :role="$role" />
        </flux:card>
    </div>
</x-layouts::app>
