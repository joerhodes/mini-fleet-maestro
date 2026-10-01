<x-layouts::app :title="__('Roles')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <flux:card class="space-y-5 h-full">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading size="xl">Role List</flux:heading>
                    </div>
                </div>
                @if($roles->isEmpty())
                    <flux:text>No Ansible roles discovered.</flux:text>
                @else
                    <flux:table bleed>
                        <flux:table.columns>
                            <flux:table.column>Role</flux:table.column>
                            <flux:table.column>Registered</flux:table.column>
                            <flux:table.column>Label</flux:table.column>
                            <flux:table.column>Always Apply</flux:table.column>
                            <flux:table.column></flux:table.column>
                        </flux:table.columns>

                        @foreach($roles as $role)
                            <flux:table.row>
                                <flux:table.cell>{{ $role['role'] }}</flux:table.cell>
                                <flux:table.cell>{{ $role['registered'] ? 'Yes' : 'No' }}</flux:table.cell>
                                <flux:table.cell>{{ $role['label'] ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $role['registered'] ? ($role['alwaysApply'] ? 'Yes' : 'No') : '—' }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="sm" :href="route('roles.edit', $role['role'])" wire:navigate>
                                        {{ $role['registered'] ? 'Edit' : 'Register' }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table>
                @endif
            </flux:card>
        </div>
    </div>
</x-layouts::app>
