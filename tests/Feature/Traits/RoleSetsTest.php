<?php

use App\Models\Server;
use App\Models\Role;
use App\Models\ServerRole;
use App\Traits\RoleSets;


test('it builds role set for servers', function () {
    $commonRole = Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    $serverRole = Role::factory()->create(['role' => 'server', 'always_apply' => false]);
    $server = Server::factory()->create();
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => $serverRole->role]);

    $sut = new class {
        use RoleSets;
    };

    $result = $sut->roleSetFor(collect([$server]));

    expect($result->pluck('role')->all())->toContain($serverRole->role);
    expect($result->pluck('role')->all())->not()->toContain($commonRole->role);
});

test('it builds role set for servers with always apply', function () {
    $commonRole = Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    $serverRole = Role::factory()->create(['role' => 'server', 'always_apply' => false]);
    $server = Server::factory()->create();
    ServerRole::factory()->create(['server_id' => $server->id, 'role' => $serverRole->role]);

    $sut = new class {
        use RoleSets;
    };

    $result = $sut->roleSetFor(collect([$server]), true);

    expect($result->pluck('role')->all())->toContain($serverRole->role);
    expect($result->pluck('role')->all())->toContain($commonRole->role);
});
