<?php

use App\Models\Server;
use App\Models\ServerConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->server = Server::factory()->create();
});

test('it lists the server configs by key without revealing their values', function () {
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'rig_id', 'value' => 'super-secret']);
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'api_token', 'value' => 'other-secret']);
    ServerConfig::factory()->create(['key' => 'other_key']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->assertSeeInOrder(['api_token', 'rig_id'])
        ->assertDontSee('other_key')
        ->assertDontSee('super-secret')
        ->assertDontSee('other-secret');
});

test('it shows an empty state when the server has no configuration', function () {
    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->assertSee('No configuration found for this server.')
        ->assertSee('Add Configuration');
});

test('it asks to save a new server first and hides the Add Configuration button', function () {
    Livewire::test('servers.server-config-list')
        ->assertSee('Save this server before managing its configuration.')
        ->assertDontSee('Add Configuration');
});

test('editing loads the current value and saving updates it', function () {
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'rig_id', 'value' => 'old']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('edit', 'rig_id')
        ->assertSet('form.key', 'rig_id')
        ->assertSet('form.value', 'old')
        ->set('form.value', 'new')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->server->secrets()->where('key', 'rig_id')->first()->value)->toBe('new');
});

test('editing can rename the key', function () {
    $config = ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'rig_id', 'value' => 'old']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('edit', 'rig_id')
        ->set('form.key', 'rig')
        ->call('save')
        ->assertHasNoErrors();

    expect(ServerConfig::count())->toBe(1)
        ->and($config->fresh()->key)->toBe('rig')
        ->and($config->fresh()->value)->toBe('old');
});

test('a key cannot be renamed to one the server already has', function () {
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'rig_id']);
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'api_token']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('edit', 'rig_id')
        ->set('form.key', 'api_token')
        ->call('save')
        ->assertHasErrors(['form.key' => 'unique']);
});

test('adding creates a config and clears leftovers from a previous edit', function () {
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'rig_id', 'value' => 'old']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('edit', 'rig_id')
        ->call('create')
        ->assertSet('form.key', '')
        ->assertSet('form.value', '')
        ->assertSet('form.configId', null)
        ->set('form.key', 'api_token')
        ->set('form.value', 'abc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('api_token');

    expect($this->server->secrets()->where('key', 'api_token')->first()->value)->toBe('abc');
});

test('key and value are required and the key must be unique when adding', function () {
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'rig_id', 'value' => 'old']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('create')
        ->call('save')
        ->assertHasErrors(['form.key' => 'required', 'form.value' => 'required'])
        ->set('form.key', 'rig_id')
        ->set('form.value', 'new')
        ->call('save')
        ->assertHasErrors(['form.key' => 'unique']);

    expect(ServerConfig::first()->value)->toBe('old');
});

test('the same key is allowed on a different server', function () {
    ServerConfig::factory()->create(['server_id' => Server::factory()->create()->id, 'key' => 'rig_id']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('create')
        ->set('form.key', 'rig_id')
        ->set('form.value', 'abc')
        ->call('save')
        ->assertHasNoErrors();

    expect(ServerConfig::count())->toBe(2);
});

test('configuration cannot be added to a server that has not been saved', function () {
    Livewire::test('servers.server-config-list')
        ->call('create')
        ->assertNotFound();
});

test('a config from another server cannot be edited or deleted', function () {
    $other = ServerConfig::factory()->create(['key' => 'rig_id', 'value' => 'theirs']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('delete', 'rig_id');

    expect($other->fresh())->not->toBeNull();

    expect(fn () => Livewire::test('servers.server-config-list', ['server' => $this->server])->call('edit', 'rig_id'))
        ->toThrow(ModelNotFoundException::class);
});

test('deleting removes the config', function () {
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'rig_id']);
    ServerConfig::factory()->create(['server_id' => $this->server->id, 'key' => 'api_token']);

    Livewire::test('servers.server-config-list', ['server' => $this->server])
        ->call('delete', 'rig_id')
        ->assertDontSee('rig_id')
        ->assertSee('api_token');

    expect($this->server->secrets()->pluck('key')->all())->toBe(['api_token']);
});
