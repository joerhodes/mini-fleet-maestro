<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['maestro.ansible.paths.roles' => base_path('tests/Fixtures/ansible-roles')]);
    $this->actingAs(User::factory()->create());
});

test('an unregistered role starts with an empty form', function () {
    Livewire::test('roles.role-form', ['role' => 'feature-role'])
        ->assertSet('form.role', 'feature-role')
        ->assertSet('form.label', '')
        ->assertSet('form.alwaysApply', false)
        ->assertSet('form.isRegistered', false)
        ->assertSee('Register role');
});

test('a registered role loads its label and always_apply value', function () {
    Role::factory()->create(['role' => 'complete-role', 'label' => 'Complete Role', 'always_apply' => true]);

    Livewire::test('roles.role-form', ['role' => 'complete-role'])
        ->assertSet('form.label', 'Complete Role')
        ->assertSet('form.alwaysApply', true)
        ->assertSet('form.isRegistered', true)
        ->assertSee('Edit role');
});

test('saving registers an unregistered role', function () {
    Livewire::test('roles.role-form', ['role' => 'feature-role'])
        ->set('form.label', 'Feature Role')
        ->set('form.alwaysApply', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('role-saved')
        ->assertSet('form.isRegistered', true);

    $this->assertDatabaseHas('roles', ['role' => 'feature-role', 'label' => 'Feature Role', 'always_apply' => true]);
});

test('saving updates the label and clears always_apply', function () {
    Role::factory()->create(['role' => 'complete-role', 'label' => 'Old', 'always_apply' => true]);

    Livewire::test('roles.role-form', ['role' => 'complete-role'])
        ->set('form.label', 'New')
        ->set('form.alwaysApply', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('roles', ['role' => 'complete-role', 'label' => 'New', 'always_apply' => false]);
    expect(Role::count())->toBe(1);
});

test('the label is required', function () {
    Livewire::test('roles.role-form', ['role' => 'feature-role'])
        ->set('form.label', '')
        ->call('save')
        ->assertHasErrors(['form.label' => 'required']);

    expect(Role::count())->toBe(0);
});

test('the role name cannot be tampered with', function () {
    Livewire::test('roles.role-form', ['role' => 'feature-role'])
        ->set('form.role', 'not-a-role');
})->throws(Exception::class, 'Cannot update locked property');

test('a role that was not discovered cannot be saved', function () {
    Livewire::test('roles.role-form', ['role' => 'not-a-role'])
        ->set('form.label', 'Nope')
        ->call('save')
        ->assertHasErrors(['form.role']);

    expect(Role::count())->toBe(0);
});
