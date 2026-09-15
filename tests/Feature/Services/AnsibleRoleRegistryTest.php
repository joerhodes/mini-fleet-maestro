<?php

use App\Models\Role;
use App\Services\AnsibleRoleRegistry;

beforeEach(function () {
    config(['ansible.paths.roles' => base_path('tests/Fixtures/ansible-roles')]);
});

test('discovered returns only role folders containing a tasks/main.yml file', function () {
    $registry = new AnsibleRoleRegistry;

    expect($registry->discovered()->all())
        ->toContain('complete-role', 'feature-role')
        ->not->toContain('incomplete-role');
});

test('registered returns the roles stored in the database', function () {
    Role::factory()->create(['role' => 'complete-role']);
    Role::factory()->create(['role' => 'other-role']);

    $registry = new AnsibleRoleRegistry;

    expect($registry->registered()->all())->toContain('complete-role', 'other-role');
});

test('unregistered returns discovered roles that are not yet registered', function () {
    Role::factory()->create(['role' => 'complete-role']);

    $registry = new AnsibleRoleRegistry;

    expect($registry->unregistered()->all())->toBe(['feature-role']);
});

test('assignable returns registered roles that are not always applied', function () {
    Role::factory()->create(['role' => 'complete-role', 'always_apply' => true]);
    Role::factory()->create(['role' => 'feature-role', 'always_apply' => false]);

    $registry = new AnsibleRoleRegistry;

    expect($registry->assignable()->all())->toBe(['feature-role']);
});
