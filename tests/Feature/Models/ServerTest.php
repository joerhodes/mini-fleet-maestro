<?php

use App\Models\Role;
use App\Models\Server;
use App\Models\ServerRole;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('roles returns a HasMany relation', function () {
    $server = new Server;

    expect($server->roles())->toBeInstanceOf(HasMany::class);
});

test('roles returns the role assignments belonging to the server', function () {
    $server = Server::factory()->create();
    $otherServer = Server::factory()->create();

    $role = Role::factory()->create();
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => $role->role]);
    ServerRole::factory()->create(['server_id' => $otherServer->id]);

    expect($server->roles()->pluck('role'))->toEqual(collect([$role->role]));
    expect($otherServer->roles)->toHaveCount(1);
});
