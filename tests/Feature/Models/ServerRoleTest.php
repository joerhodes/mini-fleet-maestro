<?php

use App\Models\Role;
use App\Models\ServerRole;

test('role returns the role assigned to the server_role', function () {
    $role = Role::factory()->create(['role' => 'power-management']);
    Role::factory()->create(['role' => 'xmrig']);

    $serverRole = ServerRole::factory()->create(['role' => 'power-management']);

    $related = $serverRole->role()->first();

    expect($related)->toBeInstanceOf(Role::class);
    expect($related->is($role))->toBeTrue();
});
