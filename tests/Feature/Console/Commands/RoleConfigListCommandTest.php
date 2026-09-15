<?php

use App\Models\Role;
use App\Models\RoleConfig;

test('lists the configuration values stored for a role', function () {
    $role = Role::factory()->create(['role' => 'complete-role']);
    RoleConfig::factory()->create(['role' => $role->role, 'key' => 'pool_url', 'value' => 'pool.example.com']);
    RoleConfig::factory()->create(['role' => $role->role, 'key' => 'wallet_id', 'value' => '4Axxxx']);

    $this->artisan('role:config-list', ['role' => 'complete-role'])
        ->expectsTable(
            ['Key', 'Value'],
            [
                ['pool_url', 'pool.example.com'],
                ['wallet_id', '4Axxxx'],
            ],
        )
        ->assertExitCode(0);
});

test('reports when a role has no configuration', function () {
    Role::factory()->create(['role' => 'complete-role']);

    $this->artisan('role:config-list', ['role' => 'complete-role'])
        ->expectsOutputToContain('No configuration found for role [complete-role].')
        ->assertExitCode(0);
});

test('rejects a role that is not registered', function () {
    $this->artisan('role:config-list', ['role' => 'unregistered-role'])
        ->assertExitCode(1);
});
