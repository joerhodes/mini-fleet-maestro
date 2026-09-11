<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <!-- x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" / -->
                <flux:card class="h-full">
                    <flux:heading size="xl">Servers</flux:heading>
                    <p>{{ $servers->count() }}</p>
                </flux:card>
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <!-- x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" / -->
            <flux:card class="space-y-5 h-full">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading size="xl">Server List</flux:heading>
                    </div>
                    <flux:button icon="plus">Add Server</flux:button>
                </div>
                <flux:table bleed>
                    <flux:table.columns>
                        <flux:table.column>Server</flux:table.column>
                        <flux:table.column>Hostname</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                    </flux:table.columns>

                    @foreach($servers as $server)
                        <flux:table.row>
                            <flux:table.cell>{{ $server->name }}</flux:table.cell>
                            <flux:table.cell>{{ $server->hostname }}</flux:table.cell>
                            <flux:table.cell>{{ $server->status }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table>
            </flux:card>
        </div>
    </div>
</x-layouts::app>
