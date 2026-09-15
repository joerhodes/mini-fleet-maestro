<?php

use App\Models\Role;

beforeEach(function () {
    config(['ansible.paths.roles' => base_path('tests/Fixtures/ansible-roles')]);
});

test('lists discovered roles as unregistered when none are registered', function () {
    $this->artisan('role:list')
        ->expectsTable(
            ['Role', 'Registered', 'Label', 'Always Apply'],
            [
                ['complete-role', 'No', '—', '—'],
                ['feature-role', 'No', '—', '—'],
            ],
        )
        ->assertExitCode(0);
});

test('lists the label and always_apply value for a registered role', function () {
    Role::factory()->create(['role' => 'complete-role', 'label' => 'Complete Role', 'always_apply' => true]);

    $this->artisan('role:list')
        ->expectsTable(
            ['Role', 'Registered', 'Label', 'Always Apply'],
            [
                ['complete-role', 'Yes', 'Complete Role', 'Yes'],
                ['feature-role', 'No', '—', '—'],
            ],
        )
        ->assertExitCode(0);
});

test('reports when no roles are discovered', function () {
    config(['ansible.paths.roles' => base_path('tests/Fixtures/ansible-roles-empty')]);

    $this->artisan('role:list')
        ->expectsOutputToContain('No Ansible roles discovered.')
        ->assertExitCode(0);
});
