<?php

use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    config(['ansible.paths.roles' => base_path('tests/Fixtures/ansible-roles')]);
});

test('guests are redirected to the login page', function () {
    $this->get(route('roles.index'))->assertRedirect(route('login'));
    $this->get(route('roles.edit', 'feature-role'))->assertRedirect(route('login'));
});

test('authenticated users see discovered roles with their registration status', function () {
    Role::factory()->create(['role' => 'complete-role', 'label' => 'Complete Role', 'always_apply' => true]);

    $this->actingAs(User::factory()->create())
        ->get(route('roles.index'))
        ->assertOk()
        ->assertSeeInOrder(['complete-role', 'Complete Role', 'Edit', 'feature-role', 'Register'])
        ->assertSee(route('roles.edit', 'feature-role'), false);
});

test('the roles page reports when no roles are discovered', function () {
    config(['ansible.paths.roles' => base_path('tests/Fixtures/ansible-roles-empty')]);

    $this->actingAs(User::factory()->create())
        ->get(route('roles.index'))
        ->assertOk()
        ->assertSee('No Ansible roles discovered.');
});

test('the sidebar links to the roles page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee(route('roles.index'), false);
});

test('the role page shows the form and configuration for a discovered role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('roles.edit', 'feature-role'))
        ->assertOk()
        ->assertSeeInOrder(['Register role', 'feature-role', 'Configuration']);
});

test('the role page is not found for an undiscovered role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('roles.edit', 'not-a-role'))
        ->assertNotFound();
});
