<x-layouts::app :title="$server ? __('Edit Server') : __('Add Server')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:card class="space-y-6">
            <div class="flex flex-col gap-6 md:flex-row">
                <div class="flex-1">
                    <livewire:servers.server-form :server="$server" />
                </div>

                <flux:separator vertical class="hidden md:block" />

                <div class="md:w-72">
                    <livewire:servers.server-roles :server="$server" />
                </div>
            </div>

            <flux:separator />

            <livewire:servers.server-config-list :server="$server" />
        </flux:card>
    </div>
</x-layouts::app>
