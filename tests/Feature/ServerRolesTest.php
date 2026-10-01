<?php

use App\Models\Role;
use App\Models\Server;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->server = Server::factory()->create();
});

test('roles are listed by label with always-apply roles first', function () {
    Role::factory()->create(['role' => 'zeta', 'label' => 'Alpha Optional']);
    Role::factory()->create(['role' => 'beta', 'label' => 'Beta Optional']);
    Role::factory()->create(['role' => 'common', 'label' => 'Zulu Common', 'always_apply' => true]);

    Livewire::test('servers.server-roles', ['server' => $this->server])
        ->assertSeeInOrder(['Zulu Common', 'Always applied', 'Alpha Optional', 'Beta Optional']);
});

test('always-apply roles render as checked and disabled switches', function () {
    Role::factory()->create(['label' => 'Common', 'always_apply' => true]);

    $html = Livewire::test('servers.server-roles', ['server' => $this->server])->html();

    expect($html)->toMatch('/<ui-switch[^>]*\bdisabled\b/s')
        ->and($html)->toMatch('/<ui-switch[^>]*\bchecked\b/s');
});

test('each optional role switch is wired to toggle that role', function () {
    Role::factory()->create(['role' => 'mining', 'label' => 'Mining']);

    Livewire::test('servers.server-roles', ['server' => $this->server])
        ->assertSeeHtml('wire:click="toggle(\'mining\')"');
});

test('toggling assigns and then removes a role', function () {
    Role::factory()->create(['role' => 'mining', 'label' => 'Mining']);

    $component = Livewire::test('servers.server-roles', ['server' => $this->server])
        ->call('toggle', 'mining');

    expect($this->server->roles()->pluck('roles.role')->all())->toBe(['mining']);

    $component->call('toggle', 'mining');

    expect($this->server->roles()->count())->toBe(0);
});

test('assigned roles are shown as on', function () {
    $role = Role::factory()->create(['label' => 'Mining']);
    $this->server->roles()->attach($role->role);

    expect(Livewire::test('servers.server-roles', ['server' => $this->server])->instance()->assigned)
        ->toBe([$role->role]);
});

test('always-apply and unregistered roles cannot be toggled', function () {
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);

    Livewire::test('servers.server-roles', ['server' => $this->server])
        ->call('toggle', 'common')
        ->assertNotFound();

    Livewire::test('servers.server-roles', ['server' => $this->server])
        ->call('toggle', 'not-a-role')
        ->assertNotFound();

    expect($this->server->roles()->count())->toBe(0);
});

test('toggling only affects this server', function () {
    $role = Role::factory()->create();
    $other = Server::factory()->create();
    $other->roles()->attach($role->role);

    Livewire::test('servers.server-roles', ['server' => $this->server])->call('toggle', $role->role);
    Livewire::test('servers.server-roles', ['server' => $this->server])->call('toggle', $role->role);

    expect($other->roles()->count())->toBe(1);
});

test('an unsaved server asks to be saved first and cannot toggle', function () {
    Role::factory()->create(['role' => 'mining']);

    Livewire::test('servers.server-roles')
        ->assertSee('Save this server before assigning roles.')
        ->call('toggle', 'mining')
        ->assertNotFound();
});

test('it reports when no roles are registered', function () {
    Livewire::test('servers.server-roles', ['server' => $this->server])
        ->assertSee('No roles have been registered.');
});

test('the edit page shows the roles beside the server details', function () {
    Role::factory()->create(['label' => 'Mining']);

    $this->get(route('servers.edit', $this->server))
        ->assertOk()
        ->assertSeeInOrder(['Edit server', 'Roles', 'Mining', 'Configuration']);
});
