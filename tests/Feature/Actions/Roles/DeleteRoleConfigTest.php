<?php

use App\Actions\Roles\DeleteRoleConfig;
use App\Models\Role;
use App\Models\RoleConfig;

test('deletes an existing config value for a role', function () {
    $role = Role::factory()->create(['role' => 'complete-role']);
    RoleConfig::factory()->create(['role' => $role->role, 'key' => 'wallet_id', 'value' => '4Axxxx']);
    $deleteRoleConfig = new DeleteRoleConfig;

    $deleted = $deleteRoleConfig($role->role, 'wallet_id');

    expect($deleted)->toBeTrue();
    $this->assertDatabaseMissing('role_configs', ['role' => 'complete-role', 'key' => 'wallet_id']);
});

test('returns false when no matching config exists', function () {
    $role = Role::factory()->create(['role' => 'complete-role']);
    $deleteRoleConfig = new DeleteRoleConfig;

    $deleted = $deleteRoleConfig($role->role, 'wallet_id');

    expect($deleted)->toBeFalse();
});
