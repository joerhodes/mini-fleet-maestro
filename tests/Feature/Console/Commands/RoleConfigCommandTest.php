<?php

use App\Models\Role;
use App\Models\RoleConfig;

test('sets a config value for a registered role', function () {
    Role::factory()->create(['role' => 'complete-role']);

    $this->artisan('role:config', ['role' => 'complete-role', 'key' => 'wallet_id', 'value' => '4Axxxx'])
        ->expectsOutputToContain('Set config [wallet_id] for role [complete-role].')
        ->assertExitCode(0);

    $this->assertDatabaseHas('role_configs', ['role' => 'complete-role', 'key' => 'wallet_id']);
    expect(RoleConfig::where('role', 'complete-role')->where('key', 'wallet_id')->first()->value)->toBe('4Axxxx');
});

test('updates an existing config value instead of duplicating it', function () {
    $role = Role::factory()->create(['role' => 'complete-role']);
    RoleConfig::factory()->create(['role' => $role->role, 'key' => 'wallet_id', 'value' => '4Axxxx']);

    $this->artisan('role:config', ['role' => 'complete-role', 'key' => 'wallet_id', 'value' => '4Byyyy'])
        ->assertExitCode(0);

    expect(RoleConfig::where('role', 'complete-role')->where('key', 'wallet_id')->count())->toBe(1);
    expect(RoleConfig::where('role', 'complete-role')->where('key', 'wallet_id')->first()->value)->toBe('4Byyyy');
});

test('rejects a role that is not registered', function () {
    $this->artisan('role:config', ['role' => 'unregistered-role', 'key' => 'wallet_id', 'value' => '4Axxxx'])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('role_configs', ['role' => 'unregistered-role']);
});

test('rejects a blank key', function () {
    Role::factory()->create(['role' => 'complete-role']);

    $this->artisan('role:config', ['role' => 'complete-role', 'key' => '', 'value' => '4Axxxx'])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('role_configs', ['role' => 'complete-role']);
});
