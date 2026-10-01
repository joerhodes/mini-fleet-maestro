<?php

use App\Models\Role;
use App\Models\RoleConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    $this->role = Role::factory()->create(['role' => 'mining']);
});

test('it lists the role configs by key without revealing their values', function () {
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id', 'value' => 'super-secret']);
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'pool_url', 'value' => 'pool.invalid']);
    RoleConfig::factory()->create(['key' => 'other_key']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->assertSeeInOrder(['pool_url', 'wallet_id'])
        ->assertDontSee('other_key')
        ->assertDontSee('super-secret');
});

test('it shows an empty state when the role has no configuration', function () {
    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->assertSee('No configuration found for this role.');
});

test('it asks to register an unregistered role first', function () {
    Livewire::test('roles.role-config-list', ['role' => 'feature-role'])
        ->assertSee('Save this role to register it');
});

test('it refreshes when the role is saved', function () {
    $component = Livewire::test('roles.role-config-list', ['role' => 'new-role'])
        ->assertSee('Save this role to register it');

    Role::factory()->create(['role' => 'new-role']);

    $component->dispatch('role-saved')->assertSee('No configuration found for this role.');
});

test('editing loads the current value and saving updates it', function () {
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id', 'value' => 'old']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('edit', 'wallet_id')
        ->assertSet('form.key', 'wallet_id')
        ->assertSet('form.value', 'old')
        ->set('form.value', 'new')
        ->call('save')
        ->assertHasNoErrors();

    expect(RoleConfig::where('role', 'mining')->where('key', 'wallet_id')->first()->value)->toBe('new');
});

test('editing can rename the key', function () {
    $config = RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id', 'value' => 'old']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('edit', 'wallet_id')
        ->set('form.key', 'wallet')
        ->call('save')
        ->assertHasNoErrors();

    expect(RoleConfig::count())->toBe(1)
        ->and($config->fresh()->key)->toBe('wallet')
        ->and($config->fresh()->value)->toBe('old');
});

test('a key cannot be renamed to one the role already has', function () {
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id']);
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'pool_url']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('edit', 'wallet_id')
        ->set('form.key', 'pool_url')
        ->call('save')
        ->assertHasErrors(['form.key' => 'unique']);
});

test('adding creates a config with the given key and value', function () {
    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('create')
        ->assertSet('form.key', '')
        ->assertSet('form.configId', null)
        ->set('form.key', 'wallet_id')
        ->set('form.value', 'abc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('wallet_id');

    expect(RoleConfig::where('role', 'mining')->where('key', 'wallet_id')->first()->value)->toBe('abc');
});

test('adding clears values left over from a previous edit', function () {
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id', 'value' => 'old']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('edit', 'wallet_id')
        ->call('create')
        ->assertSet('form.key', '')
        ->assertSet('form.value', '')
        ->assertSet('form.configId', null);
});

test('adding requires a unique key, a key and a value', function () {
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id', 'value' => 'old']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('create')
        ->call('save')
        ->assertHasErrors(['form.key' => 'required', 'form.value' => 'required'])
        ->set('form.key', 'wallet_id')
        ->set('form.value', 'new')
        ->call('save')
        ->assertHasErrors(['form.key' => 'unique']);

    expect(RoleConfig::first()->value)->toBe('old');
});

test('the same key is allowed on a different role', function () {
    RoleConfig::factory()->create(['role' => Role::factory()->create()->role, 'key' => 'wallet_id']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('create')
        ->set('form.key', 'wallet_id')
        ->set('form.value', 'abc')
        ->call('save')
        ->assertHasNoErrors();

    expect(RoleConfig::count())->toBe(2);
});

test('configuration cannot be added to an unregistered role', function () {
    Livewire::test('roles.role-config-list', ['role' => 'feature-role'])
        ->call('create')
        ->assertNotFound();
});

test('only registered roles show the Add Configuration button', function () {
    Livewire::test('roles.role-config-list', ['role' => 'mining'])->assertSee('Add Configuration');
    Livewire::test('roles.role-config-list', ['role' => 'feature-role'])->assertDontSee('Add Configuration');
});

test('the value and key are required when editing', function () {
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id', 'value' => 'old']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('edit', 'wallet_id')
        ->set('form.key', '')
        ->set('form.value', '')
        ->call('save')
        ->assertHasErrors(['form.key' => 'required', 'form.value' => 'required']);

    expect(RoleConfig::first()->value)->toBe('old');
});

test('a config from another role cannot be edited or deleted', function () {
    $other = RoleConfig::factory()->create(['key' => 'wallet_id', 'value' => 'theirs']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('delete', 'wallet_id');

    expect($other->fresh())->not->toBeNull();

    expect(fn () => Livewire::test('roles.role-config-list', ['role' => 'mining'])->call('edit', 'wallet_id'))
        ->toThrow(ModelNotFoundException::class);
});

test('deleting removes the config', function () {
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'wallet_id']);
    RoleConfig::factory()->create(['role' => 'mining', 'key' => 'pool_url']);

    Livewire::test('roles.role-config-list', ['role' => 'mining'])
        ->call('delete', 'wallet_id')
        ->assertDontSee('wallet_id')
        ->assertSee('pool_url');

    expect(RoleConfig::where('role', 'mining')->pluck('key')->all())->toBe(['pool_url']);
});
