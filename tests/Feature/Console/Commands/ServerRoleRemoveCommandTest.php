<?php

use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;

test('removes an assigned role from a server', function () {
    $server = Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'power-management']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'power-management']);

    $this->artisan('server:role-remove', ['server' => 'msv05', 'roles' => ['power-management']])
        ->expectsOutputToContain('Removed [power-management] from server [msv05].')
        ->assertExitCode(0);

    $this->assertDatabaseMissing('server_roles', ['server_id' => $server->id, 'role' => 'power-management']);
});

test('rejects an unknown server', function () {
    $this->artisan('server:role-remove', ['server' => 'unknown-server', 'roles' => ['power-management']])
        ->assertExitCode(1);
});

test('rejects a role that is not assigned to the server', function () {
    Server::factory()->create(['name' => 'msv05']);
    Role::factory()->create(['role' => 'power-management']);

    $this->artisan('server:role-remove', ['server' => 'msv05', 'roles' => ['power-management']])
        ->assertExitCode(1);
});
