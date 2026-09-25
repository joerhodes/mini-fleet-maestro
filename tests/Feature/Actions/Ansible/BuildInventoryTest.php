<?php

use App\Actions\Ansible\BuildInventory;
use App\Models\Role;
use App\Models\RoleConfig;
use App\Models\Server;
use App\Models\ServerConfig;
use App\Models\ServerRole;

test('returns an empty inventory when given no servers', function () {
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $inventory = (new BuildInventory)->handle(collect());

    expect($inventory)->toBe([]);
});

test('places always_apply role config in the top-level vars', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'ntp_server', 'value' => 'time.apple.com']);

    $inventory = (new BuildInventory)->handle(collect([$server]));

    expect($inventory['vars'])->toBe(['ntp_server' => 'time.apple.com']);
});

test('omits top-level vars when no always_apply role has config', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);

    $inventory = (new BuildInventory)->handle(collect([$server]));

    expect($inventory)->not->toHaveKey('vars');
});

test('groups a server under a child group for its assigned role with that role config as group vars', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    RoleConfig::factory()->create(['role' => 'xmrig', 'key' => 'pool_url', 'value' => 'pool.example.com']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);

    $inventory = (new BuildInventory)->handle(collect([$server]));

    expect($inventory['children'])->toBe([
        'xmrig' => [
            'vars' => ['pool_url' => 'pool.example.com'],
            'hosts' => ['msv05' => null],
        ],
    ]);
});

test('builds host vars from the server connection fields and its server_config secrets', function () {
    $server = Server::factory()->create([
        'name' => 'msv05',
        'hostname' => 'msv05.local',
        'ssh_user' => 'fleet',
        'ssh_port' => 2222,
    ]);
    ServerConfig::factory()->create(['server_id' => $server->id, 'key' => 'rig_id', 'value' => 'rig-05']);

    $inventory = (new BuildInventory)->handle(collect([$server]));

    expect($inventory['hosts']['msv05'])->toMatchArray([
        'ansible_host' => 'msv05.local',
        'ansible_user' => 'fleet',
        'ansible_port' => 2222,
        'rig_id' => 'rig-05',
    ]);
});

test('lists a server with no assigned roles as a host with the always_apply roles in app_roles', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);

    $inventory = (new BuildInventory)->handle(collect([$server]));

    expect($inventory['hosts']['msv05']['app_roles'])->toBe(['common']);
    expect($inventory)->not->toHaveKey('children');
});

test('includes always_apply roles alongside assigned roles in each host app_roles', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);

    $inventory = (new BuildInventory)->handle(collect([$server]));

    expect($inventory['hosts']['msv05']['app_roles'])->toEqualCanonicalizing(['common', 'xmrig']);
});

test('lists every given server sharing a role under a single group for that role', function () {
    $firstServer = Server::factory()->create(['name' => 'msv05']);
    $secondServer = Server::factory()->create(['name' => 'msv06']);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    ServerRole::factory()->create(['server_id' => $firstServer->id, 'role' => 'xmrig']);
    ServerRole::factory()->create(['server_id' => $secondServer->id, 'role' => 'xmrig']);

    $inventory = (new BuildInventory)->handle(collect([$firstServer, $secondServer]));

    expect(array_keys($inventory['children']))->toBe(['xmrig']);
    expect($inventory['children']['xmrig']['hosts'])->toBe(['msv05' => null, 'msv06' => null]);
});

test('excludes servers that were not given even when they share an assigned role', function () {
    $givenServer = Server::factory()->create(['name' => 'msv05']);
    $otherServer = Server::factory()->create(['name' => 'msv06']);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    ServerRole::factory()->create(['server_id' => $givenServer->id, 'role' => 'xmrig']);
    ServerRole::factory()->create(['server_id' => $otherServer->id, 'role' => 'xmrig']);

    $inventory = (new BuildInventory)->handle(collect([$givenServer]));

    expect(array_keys($inventory['hosts']))->toBe(['msv05']);
    expect($inventory['children']['xmrig']['hosts'])->toBe(['msv05' => null]);
});

test('omits group vars for an assigned role that has no config', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);

    $inventory = (new BuildInventory)->handle(collect([$server]));

    expect($inventory['children'])->toBe(['xmrig' => ['hosts' => ['msv05' => null]]]);
});
