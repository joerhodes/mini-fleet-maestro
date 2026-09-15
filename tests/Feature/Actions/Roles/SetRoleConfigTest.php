<?php

use App\Actions\Roles\SetRoleConfig;
use App\Models\Role;
use App\Models\RoleConfig;

test('creates a new config value for a role', function () {
    $role = Role::factory()->create(['role' => 'complete-role']);
    $setRoleConfig = new SetRoleConfig;

    $config = $setRoleConfig($role->role, 'wallet_id', '4Axxxx');

    expect($config)->toBeInstanceOf(RoleConfig::class);
    $this->assertDatabaseHas('role_configs', [
        'role' => 'complete-role',
        'key' => 'wallet_id',
    ]);
    expect($config->fresh()->value)->toBe('4Axxxx');
});

test('updates the value of an existing config for the same role and key', function () {
    $role = Role::factory()->create(['role' => 'complete-role']);
    RoleConfig::factory()->create(['role' => $role->role, 'key' => 'wallet_id', 'value' => '4Axxxx']);
    $setRoleConfig = new SetRoleConfig;

    $config = $setRoleConfig($role->role, 'wallet_id', '4Byyyy');

    expect(RoleConfig::where('role', 'complete-role')->where('key', 'wallet_id')->count())->toBe(1);
    expect($config->fresh()->value)->toBe('4Byyyy');
});
