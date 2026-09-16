<?php

use App\Actions\Servers\RemoveRolesFromServer;
use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;

test('removes the given roles from a server', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'power-management']);
    Role::factory()->create(['role' => 'xmrig']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'power-management']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'xmrig']);
    $removeRolesFromServer = new RemoveRolesFromServer;

    $removed = $removeRolesFromServer($server, ['power-management']);

    expect($removed)->toBe(1);
    $this->assertDatabaseMissing('server_roles', ['server_id' => $server->id, 'role' => 'power-management']);
    $this->assertDatabaseHas('server_roles', ['server_id' => $server->id, 'role' => 'xmrig']);
});

test('does not remove the same role from a different server', function () {
    $serverA = Server::factory()->create();
    $serverB = Server::factory()->create();
    Role::factory()->create(['role' => 'power-management']);
    ServerRole::factory()->create(['server_id' => $serverA->id, 'role' => 'power-management']);
    ServerRole::factory()->create(['server_id' => $serverB->id, 'role' => 'power-management']);
    $removeRolesFromServer = new RemoveRolesFromServer;

    $removeRolesFromServer($serverA, ['power-management']);

    $this->assertDatabaseMissing('server_roles', ['server_id' => $serverA->id, 'role' => 'power-management']);
    $this->assertDatabaseHas('server_roles', ['server_id' => $serverB->id, 'role' => 'power-management']);
});
