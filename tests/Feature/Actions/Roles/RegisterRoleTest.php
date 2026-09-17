<?php

use App\Actions\Roles\RegisterRole;
use App\Models\Role;

test('creates a role with the given attributes', function () {
    $registerRole = new RegisterRole;

    $model = $registerRole('feature-role', 'Feature Role', true);

    expect($model)->toBeInstanceOf(Role::class);
    $this->assertDatabaseHas('roles', [
        'role' => 'feature-role',
        'label' => 'Feature Role',
        'always_apply' => true,
    ]);
});

test('defaults always_apply to false when not given', function () {
    $registerRole = new RegisterRole;

    $model = $registerRole('feature-role', 'Feature Role');

    expect($model->always_apply)->toBeFalse();
});

test('returns the existing role without creating a duplicate when already registered', function () {
    Role::factory()->create(['role' => 'feature-role', 'label' => 'Original Label', 'always_apply' => false]);
    $registerRole = new RegisterRole;

    $model = $registerRole('feature-role', 'New Label', true);

    expect(Role::where('role', 'feature-role')->count())->toBe(1);
    expect($model->label)->toBe('Original Label');
    expect((bool) $model->always_apply)->toBeFalse();
});
