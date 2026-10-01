<?php

use App\Actions\Servers\AssignRolesToServer;
use App\Actions\Servers\RemoveRolesFromServer;
use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;
use App\Services\AnsibleRoleRegistry;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $serverId = null;

    public function mount(?Server $server = null): void
    {
        $this->serverId = $server?->id;
    }

    /**
     * Always-apply roles first, then the rest, each tier sorted by label.
     *
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::all()->sortBy([['always_apply', 'desc'], ['label', 'asc']])->values();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function assigned(): array
    {
        return ServerRole::where('server_id', $this->serverId)->pluck('role')->all();
    }

    public function toggle(string $role): void
    {
        abort_if($this->serverId === null, 404);
        abort_unless(app(AnsibleRoleRegistry::class)->assignable()->contains($role), 404);

        $server = Server::findOrFail($this->serverId);

        if (in_array($role, $this->assigned, true)) {
            app(RemoveRolesFromServer::class)($server, [$role]);
        } else {
            app(AssignRolesToServer::class)($server, [$role]);
        }

        unset($this->assigned);
    }
};
?>

<div class="space-y-5">
    <flux:heading size="lg">Roles</flux:heading>

    @if($serverId === null)
        <flux:text>Save this server before assigning roles.</flux:text>
    @elseif($this->roles->isEmpty())
        <flux:text>No roles have been registered.</flux:text>
    @else
        <ul class="space-y-4">
            @foreach($this->roles as $role)
                <li wire:key="role-{{ $role->role }}" class="flex items-center justify-between gap-4">
                    <div>
                        <flux:text class="font-medium">{{ $role->label }}</flux:text>
                        @if($role->always_apply)
                            <flux:text size="sm">Always applied</flux:text>
                        @endif
                    </div>

                    @if($role->always_apply)
                        <flux:switch checked disabled :aria-label="$role->label" />
                    @else
                        <flux:switch
                            :checked="in_array($role->role, $this->assigned, true)"
                            :aria-label="$role->label"
                            wire:click="toggle('{{ $role->role }}')"
                        />
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
