<?php

use App\Models\Role;

beforeEach(function () {
    config(['ansible.paths.roles' => base_path('tests/Fixtures/ansible-roles')]);
});

test('registers a discovered role', function () {
    $this->artisan('role:register', ['role' => 'complete-role', 'label' => 'Complete Role'])
        ->expectsOutputToContain('Registered role [complete-role].')
        ->assertExitCode(0);

    $this->assertDatabaseHas('roles', [
        'role' => 'complete-role',
        'label' => 'Complete Role',
        'always_apply' => false,
    ]);
});

test('registers a role with always_apply when the flag is given', function () {
    $this->artisan('role:register', ['role' => 'complete-role', 'label' => 'Complete Role', '--always-apply' => true])
        ->assertExitCode(0);

    $this->assertDatabaseHas('roles', [
        'role' => 'complete-role',
        'always_apply' => true,
    ]);
});

test('rejects a role that was not discovered', function () {
    $this->artisan('role:register', ['role' => 'incomplete-role', 'label' => 'Incomplete Role'])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('roles', ['role' => 'incomplete-role']);
});

test('rejects a role that is already registered', function () {
    Role::factory()->create(['role' => 'complete-role']);

    $this->artisan('role:register', ['role' => 'complete-role', 'label' => 'Complete Role Again'])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('roles', ['label' => 'Complete Role Again']);
});

test('rejects a blank label', function () {
    $this->artisan('role:register', ['role' => 'complete-role', 'label' => ''])
        ->assertExitCode(1);

    $this->assertDatabaseMissing('roles', ['role' => 'complete-role']);
});
