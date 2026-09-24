<?php

use App\Models\Role;
use App\Models\RoleConfig;
use App\Traits\RoleVars;

test('it builds role vars', function () {
    $role = Role::factory()->create(['role' => 'common', 'always_apply' => true]);
    RoleConfig::factory()->create(['role' => 'common', 'key' => 'key1', 'value' => 'value2']);

    $sut = new class {
        use RoleVars;
    };

    $result = $sut->buildRoleVars(collect([$role]));

    expect($result)->toBe(['key1' => 'value2']);
});
