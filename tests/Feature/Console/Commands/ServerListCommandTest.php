<?php

use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;

test('lists registered servers with a placeholder when no roles apply', function () {
    Server::factory()->create([
        'name' => 'msv05',
        'hostname' => 'msv05.local',
        'ssh_user' => 'admin',
        'ssh_port' => 22,
        'status' => 'connected',
    ]);

    $this->artisan('server:list')
        ->expectsTable(
            ['ID', 'Name', 'Hostname', 'SSH User', 'SSH Port', 'Status', 'Roles'],
            [
                [1, 'msv05', 'msv05.local', 'admin', 22, 'Connected', '—'],
            ],
        )
        ->assertExitCode(0);
});

test('lists assigned roles alongside always_apply roles', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'label' => 'Common', 'always_apply' => true]);
    Role::factory()->create(['role' => 'xmrig', 'label' => 'XMRig', 'always_apply' => false]);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);

    $this->artisan('server:list')
        ->expectsTable(
            ['ID', 'Name', 'Hostname', 'SSH User', 'SSH Port', 'Status', 'Roles'],
            [
                [$server->id, 'msv05', $server->hostname, $server->ssh_user, 22, 'Pending', 'Common, XMRig'],
            ],
        )
        ->assertExitCode(0);
});

test('lists an always_apply role for a server it was not individually assigned to', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'label' => 'Common', 'always_apply' => true]);

    $this->artisan('server:list')
        ->expectsTable(
            ['ID', 'Name', 'Hostname', 'SSH User', 'SSH Port', 'Status', 'Roles'],
            [
                [$server->id, 'msv05', $server->hostname, $server->ssh_user, 22, 'Pending', 'Common'],
            ],
        )
        ->assertExitCode(0);
});

test('reports when no servers are registered', function () {
    $this->artisan('server:list')
        ->expectsOutputToContain('No servers registered.')
        ->assertExitCode(0);
});
