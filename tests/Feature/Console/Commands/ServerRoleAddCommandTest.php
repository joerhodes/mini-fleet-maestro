<?php

use App\Models\Role;
use App\Models\Server;

test('assigns roles to a server', function () {
    Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'power-management', 'always_apply' => false]);
    Role::factory()->create(['role' => 'xmrig', 'always_apply' => false]);

    $this->artisan('server:role-add', ['server' => 'msv05', 'roles' => ['power-management', 'xmrig']])
        ->expectsOutputToContain('Assigned [power-management, xmrig] to server [msv05].')
        ->assertExitCode(0);

    $this->assertDatabaseHas('server_roles', ['role' => 'power-management']);
    $this->assertDatabaseHas('server_roles', ['role' => 'xmrig']);
});

test('rejects an unknown server', function () {
    Role::factory()->create(['role' => 'power-management', 'always_apply' => false]);

    $this->artisan('server:role-add', ['server' => 'unknown-server', 'roles' => ['power-management']])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('server_roles', ['role' => 'power-management']);
});

test('rejects a role that is not registered', function () {
    Server::factory()->create(['name' => 'msv05']);

    $this->artisan('server:role-add', ['server' => 'msv05', 'roles' => ['unregistered-role']])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('server_roles', ['role' => 'unregistered-role']);
});

test('rejects a role that is always applied instead of individually assignable', function () {
    Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'common', 'always_apply' => true]);

    $this->artisan('server:role-add', ['server' => 'msv05', 'roles' => ['common']])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('server_roles', ['role' => 'common']);
});
