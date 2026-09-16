<?php

use App\Models\Role;
use App\Models\RoleConfig;

test('role returns the owning role', function () {
    $role = Role::factory()->create(['role' => 'feature-role']);
    $config = RoleConfig::factory()->create(['role' => 'feature-role']);

    $related = $config->role()->first();

    expect($related)->toBeInstanceOf(Role::class);
    expect($related->is($role))->toBeTrue();
});
