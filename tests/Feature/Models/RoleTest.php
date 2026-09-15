<?php

use App\Models\Role;
use App\Models\RoleConfig;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

test('roleConfigs returns the role configs belonging to the role', function () {
    $role = Role::factory()->create(['role' => 'feature-role']);
    $otherRole = Role::factory()->create(['role' => 'other-role']);

    $config = RoleConfig::factory()->create(['role' => 'feature-role', 'key' => 'wallet_id']);
    RoleConfig::factory()->create(['role' => 'other-role', 'key' => 'pool_url']);

    expect($role->roleConfigs()->pluck('id'))->toEqual(collect([$config->id]));
    expect($otherRole->roleConfigs)->toHaveCount(1);
});

test('servers relation does not throw', function () {
    $role = Role::factory()->create();

    expect($role->servers())->toBeInstanceOf(HasManyThrough::class);
});
