<?php

use App\Models\Role;
use App\Models\RoleConfig;

test('deletes a config value for a registered role', function () {
    $role = Role::factory()->create(['role' => 'complete-role']);
    RoleConfig::factory()->create(['role' => $role->role, 'key' => 'wallet_id', 'value' => '4Axxxx']);

    $this->artisan('role:config-delete', ['role' => 'complete-role', 'key' => 'wallet_id'])
        ->expectsOutputToContain('Deleted config [wallet_id] for role [complete-role].')
        ->assertExitCode(0);

    $this->assertDatabaseMissing('role_configs', ['role' => 'complete-role', 'key' => 'wallet_id']);
});

test('rejects a role that is not registered', function () {
    $this->artisan('role:config-delete', ['role' => 'unregistered-role', 'key' => 'wallet_id'])
        ->assertExitCode(1);
});

test('fails when no matching config exists for the role', function () {
    Role::factory()->create(['role' => 'complete-role']);

    $this->artisan('role:config-delete', ['role' => 'complete-role', 'key' => 'wallet_id'])
        ->expectsOutputToContain('No config [wallet_id] found for role [complete-role].')
        ->assertExitCode(1);
});

test('rejects a blank key', function () {
    Role::factory()->create(['role' => 'complete-role']);

    $this->artisan('role:config-delete', ['role' => 'complete-role', 'key' => ''])
        ->assertExitCode(1);
});
