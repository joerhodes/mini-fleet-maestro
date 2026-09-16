<?php

use App\Actions\Servers\AssignRolesToServer;
use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;

test('assigns roles to a server', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'power-management']);
    Role::factory()->create(['role' => 'xmrig']);
    $assignRolesToServer = new AssignRolesToServer;

    $result = $assignRolesToServer($server, ['power-management', 'xmrig']);

    expect($result)->toHaveCount(2);
    $this->assertDatabaseHas('server_roles', ['server_id' => $server->id, 'role' => 'power-management']);
    $this->assertDatabaseHas('server_roles', ['server_id' => $server->id, 'role' => 'xmrig']);
});

test('does not duplicate a role that is already assigned', function () {
    $server = Server::factory()->create();
    Role::factory()->create(['role' => 'power-management']);
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => 'power-management']);
    $assignRolesToServer = new AssignRolesToServer;

    $assignRolesToServer($server, ['power-management']);

    expect(ServerRole::where('server_id', $server->id)->where('role', 'power-management')->count())->toBe(1);
});
